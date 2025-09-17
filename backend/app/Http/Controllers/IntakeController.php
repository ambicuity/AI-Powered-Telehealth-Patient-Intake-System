<?php

namespace App\Http\Controllers;

use App\Http\Requests\IntakeUploadRequest;
use App\Http\Resources\IntakeFormResource;
use App\Models\AuditLog;
use App\Models\IntakeForm;
use App\Services\IntakeProcessingService;
use App\Services\S3Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IntakeController extends Controller
{
    public function __construct(
        private IntakeProcessingService $intakeProcessingService,
        private S3Service $s3Service
    ) {}

    public function store(IntakeUploadRequest $request): JsonResponse
    {
        $user = $request->user();
        
        if (!$user->isPatient()) {
            return response()->json([
                'message' => 'Unauthorized. Only patients can upload intake forms.',
            ], 403);
        }

        $patient = $user->patient;
        if (!$patient) {
            return response()->json([
                'message' => 'Patient record not found.',
            ], 404);
        }

        $validated = $request->validated();
        $sourceUrl = null;

        // Handle file upload to S3
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $sourceUrl = $this->s3Service->uploadIntakeFile($file, $patient->id);
        }

        // Create intake form record
        $intakeForm = IntakeForm::create([
            'patient_id' => $patient->id,
            'status' => 'uploaded',
            'source_type' => $validated['source_type'],
            'source_url' => $sourceUrl,
        ]);

        AuditLog::log('create', 'IntakeForm', $intakeForm->id, [
            'source_type' => $validated['source_type'],
            'has_file' => $request->hasFile('file'),
        ]);

        // Process the intake form (sync or async based on config)
        try {
            $this->intakeProcessingService->process($intakeForm, $validated);
        } catch (\Exception $e) {
            $intakeForm->update(['status' => 'failed']);
            
            return response()->json([
                'message' => 'Failed to process intake form',
                'error' => $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'data' => new IntakeFormResource($intakeForm->fresh()),
            'message' => 'Intake form uploaded successfully',
        ], 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        
        $intakeForm = IntakeForm::with('patient.user')->findOrFail($id);

        // Authorization check
        if ($user->isPatient() && $intakeForm->patient->user_id !== $user->id) {
            return response()->json([
                'message' => 'Unauthorized. You can only view your own intake forms.',
            ], 403);
        }

        if ($user->isProvider() && !$this->canProviderAccessIntake($user, $intakeForm)) {
            return response()->json([
                'message' => 'Unauthorized. You can only view intake forms for your patients.',
            ], 403);
        }

        AuditLog::log('view', 'IntakeForm', $intakeForm->id);

        return response()->json([
            'data' => new IntakeFormResource($intakeForm),
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = IntakeForm::with('patient.user');

        if ($user->isPatient()) {
            $patient = $user->patient;
            if (!$patient) {
                return response()->json(['data' => []]);
            }
            $query->where('patient_id', $patient->id);
        } elseif ($user->isProvider()) {
            // Providers can see intake forms for their patients
            $patientIds = $this->getProviderPatientIds($user);
            $query->whereIn('patient_id', $patientIds);
        }

        // Filter by patient_id if provided
        if ($request->has('patient_id')) {
            $query->where('patient_id', $request->input('patient_id'));
        }

        // Filter by status if provided
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        $intakeForms = $query->orderBy('created_at', 'desc')
                            ->paginate(20);

        return response()->json([
            'data' => IntakeFormResource::collection($intakeForms->items()),
            'meta' => [
                'current_page' => $intakeForms->currentPage(),
                'per_page' => $intakeForms->perPage(),
                'total' => $intakeForms->total(),
                'last_page' => $intakeForms->lastPage(),
            ],
        ]);
    }

    private function canProviderAccessIntake($provider, $intakeForm): bool
    {
        // Check if provider has appointments with this patient
        return $provider->providerAppointments()
                       ->where('patient_id', $intakeForm->patient_id)
                       ->exists();
    }

    private function getProviderPatientIds($provider): array
    {
        return $provider->providerAppointments()
                       ->distinct()
                       ->pluck('patient_id')
                       ->toArray();
    }
}