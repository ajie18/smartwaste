@extends('layouts.admin', ['active' => 'announcements', 'cssFile' => 'announcements'])

@section('title', 'Announcements')
@section('page-title', 'Announcements')

@section('content')

    <div class="panel reveal section-gap">
        <div class="panel-header">
            <div class="panel-title">
                <div class="panel-title-icon"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 20h9" />
                        <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z" />
                    </svg></div>Compose Announcement
            </div>
            <button class="btn btn-gold" onclick="createAnnouncement()">Publish <svg xmlns="http://www.w3.org/2000/svg"
                    width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="5" y1="12" x2="19" y2="12" />
                    <polyline points="12 5 19 12 12 19" />
                </svg></button>
        </div>
        <div class="panel-body">
            <div class="form-grid form-grid-3">
                <div class="form-group">
                    <label class="form-label">Title</label>
                    <input class="form-control" id="annTitle" placeholder="Announcement title…">
                </div>
                <div class="form-group">
                    <label class="form-label">Pickup Date (optional)</label>
                    <input class="form-control" type="date" id="annDate">
                </div>
                <div class="form-group">
                    <label class="form-label">Image (optional)</label>
                    <div class="image-upload-area">
                        <img id="imagePreview" class="image-upload-preview" alt="Preview">
                        <div class="image-upload-actions">
                            <button class="image-upload-btn" onclick="document.getElementById('imageInput').click()">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2">
                                    <path
                                        d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z" />
                                    <circle cx="12" cy="13" r="4" />
                                </svg> Upload Image
                            </button>
                            <button class="image-upload-btn remove" id="removeImageBtn" onclick="removeImage()"
                                style="display:none;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2">
                                    <line x1="18" y1="6" x2="6" y2="18" />
                                    <line x1="6" y1="6" x2="18" y2="18" />
                                </svg> Remove
                            </button>
                        </div>
                        <input type="file" id="imageInput" class="image-upload-input" accept="image/*"
                            onchange="handleImageUpload(event)">
                        <div class="image-upload-filename" id="fileNameDisplay"></div>
                        <input type="hidden" id="annImg" value="">
                    </div>
                </div>
                <div class="form-group" style="grid-column:1/-1">
                    <label class="form-label">Message</label>
                    <textarea class="form-control" id="annMsg" placeholder="Write your message to residents…" style="min-height:80px"></textarea>
                </div>
            </div>
        </div>
    </div>

    <div class="panel reveal">
        <div class="panel-header">
            <div class="panel-title">
                <div class="panel-title-icon"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 11l18-5v12L3 13v-2z" />
                        <path d="M11.6 16.8a3 3 0 1 1-5.8-1.6" />
                    </svg></div>Published Announcements
            </div>
            <span class="badge badge-gold" id="annCount">0</span>
        </div>
        <div style="padding:20px">
            <div class="ann-grid" id="annGrid">
                <div style="text-align:center;padding:28px;color:var(--text2);grid-column:1/-1">
                    <span class="loading-spinner"></span>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('extra-modals')
    <div class="modal-overlay" id="deleteConfirmModal">
        <div class="modal">
            <div class="modal-header">
                <div class="modal-title" style="color:var(--red);display:flex;align-items:center;gap:8px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="3 6 5 6 21 6" />
                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
                    </svg> Confirm Delete
                </div>
                <button class="modal-close" onclick="closeModal('deleteConfirmModal')">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18" />
                        <line x1="6" y1="6" x2="18" y2="18" />
                    </svg></button>
            </div>
            <div class="modal-body">
                <p id="deleteModalMessage">Are you sure you want to delete this announcement? This action cannot be undone.
                </p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline" onclick="closeModal('deleteConfirmModal')">Cancel</button>
                <button class="btn btn-danger" id="deleteConfirmBtn">Yes, Delete</button>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/admin/announcements.js') }}"></script>
@endpush
