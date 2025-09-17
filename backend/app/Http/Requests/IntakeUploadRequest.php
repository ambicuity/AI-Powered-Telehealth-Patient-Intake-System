<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IntakeUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->isPatient();
    }

    public function rules(): array
    {
        $maxFileSize = config('app.max_file_size', 10240); // KB
        $allowedMimes = explode(',', config('app.allowed_mime_types', 'application/pdf,image/jpeg,image/png,image/jpg'));

        return [
            'file' => [
                'required_without:text',
                'file',
                "max:{$maxFileSize}",
                'mimes:pdf,jpeg,jpg,png',
                function ($attribute, $value, $fail) use ($allowedMimes) {
                    if ($value && !in_array($value->getMimeType(), $allowedMimes)) {
                        $fail('The file type is not allowed.');
                    }
                },
            ],
            'text' => ['required_without:file', 'string', 'max:10000'],
            'source_type' => ['required', 'string', 'in:pdf,image,text'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required_without' => 'Either a file or text must be provided.',
            'text.required_without' => 'Either a file or text must be provided.',
            'file.max' => 'File size must not exceed ' . (config('app.max_file_size', 10240) / 1024) . 'MB.',
            'file.mimes' => 'File must be a PDF or image (JPEG, JPG, PNG).',
            'source_type.required' => 'Source type is required.',
            'source_type.in' => 'Source type must be pdf, image, or text.',
            'text.max' => 'Text content must not exceed 10,000 characters.',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Auto-detect source type if not provided
        if (!$this->has('source_type')) {
            if ($this->hasFile('file')) {
                $file = $this->file('file');
                $mimeType = $file->getMimeType();
                
                if ($mimeType === 'application/pdf') {
                    $this->merge(['source_type' => 'pdf']);
                } elseif (str_starts_with($mimeType, 'image/')) {
                    $this->merge(['source_type' => 'image']);
                }
            } elseif ($this->has('text')) {
                $this->merge(['source_type' => 'text']);
            }
        }
    }
}