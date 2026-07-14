@extends('layouts.app')

@section('title', 'Manage files')

@section('content')
<section class="management-section">
    <div class="container app-container">
        <div class="page-heading d-flex flex-column flex-md-row align-items-md-end justify-content-between gap-4">
            <div>
                <div class="eyebrow"><span></span> File management</div>
                <h1>Your temporary files</h1>
                <p>Review, replace, or remove documents before their automatic expiry.</p>
            </div>
            <a class="btn btn-primary new-upload-button" href="{{ route('files.upload') }}">
                <svg viewBox="0 0 24 24" fill="none"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                New upload
            </a>
        </div>

        <div class="alert d-none page-alert" id="pageAlert" role="alert"></div>

        <div class="files-panel">
            <div class="files-panel-head">
                <div><strong>Stored documents</strong><span id="fileCount">{{ count($files) }} on this page</span></div>
                <span class="privacy-note">
                    <svg viewBox="0 0 24 24" fill="none"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z" stroke="currentColor" stroke-width="1.8"/><path d="m9 12 2 2 4-4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                    Private storage
                </span>
            </div>

            @if (count($files) > 0)
                <div class="table-responsive">
                    <table class="table files-table align-middle mb-0">
                        <thead><tr><th>Document</th><th>Size</th><th>Uploaded</th><th>Expires</th><th><span class="visually-hidden">Actions</span></th></tr></thead>
                        <tbody id="filesTableBody">
                        @foreach ($files as $file)
                            <tr data-file-row="{{ $file->id }}">
                                <td>
                                    <div class="document-cell">
                                        <span class="document-icon {{ $file->extension === 'docx' ? 'docx' : 'pdf' }}">{{ strtoupper($file->extension) }}</span>
                                        <span><strong title="{{ $file->originalName }}">{{ $file->originalName }}</strong><small>{{ strtoupper($file->extension) }} document</small></span>
                                    </div>
                                </td>
                                <td><span class="table-primary-text">{{ Illuminate\Support\Number::fileSize($file->sizeBytes, 1) }}</span></td>
                                <td><span class="table-primary-text">{{ $file->uploadedAt->format('M j, Y') }}</span><small class="table-subtext">{{ $file->uploadedAt->format('H:i T') }}</small></td>
                                <td><span class="expiry-badge"><i></i>{{ $file->expiresAt->format('M j, H:i') }}</span></td>
                                <td>
                                    <div class="dropdown text-end">
                                        <button class="btn action-menu" type="button" data-bs-toggle="dropdown" data-bs-boundary="viewport" aria-expanded="false" aria-label="Actions for {{ $file->originalName }}">•••</button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow-sm file-actions-menu">
                                            <li><button class="dropdown-item replace-file" type="button" data-file-name="{{ $file->originalName }}" data-update-url="{{ route('files.update', $file->id) }}"><span>↻</span> Replace file</button></li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li><button class="dropdown-item text-danger delete-file" type="button" data-file-name="{{ $file->originalName }}" data-file-row="{{ $file->id }}" data-delete-url="{{ route('files.destroy', $file->id) }}"><span>×</span> Delete permanently</button></li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="empty-state" id="emptyState">
                    <span class="empty-icon"><svg viewBox="0 0 24 24" fill="none"><path d="M7 3h7l4 4v14H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="1.7"/><path d="M14 3v5h5M9 13h6M9 17h4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg></span>
                    <h2>No files yet</h2>
                    <p>Upload your first PDF or DOCX document. It will appear here instantly.</p>
                    <a class="btn btn-primary" href="{{ route('files.upload') }}">Upload a document</a>
                </div>
            @endif
        </div>

        @if ($page > 1 || $hasNextPage)
            <nav class="pagination-nav" aria-label="File pages">
                <a class="btn btn-outline-secondary {{ $page <= 1 ? 'disabled' : '' }}" href="{{ $page > 1 ? route('files.index', ['page' => $page - 1]) : '#' }}">← Previous</a>
                <span>Page {{ $page }}</span>
                <a class="btn btn-outline-secondary {{ !$hasNextPage ? 'disabled' : '' }}" href="{{ $hasNextPage ? route('files.index', ['page' => $page + 1]) : '#' }}">Next →</a>
            </nav>
        @endif
    </div>
</section>
@endsection

@push('modals')
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered"><div class="modal-content app-modal">
        <div class="modal-icon danger"><svg viewBox="0 0 24 24" fill="none"><path d="M4 7h16M9 7V4h6v3m-8 0 1 14h8l1-14M10 11v6m4-6v6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></div>
        <h2 id="deleteModalTitle">Delete this file?</h2>
        <p><strong id="deleteFileName"></strong> will be removed immediately. A deletion notification will be queued.</p>
        <div class="modal-actions"><button class="btn btn-light" type="button" data-bs-dismiss="modal">Keep file</button><button class="btn btn-danger" id="confirmDelete" type="button">Delete permanently</button></div>
    </div></div>
