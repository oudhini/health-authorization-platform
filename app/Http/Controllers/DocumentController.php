<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Documents\AttachDocument;
use App\Enums\AuditAction;
use App\Enums\DocumentType;
use App\Http\Requests\StoreDocumentRequest;
use App\Models\AuthorizationRequest;
use App\Models\Document;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class DocumentController extends Controller
{
    public function store(
        StoreDocumentRequest $request,
        AuthorizationRequest $authorizationRequest,
        AttachDocument $attach,
    ): RedirectResponse {
        Gate::authorize('attachDocument', $authorizationRequest);

        $attach->handle(
            $request->user(),
            $authorizationRequest,
            $request->file('document'),
            DocumentType::from($request->validated('type')),
        );

        return back()->with('status', 'Document ajouté.');
    }

    public function download(Request $request, Document $document, AuditLogger $audit): StreamedResponse
    {
        Gate::authorize('view', $document);

        $audit->record(AuditAction::DocumentDownloaded, $request->user(), $document);

        // Always an attachment, served through the application: the file is never publicly reachable.
        return Storage::disk('local')->download($document->stored_path, $document->original_name);
    }
}
