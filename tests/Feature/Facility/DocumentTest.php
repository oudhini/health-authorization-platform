<?php

declare(strict_types=1);

namespace Tests\Feature\Facility;

use App\Actions\Requests\CreateAuthorizationRequest;
use App\Domain\Requests\RequestStatus;
use App\Enums\DocumentType;
use App\Models\AuthorizationRequest;
use App\Models\Document;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Support\Scenario;
use Tests\TestCase;

/** Secure upload and download (docs/requirements.md, 8.2). */
final class DocumentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    private function draft(Scenario $s): AuthorizationRequest
    {
        return app(CreateAuthorizationRequest::class)->handle(
            $s->agent,
            $s->insurer->id,
            $s->beneficiary->id,
            $s->policy->id,
            [['medical_act_id' => $s->echo->id, 'billed_amount' => 10000]],
        );
    }

    private function upload(Scenario $s, AuthorizationRequest $request, UploadedFile $file, string $type = 'medical_form')
    {
        return $this->actingAs($s->agent)->post(
            route('facility.documents.store', $request),
            ['type' => $type, 'document' => $file],
        );
    }

    public function test_agent_uploads_a_pdf_to_the_private_disk_under_a_random_name(): void
    {
        $s = Scenario::create();
        $request = $this->draft($s);

        $this->upload($s, $request, UploadedFile::fake()->create('fiche-maladie.pdf', 120, 'application/pdf'))
            ->assertSessionHasNoErrors();

        $document = Document::query()->firstOrFail();

        $this->assertSame(DocumentType::MedicalForm, $document->type);
        $this->assertSame('fiche-maladie.pdf', $document->original_name);
        $this->assertSame($s->agent->id, $document->uploaded_by);
        $this->assertStringNotContainsString('fiche-maladie', $document->stored_path);
        Storage::disk('local')->assertExists($document->stored_path);
        $this->assertDatabaseHas('audit_logs', ['action' => 'document.uploaded', 'user_id' => $s->agent->id]);
    }

    public function test_images_are_accepted(): void
    {
        $s = Scenario::create();
        $request = $this->draft($s);

        $this->upload($s, $request, UploadedFile::fake()->create('scan.png', 50, 'image/png'), 'other')->assertSessionHasNoErrors();

        $this->assertSame(1, Document::query()->count());
    }

    public function test_executables_and_oversized_files_are_rejected(): void
    {
        $s = Scenario::create();
        $request = $this->draft($s);

        $this->upload($s, $request, UploadedFile::fake()->create('virus.exe', 10, 'application/x-msdownload'))
            ->assertSessionHasErrors('document');
        $this->upload($s, $request, UploadedFile::fake()->create('huge.pdf', 6000, 'application/pdf'))
            ->assertSessionHasErrors('document');
        $this->upload($s, $request, UploadedFile::fake()->create('page.php', 1, 'text/x-php'))
            ->assertSessionHasErrors('document');

        $this->assertSame(0, Document::query()->count());
    }

    public function test_document_type_and_file_are_required(): void
    {
        $s = Scenario::create();
        $request = $this->draft($s);

        $this->actingAs($s->agent)->post(route('facility.documents.store', $request), [])
            ->assertSessionHasErrors(['type', 'document']);
        $this->upload($s, $request, UploadedFile::fake()->create('a.pdf', 10, 'application/pdf'), 'not-a-type')
            ->assertSessionHasErrors('type');
    }

    public function test_no_document_can_be_added_once_the_request_is_submitted(): void
    {
        $s = Scenario::create();
        $request = $this->draft($s);
        $request->update(['status' => RequestStatus::Submitted]);

        $this->upload($s, $request, UploadedFile::fake()->create('a.pdf', 10, 'application/pdf'))->assertForbidden();

        $this->assertSame(0, Document::query()->count());
    }

    public function test_owner_downloads_the_document_and_the_download_is_audited(): void
    {
        $s = Scenario::create();
        $request = $this->draft($s);
        $this->upload($s, $request, UploadedFile::fake()->create('fiche.pdf', 10, 'application/pdf'));
        $document = Document::query()->firstOrFail();

        $this->actingAs($s->agent)
            ->get(route('documents.download', $document))
            ->assertOk()
            ->assertDownload('fiche.pdf');

        $this->assertDatabaseHas('audit_logs', ['action' => 'document.downloaded', 'user_id' => $s->agent->id]);
    }

    public function test_other_organizations_cannot_download_it(): void
    {
        $s = Scenario::create();
        $request = $this->draft($s);
        $this->upload($s, $request, UploadedFile::fake()->create('fiche.pdf', 10, 'application/pdf'));
        $document = Document::query()->firstOrFail();

        $this->actingAs($s->otherAgent)->get(route('documents.download', $document))->assertNotFound();
        $this->actingAs($s->otherManager)->get(route('documents.download', $document))->assertNotFound();
        $this->actingAs($s->admin)->get(route('documents.download', $document))->assertNotFound();
        $this->assertDatabaseMissing('audit_logs', ['action' => 'document.downloaded']);
    }

    public function test_the_insurer_sees_documents_only_after_the_request_is_sent(): void
    {
        $s = Scenario::create();
        $request = $this->draft($s);
        $this->upload($s, $request, UploadedFile::fake()->create('fiche.pdf', 10, 'application/pdf'));
        $document = Document::query()->firstOrFail();

        $this->actingAs($s->manager)->get(route('documents.download', $document))->assertNotFound();

        $request->update(['status' => RequestStatus::Submitted]);

        $this->actingAs($s->manager)->get(route('documents.download', $document))->assertOk();
        $this->actingAs($s->otherManager)->get(route('documents.download', $document))->assertNotFound();
    }

    public function test_guests_cannot_download(): void
    {
        $s = Scenario::create();
        $request = $this->draft($s);
        // Created directly (no upload, hence no logged-in user) so the next request is a true guest.
        $document = $request->documents()->create([
            'type' => DocumentType::Other, 'original_name' => 'a.pdf', 'stored_path' => 'documents/a.pdf',
            'mime_type' => 'application/pdf', 'size_bytes' => 10, 'uploaded_by' => $s->agent->id,
        ]);

        $this->get(route('documents.download', $document))->assertRedirect(route('login'));
    }
}
