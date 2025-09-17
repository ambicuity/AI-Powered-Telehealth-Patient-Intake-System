<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdatePatientRequest;
use App\Http\Resources\PatientResource;
use App\Models\AuditLog;
use App\Models\Appointment;
use App\Models\IntakeForm;
use App\Models\Patient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PatientController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        
        if (!$user->isPatient()) {
            return response()->json([
                'message' => 'Unauthorized. Only patients can access this endpoint.',
            ], 403);
        }

        $patient = $user->patient()->with('user')->first();

        if (!$patient) {
            // Create patient record if it doesn't exist
            $patient = Patient::create([
                'user_id' => $user->id,
            ]);
            $patient->load('user');
        }

        return response()->json([
            'data' => new PatientResource($patient),
        ]);
    }

    public function update(UpdatePatientRequest $request): JsonResponse
    {
        $user = $request->user();
        
        if (!$user->isPatient()) {
            return response()->json([
                'message' => 'Unauthorized. Only patients can access this endpoint.',
            ], 403);
        }

        $patient = $user->patient;
        $validated = $request->validated();

        // Update user fields
        if (isset($validated['name']) || isset($validated['phone'])) {
            $user->update(array_filter([
                'name' => $validated['name'] ?? null,
                'phone' => $validated['phone'] ?? null,
            ]));
        }

        // Update patient fields
        $patient->update(array_filter([
            'dob' => $validated['dob'] ?? null,
            'gender' => $validated['gender'] ?? null,
            'address' => $validated['address'] ?? null,
            'insurance_provider' => $validated['insurance_provider'] ?? null,
            'insurance_member_id' => $validated['insurance_member_id'] ?? null,
            'emergency_contact' => $validated['emergency_contact'] ?? null,
        ]));

        AuditLog::log('update', 'Patient', $patient->id, [
            'updated_fields' => array_keys($validated),
        ]);

        return response()->json([
            'data' => new PatientResource($patient->fresh('user')),
            'message' => 'Profile updated successfully',
        ]);
    }

    public function stats(Request $request): JsonResponse
    {
        $user = $request->user();
        
        if (!$user->isPatient()) {
            return response()->json([
                'message' => 'Unauthorized. Only patients can access this endpoint.',
            ], 403);
        }

        $patient = $user->patient;

        if (!$patient) {
            return response()->json([
                'data' => [
                    'appointments_count' => 0,
                    'intake_forms_count' => 0,
                    'pending_appointments' => 0,
                ],
            ]);
        }

        $appointmentsCount = Appointment::where('patient_id', $patient->id)->count();
        $intakeFormsCount = IntakeForm::where('patient_id', $patient->id)->count();
        $pendingAppointments = Appointment::where('patient_id', $patient->id)
            ->whereIn('status', ['requested', 'scheduled'])
            ->count();

        return response()->json([
            'data' => [
                'appointments_count' => $appointmentsCount,
                'intake_forms_count' => $intakeFormsCount,
                'pending_appointments' => $pendingAppointments,
            ],
        ]);
    }
}