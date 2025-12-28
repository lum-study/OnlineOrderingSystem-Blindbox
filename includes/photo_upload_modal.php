<!-- Webcam/Upload Selection Modal -->
<div class="modal-overlay" id="photo-source-modal">
    <div class="modal-content profile-card">
        <button class="modal-close" id="photo-source-modal-close">&times;</button>
        <h2>Upload Profile Photo</h2>
        <div style="display: flex; flex-direction: column; gap: 1rem; padding: 1rem 0;">
            <div id="webcam-container" style="position: relative; width: 100%; max-width: 400px; margin: 0 auto; display: none;">
                <video id="webcam-video" autoplay playsinline style="width: 100%; background: #000;"></video>
                <canvas id="webcam-canvas" style="display: none;"></canvas>
            </div>
            <div style="display: flex; gap: 1rem; justify-content: center;">
                <button type="button" class="user-btn" id="capture-webcam-btn" style="display: none;">
                    <svg style="width: 20px; height: 20px; margin-right: 8px; vertical-align: middle;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    Capture Photo
                </button>
                <button type="button" class="user-btn user-btn-secondary" id="select-file-btn">
                    <svg style="width: 20px; height: 20px; margin-right: 8px; vertical-align: middle;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    Select from Files
                </button>
            </div>
            <div style="text-align: center;">
                <small style="color: var(--accent-gray);">JPG, PNG, GIF - Max 2MB</small>
            </div>
        </div>
    </div>
</div>

<!-- Image Crop Modal -->
<div class="modal-overlay" id="crop-modal">
    <div class="modal-content crop-modal-content profile-card">
        <button class="modal-close" id="crop-modal-close">&times;</button>
        <h2>Adjust Photo</h2>
        <div class="crop-container" id="crop-container">
            <img src="" alt="Crop" id="crop-image" class="crop-image">
        </div>
        <div class="crop-controls">
            <p class="crop-instruction">Drag selection box to reposition • Drag corners to resize</p>
        </div>
        <div class="crop-actions">
            <button type="button" class="user-btn" id="crop-confirm-btn">Confirm</button>
            <button type="button" class="user-btn user-btn-secondary" id="crop-cancel-btn">Cancel</button>
        </div>
    </div>
</div>