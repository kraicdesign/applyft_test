<?php

declare(strict_types=1);

namespace App\Presentation\Http\Request;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

abstract class FileUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'file' => [
                'bail',
                'required',
                'file',
                'max:10240',
                'extensions:pdf,docx',
                'mimetypes:application/pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'file.required' => 'Choose a PDF or DOCX file to continue.',
            'file.max' => 'The file may not be larger than 10 MB.',
            'file.extensions' => 'Only files ending in .pdf or .docx are accepted.',
            'file.mimetypes' => 'The selected file is not a valid PDF or DOCX document.',
        ];
    }

    public function uploadedFile(): UploadedFile
    {
        $uploadedFile = $this->file('file');

        if (! $uploadedFile instanceof UploadedFile || ! is_string($uploadedFile->getRealPath())) {
            throw ValidationException::withMessages([
                'file' => 'The uploaded file could not be read. Please try again.',
            ]);
        }

        if (! is_string($uploadedFile->getMimeType()) || ! is_int($uploadedFile->getSize())) {
            throw ValidationException::withMessages([
                'file' => 'The uploaded file metadata could not be read.',
            ]);
        }

        return $uploadedFile;
    }
}
