@extends('layouts.app')

@section('title', 'Upload a file')

@section('content')
<section class="upload-section">
    <div class="container app-container">
        <div class="row align-items-center g-5">
            <div class="col-lg-5">
                <div class="eyebrow"><span></span> Private by design</div>
                <h1 class="display-title">Share less.<br><span>Store smarter.</span></h1>
                <p class="hero-copy">Upload a document to a private, temporary space. It will be removed automatically after 24 hours—no cleanup required.</p>

                <div class="feature-list">
                    <div class="feature-item">
                        <span class="feature-icon">
                            <svg viewBox="0 0 24 24" fill="none"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z" stroke="currentColor" stroke-width="1.8"/><path d="m9 12 2 2 4-4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </span>
                        <span><strong>Private storage</strong><small>Files are kept outside the public web root.</small></span>
                    </div>
                    <div class="feature-item">
                        <span class="feature-icon">
                            <svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.8"/><path d="M12 7v5l3 2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </span>
                        <span><strong>Automatic expiry</strong><small>Every upload is deleted after 24 hours.</small></span>
                    </div>
                </div>
            </div>

            <div class="col-lg-7 col-xl-6 offset-xl-1">
                <div class="upload-card">
                    <div class="upload-card-head">
                        <div>
                            <span class="section-kicker">New upload</span>
                            <h2>Choose your document</h2>
                        </div>
                        <span class="secure-badge">
                            <svg viewBox="0 0 24 24" fill="none"><rect x="5" y="10" width="14" height="11" rx="2" stroke="currentColor" stroke-width="1.8"/><path d="M8 10V7a4 4 0 0 1 8 0v3" stroke="currentColor" stroke-width="1.8"/></svg>
                            Secure
                        </span>
                    </div>

                    <form id="uploadForm" action="{{ route('files.store') }}" method="post" enctype="multipart/form-data" novalidate>
                        @csrf
                        <label class="drop-zone" id="dropZone" for="fileInput">
                            <input class="visually-hidden" type="file" id="fileInput" name="file" accept=".pdf,.docx,application/pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document">
                            <span class="upload-orbit">
                                <span class="upload-icon">
                                    <svg viewBox="0 0 24 24" fill="none"><path d="M12 16V4m0 0 4.5 4.5M12 4 7.5 8.5M5 15v3a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </span>
                            </span>
                            <strong>Drop your file here</strong>
                            <span>or <u>browse from your device</u></span>
                            <small>PDF or DOCX · Maximum 10 MB</small>
                        </label>

                        <div class="selected-file d-none" id="selectedFile">
                            <span class="file-type-icon" id="selectedFileType">PDF</span>
                            <span class="file-summary">
                                <strong id="selectedFileName"></strong>
                                <small id="selectedFileSize"></small>
                            </span>
                            <button class="icon-button" id="clearFile" type="button" aria-label="Remove selected file">
                                <svg viewBox="0 0 24 24" fill="none"><path d="m7 7 10 10M17 7 7 17" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                            </button>
                        </div>

                        <div class="upload-progress d-none" id="uploadProgress" aria-live="polite">
                            <div class="d-flex justify-content-between mb-2"><span id="progressLabel">Uploading securely…</span><strong id="progressValue">0%</strong></div>
                            <div class="progress" role="progressbar" aria-label="Upload progress" aria-valuemin="0" aria-valuemax="100">
                                <div class="progress-bar" id="progressBar" style="width: 0%"></div>
                            </div>
                        </div>

                        <div class="alert alert-danger d-none mt-3 mb-0" id="uploadErrors" role="alert"></div>

                        <div class="upload-success d-none" id="uploadSuccess" role="status">
                            <span class="success-check">
                                <svg viewBox="0 0 24 24" fill="none"><path d="m6 12 4 4 8-8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </span>
                            <div><strong>Upload complete</strong><span id="successMessage"></span></div>
                        </div>

                        <button class="btn btn-primary upload-button w-100" id="uploadButton" type="submit" disabled>
                            <span class="button-label">Upload securely</span>
                            <span class="spinner-border spinner-border-sm d-none" aria-hidden="true"></span>
                        </button>
                    </form>

                    <div class="upload-card-foot">
                        <span><svg viewBox="0 0 24 24" fill="none"><path d="M12 8v4m0 4h.01M10.3 3.8 2.5 17.2A2 2 0 0 0 4.2 20h15.6a2 2 0 0 0 1.7-2.8L13.7 3.8a2 2 0 0 0-3.4 0Z" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg> Automatic deletion cannot be undone.</span>
                        <a href="{{ route('files.index') }}">Manage uploads →</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const maxSize = 10 * 1024 * 1024;
    const $form = $('#uploadForm');
    const $input = $('#fileInput');
    const $dropZone = $('#dropZone');
    const $button = $('#uploadButton');

    function formatBytes(bytes) {
        if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / (1024 * 1024)).toFixed(2) + ' MB';
    }

    function showErrors(messages) {
        const items = Array.isArray(messages) ? messages : [messages];
        $('#uploadErrors').html(items.map(message => '<div>' + $('<div>').text(message).html() + '</div>').join('')).removeClass('d-none');
    }

    function resetMessages() {
        $('#uploadErrors, #uploadSuccess').addClass('d-none');
    }

    function validateFile(file) {
        if (!file) return 'Choose a PDF or DOCX file to continue.';
        if (!/\.(pdf|docx)$/i.test(file.name)) return 'Only PDF and DOCX files are accepted.';
        if (file.size > maxSize) return 'The file may not be larger than 10 MB.';
        return null;
    }

    function selectFile(file) {
        resetMessages();
        const error = validateFile(file);
        if (error) {
            $input.val('');
            $('#selectedFile').addClass('d-none');
            $button.prop('disabled', true);
            showErrors(error);
            return;
        }

        const extension = file.name.split('.').pop().toUpperCase();
        $('#selectedFileType').text(extension).toggleClass('docx', extension === 'DOCX');
        $('#selectedFileName').text(file.name);
        $('#selectedFileSize').text(formatBytes(file.size) + ' · Ready to upload');
        $('#selectedFile').removeClass('d-none');
        $button.prop('disabled', false);
    }

    $input.on('change', function () { selectFile(this.files[0]); });
    $dropZone.on('dragenter dragover', function (event) {
        event.preventDefault();
        event.stopPropagation();
        $dropZone.addClass('is-dragging');
    }).on('dragleave drop', function (event) {
        event.preventDefault();
        event.stopPropagation();
        $dropZone.removeClass('is-dragging');
    }).on('drop', function (event) {
        const files = event.originalEvent.dataTransfer.files;
        if (!files.length) return;
        const transfer = new DataTransfer();
        transfer.items.add(files[0]);
        $input[0].files = transfer.files;
        selectFile(files[0]);
    });

    $('#clearFile').on('click', function () {
        $input.val('');
        $('#selectedFile').addClass('d-none');
        $button.prop('disabled', true);
        resetMessages();
    });

    $form.on('submit', function (event) {
        event.preventDefault();
        const file = $input[0].files[0];
        const error = validateFile(file);
        if (error) return showErrors(error);

        resetMessages();
        $('#uploadProgress').removeClass('d-none');
        $('#progressBar').css('width', '0%');
        $('#progressValue').text('0%');
        $button.prop('disabled', true).find('.button-label').text('Uploading…');
        $button.find('.spinner-border').removeClass('d-none');

        $.ajax({
            url: $form.attr('action'),
            method: 'POST',
            data: new FormData(this),
            processData: false,
            contentType: false,
            headers: { 'Accept': 'application/json' },
            xhr: function () {
                const xhr = $.ajaxSettings.xhr();
                xhr.upload.addEventListener('progress', function (progress) {
                    if (!progress.lengthComputable) return;
                    const percent = Math.round((progress.loaded / progress.total) * 100);
                    $('#progressBar').css('width', percent + '%');
                    $('#progressValue').text(percent + '%');
                });
                return xhr;
            }
        }).done(function (response) {
            $('#progressBar').css('width', '100%');
            $('#progressValue').text('100%');
            $('#progressLabel').text('Stored successfully');
            $('#successMessage').html($('<div>').text(response.message).html() + ' <a href="' + response.manage_url + '">View files</a>');
            $('#uploadSuccess').removeClass('d-none');
            $input.val('');
            $('#selectedFile').addClass('d-none');
        }).fail(function (xhr) {
            const errors = xhr.responseJSON?.errors;
            showErrors(errors ? Object.values(errors).flat() : (xhr.responseJSON?.message || 'Upload failed. Please try again.'));
            $button.prop('disabled', false);
        }).always(function () {
            $button.find('.button-label').text('Upload securely');
            $button.find('.spinner-border').addClass('d-none');
        });
    });
});
</script>
@endpush
