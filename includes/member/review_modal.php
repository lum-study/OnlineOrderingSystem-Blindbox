<!-- Review Modal -->
<div id="review-modal" class="modal-overlay">
    <div class="modal-content" style="max-width: 600px;">
        <button type="button" class="modal-close" onclick="closeReviewModal()">&times;</button>
        <h2>Write a Review</h2>
        <form id="review-form" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            <input type="hidden" name="order_id" id="review-order-id">
            
            <div id="review-items-container"></div>
        </form>
    </div>
</div>
