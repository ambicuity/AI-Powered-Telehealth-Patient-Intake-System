<?php

namespace App\Services;

use App\Models\IntakeForm;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;

class IntakeProcessingService
{
    private Client $httpClient;
    private string $mlServiceUrl;
    private int $timeout;

    public function __construct()
    {
        $this->httpClient = new Client();
        $this->mlServiceUrl = config('services.ml.url', 'http://ml-service:8001');
        $this->timeout = config('services.ml.timeout', 30);
    }

    public function process(IntakeForm $intakeForm, array $data): IntakeForm
    {
        $mode = config('services.ml.mode', 'sync');

        if ($mode === 'async') {
            return $this->processAsync($intakeForm, $data);
        }

        return $this->processSync($intakeForm, $data);
    }

    private function processSync(IntakeForm $intakeForm, array $data): IntakeForm
    {
        $intakeForm->update(['status' => 'processing']);

        try {
            $mlResponse = $this->callMlService($intakeForm, $data);
            
            $intakeForm->update([
                'status' => 'extracted',
                'extracted_payload' => $mlResponse['data'] ?? null,
                'confidence' => $mlResponse['confidence'] ?? null,
                'processed_at' => now(),
            ]);

            Log::info('Intake form processed successfully', [
                'intake_form_id' => $intakeForm->id,
                'confidence' => $mlResponse['confidence'] ?? null,
            ]);

        } catch (\Exception $e) {
            $intakeForm->update([
                'status' => 'failed',
                'processed_at' => now(),
            ]);

            Log::error('Failed to process intake form', [
                'intake_form_id' => $intakeForm->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }

        return $intakeForm;
    }

    private function processAsync(IntakeForm $intakeForm, array $data): IntakeForm
    {
        // TODO: Implement queue job for async processing
        // For now, fall back to sync processing
        return $this->processSync($intakeForm, $data);
    }

    private function callMlService(IntakeForm $intakeForm, array $data): array
    {
        $payload = $this->buildMlPayload($intakeForm, $data);

        try {
            $response = $this->httpClient->post("{$this->mlServiceUrl}/parse", [
                'timeout' => $this->timeout,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ],
                'json' => $payload,
            ]);

            $responseData = json_decode($response->getBody()->getContents(), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Invalid JSON response from ML service');
            }

            return $responseData;

        } catch (RequestException $e) {
            Log::error('ML service request failed', [
                'intake_form_id' => $intakeForm->id,
                'url' => "{$this->mlServiceUrl}/parse",
                'error' => $e->getMessage(),
                'response' => $e->getResponse() ? $e->getResponse()->getBody()->getContents() : null,
            ]);

            throw new \Exception('ML service is unavailable: ' . $e->getMessage());
        }
    }

    private function buildMlPayload(IntakeForm $intakeForm, array $data): array
    {
        $payload = [
            'source_type' => $intakeForm->source_type,
            'intake_form_id' => $intakeForm->id,
        ];

        if (isset($data['text'])) {
            $payload['text'] = $data['text'];
        }

        if ($intakeForm->source_url) {
            // For file uploads, we'll need to provide access to the file
            // In a real implementation, you might provide a signed URL or file content
            $payload['file_path'] = $intakeForm->source_url;
        }

        return $payload;
    }

    public function retryProcessing(IntakeForm $intakeForm): IntakeForm
    {
        if (!in_array($intakeForm->status, ['failed', 'uploaded'])) {
            throw new \Exception('Intake form is not in a retryable state');
        }

        // Reset status and retry
        $intakeForm->update([
            'status' => 'uploaded',
            'extracted_payload' => null,
            'confidence' => null,
            'processed_at' => null,
        ]);

        // Rebuild the original data structure for retry
        $data = [];
        if ($intakeForm->source_type === 'text') {
            // For text intake, we might need to store the original text
            // This is a limitation of the current design
            throw new \Exception('Text-based intake forms cannot be retried without original text');
        }

        return $this->process($intakeForm, $data);
    }

    public function validateMlResponse(array $response): bool
    {
        if (!isset($response['data'])) {
            return false;
        }

        if (isset($response['confidence'])) {
            $confidence = $response['confidence'];
            if (!is_numeric($confidence) || $confidence < 0 || $confidence > 1) {
                return false;
            }
        }

        return true;
    }
}