</div>

<div class="modal fade" id="replaceModal" tabindex="-1" aria-labelledby="replaceModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered"><div class="modal-content app-modal">
        <div class="modal-icon"><svg viewBox="0 0 24 24" fill="none"><path d="M20 7h-5V2M4 17h5v5M19 12a7 7 0 0 0-12-5l-3 3m1 2a7 7 0 0 0 12 5l3-3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></div>
        <h2 id="replaceModalTitle">Replace document</h2>
        <p>Choose a new PDF or DOCX for <strong id="replaceFileName"></strong>. This restarts its 24-hour retention period.</p>
        <form id="replaceForm" novalidate>
            <input class="form-control form-control-lg" id="replacementFile" name="file" type="file" accept=".pdf,.docx" required>
            <div class="alert alert-danger d-none mt-3 mb-0" id="replaceErrors"></div>
            <div class="modal-actions"><button class="btn btn-light" type="button" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary" id="confirmReplace" type="submit">Replace file</button></div>
        </form>
    </div></div>
</div>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const deleteModal = new bootstrap.Modal('#deleteModal');
    const replaceModal = new bootstrap.Modal('#replaceModal');
    let deleteUrl = null;
    let deleteRow = null;
    let updateUrl = null;

    function alertPage(message, type) {
        $('#pageAlert').removeClass('d-none alert-success alert-danger').addClass('alert-' + type).text(message);
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function responseErrors(xhr) {
        const errors = xhr.responseJSON?.errors;
        return errors ? Object.values(errors).flat() : [xhr.responseJSON?.message || 'Something went wrong. Please try again.'];
    }

    document.querySelectorAll('.files-table .dropdown').forEach(function (dropdown) {
        const menu = dropdown.querySelector('.file-actions-menu');
        let placeholder = null;
        if (!menu) return;

        dropdown.addEventListener('show.bs.dropdown', function () {
            if (placeholder) return;

            placeholder = document.createComment('file actions menu');
            menu.before(placeholder);
            document.body.append(menu);
        });

        dropdown.addEventListener('hidden.bs.dropdown', function () {
            if (!placeholder) return;

            placeholder.replaceWith(menu);
            placeholder = null;
        });
    });

    $('.delete-file').on('click', function () {
        deleteUrl = $(this).data('delete-url');
        deleteRow = $(this).data('file-row');
        $('#deleteFileName').text($(this).data('file-name'));
        deleteModal.show();
    });

    $('#confirmDelete').on('click', function () {
        const $button = $(this).prop('disabled', true).text('Deleting…');
        $.ajax({ url: deleteUrl, method: 'DELETE', headers: { 'Accept': 'application/json' } })
            .done(function (response) {
                $('[data-file-row="' + deleteRow + '"]').fadeOut(220, function () {
                    $(this).remove();
                    const count = $('#filesTableBody tr').length;
                    $('#fileCount').text(count + ' on this page');
                    if (count === 0) window.location.reload();
                });
                deleteModal.hide();
                alertPage(response.message, 'success');
            })
            .fail(function (xhr) { deleteModal.hide(); alertPage(responseErrors(xhr)[0], 'danger'); })
            .always(function () { $button.prop('disabled', false).text('Delete permanently'); });
    });

    $('.replace-file').on('click', function () {
        updateUrl = $(this).data('update-url');
        $('#replaceFileName').text($(this).data('file-name'));
        $('#replacementFile').val('');
        $('#replaceErrors').addClass('d-none');
        replaceModal.show();
    });

    $('#replaceForm').on('submit', function (event) {
        event.preventDefault();
        const file = $('#replacementFile')[0].files[0];
        if (!file || !/\.(pdf|docx)$/i.test(file.name) || file.size > 10 * 1024 * 1024) {
            $('#replaceErrors').removeClass('d-none').text('Choose a PDF or DOCX file no larger than 10 MB.');
            return;
        }

        const $button = $('#confirmReplace').prop('disabled', true).text('Replacing…');
        $.ajax({
            url: updateUrl,
            method: 'POST',
            data: new FormData(this),
            processData: false,
            contentType: false,
            headers: { 'Accept': 'application/json' }
        }).done(function (response) {
            replaceModal.hide();
            alertPage(response.message, 'success');
            window.setTimeout(() => window.location.reload(), 700);
        }).fail(function (xhr) {
            $('#replaceErrors').removeClass('d-none').html(responseErrors(xhr).map(message => $('<div>').text(message).html()).join('<br>'));
        }).always(function () {
            $button.prop('disabled', false).text('Replace file');
        });
    });
});
</script>
@endpush
