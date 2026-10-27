<?php

declare(strict_types=1);

namespace App\Actions\Documents;

use App\Enums\AuditAction;
use App\Enums\DocumentType;
use App\Models\AuthorizationRequest;
use App\Models\Document;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/** Stores an uploaded file on the PRIVATE disk and records it. */
final class AttachDocument
{
    public function __construct(private readonly AuditLogger $audit)
    {
    }

    public function handle(User $user, AuthorizationRequest $request, UploadedFile $file, DocumentType $type): Document
    {
        // putFile generates a random file name: the client-supplied name is never
        // used on disk (no path tricks, no collisions, no guessable URLs).
        $path = Storage::disk('local')->putFile('documents', $file);

        $document = $request->documents()->create([
            'type' => $type,
            // Kept only to show and name the download; cleaned and truncated.
            'original_name' => Str::limit(basename($file->getClientOriginalName()), 200, ''),
            'stored_path' => $path,
            'mime_type' => (string) $file->getMimeType(),
            'size_bytes' => (int) $file->getSize(),
            'uploaded_by' => $user->id,
        ]);

        $this->audit->record(AuditAction::DocumentUploaded, $user, $document);

        return $document;
    }
}
