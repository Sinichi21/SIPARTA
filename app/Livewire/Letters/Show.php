<?php

namespace App\Livewire\Letters;

use App\Models\Letter;
use App\Services\AuditService;
use App\Services\LetterService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;

class Show extends Component
{
    use WithFileUploads;

    public Letter $letter;

    public string $cancellationReason = '';

    public $attachmentUpload;

    public function mount(Letter $letter): void
    {
        Gate::authorize('letters.view');

        abort_unless(
            $letter->letterType?->code === 'SPT',
            404
        );

        $this->letter = $letter->load([
            'letterType',
            'activityType',
            'personnels.unit',
            'creator',
            'updater',
            'canceller',
            'attachments',
            'submissionEvents.actor',
            'outgoingLetter.issuedLetter',
        ]);
    }

    public function submit(\App\Services\SptSubmissionService $service): void
    {
        Gate::authorize('letters.submit');
        $this->letter = $service->submit($this->letter, Auth::id());
        $this->letter->load(['letterType','activityType','personnels.unit','creator','updater',
            'canceller','attachments','outgoingLetter.issuedLetter','submissionEvents.actor']);
        session()->flash('success', 'Pengajuan SPT berhasil dikirim.');
    }

    public string $reviewNote = '';

    private function reloadReviewLetter(\App\Models\Letter $letter): void
    {
        $this->letter = $letter->load([
            'letterType', 'activityType', 'personnels.unit', 'creator', 'updater',
            'canceller', 'attachments', 'outgoingLetter.issuedLetter',
            'submissionEvents.actor',
        ]);
        $this->reviewNote = '';
    }

    public function verify(\App\Services\SptReviewService $service): void
    {
        Gate::authorize('letters.verify');
        $this->validate(['reviewNote' => ['nullable', 'string', 'max:2000']]);
        $this->reloadReviewLetter($service->verify($this->letter, Auth::id(), $this->reviewNote));
        session()->flash('success', 'Pengajuan SPT berhasil diverifikasi.');
    }

    public function approve(\App\Services\SptReviewService $service): void
    {
        Gate::authorize('letters.approve');
        $this->validate(['reviewNote' => ['nullable', 'string', 'max:2000']]);
        $this->reloadReviewLetter($service->approve($this->letter, Auth::id(), $this->reviewNote));
        session()->flash('success', 'Pengajuan SPT berhasil disetujui.');
    }

    public function returnForRevision(\App\Services\SptReviewService $service): void
    {
        $ability = $this->letter->status === \App\Enums\LetterStatus::Verified
            ? 'letters.approve' : 'letters.verify';
        Gate::authorize($ability);
        $this->validate(['reviewNote' => ['required', 'string', 'min:10', 'max:2000']]);
        $this->reloadReviewLetter($service->returnForRevision($this->letter, Auth::id(), $this->reviewNote));
        session()->flash('success', 'Pengajuan dikembalikan untuk diperbaiki.');
    }

    public function publish(LetterService $service): void
    {
        Gate::authorize('letters.publish');

        $this->letter = $service
            ->publish($this->letter, Auth::id())
            ->load([
                'letterType',
                'activityType',
                'personnels.unit',
                'creator',
                'updater',
                'canceller',
                'attachments',
            'submissionEvents.actor',
            'outgoingLetter.issuedLetter',
            ]);

        session()->flash(
            'success',
            'SPT berhasil diterbitkan.'
        );
    }

    public function cancel(LetterService $service): void
    {
        Gate::authorize('letters.cancel');

        $this->validate([
            'cancellationReason' => [
                'required',
                'string',
                'min:5',
                'max:1000',
            ],
        ]);

        $this->letter = $service
            ->cancel(
                $this->letter,
                $this->cancellationReason,
                Auth::id()
            )
            ->load([
                'letterType',
                'activityType',
                'personnels.unit',
                'creator',
                'updater',
                'canceller',
                'attachments',
            'submissionEvents.actor',
            'outgoingLetter.issuedLetter',
            ]);

        $this->cancellationReason = '';

        session()->flash(
            'success',
            'SPT berhasil dibatalkan.'
        );
    }

    public function uploadAttachment(AuditService $audit): void
    {
        Gate::authorize('letters.update');

        abort_unless(
            $this->letter->canBeEdited(),
            403,
            'Dokumen hanya dapat diubah pada SPT draft atau hasil import.'
        );

        $this->validate([
            'attachmentUpload' => [
                'required',
                'file',
                'mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png',
                'max:10240',
            ],
        ], [
            'attachmentUpload.required' => 'Pilih dokumen yang akan diunggah.',
            'attachmentUpload.mimes' => 'Format dokumen harus PDF, Word, Excel, JPG, atau PNG.',
            'attachmentUpload.max' => 'Ukuran dokumen maksimal 10 MB.',
        ]);

        $originalName = $this->attachmentUpload
            ->getClientOriginalName();

        $extension = strtolower(
            $this->attachmentUpload
                ->getClientOriginalExtension()
        );

        $storedName = (string) Str::uuid()
            .($extension !== '' ? '.'.$extension : '');

        $path = $this->attachmentUpload->storeAs(
            'spt/attachments/'.$this->letter->id,
            $storedName
        );

        abort_unless(
            is_string($path) && $path !== '',
            500,
            'Dokumen gagal disimpan.'
        );

        try {
            $contents = Storage::get($path);

            $attachment = $this->letter
                ->attachments()
                ->create([
                    'original_name' => $originalName,
                    'stored_name' => $storedName,
                    'path' => $path,
                    'mime_type' => Storage::mimeType($path),
                    'size' => Storage::size($path),
                    'checksum_sha256' => hash(
                        'sha256',
                        $contents
                    ),
                    'uploaded_by' => Auth::id(),
                ]);

            $audit->attachmentUploaded($attachment);
        } catch (\Throwable $exception) {
            Storage::delete($path);

            throw $exception;
        }

        $this->attachmentUpload = null;

        $this->letter->load('attachments');

        session()->flash(
            'success',
            'Dokumen pendukung berhasil diunggah.'
        );
    }

    public function deleteAttachment(
        int $attachmentId,
        AuditService $audit
    ): void {
        Gate::authorize('letters.update');

        abort_unless(
            $this->letter->canBeEdited(),
            403,
            'Dokumen hanya dapat diubah pada SPT draft atau hasil import.'
        );

        $attachment = $this->letter
            ->attachments()
            ->findOrFail($attachmentId);

        $audit->attachmentDeleted($attachment);

        Storage::delete($attachment->path);

        $attachment->delete();

        $this->letter->load('attachments');

        session()->flash(
            'success',
            'Dokumen pendukung berhasil dihapus.'
        );
    }

    public function render()
    {
        return view('livewire.letters.show');
    }

    public function downloadAttachment(int $attachmentId)
    {
        Gate::authorize('letters.view');
        $attachment = $this->letter->attachments()->findOrFail($attachmentId);

        if (! Storage::exists($attachment->path)) {
            $this->addError('download', 'Dokumen tidak ditemukan di penyimpanan.');

            return null;
        }

        return Storage::download($attachment->path, $attachment->original_name);
    }
}
