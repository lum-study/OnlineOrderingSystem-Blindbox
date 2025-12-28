<div class="modal-overlay flex flex-col" id="payment-modal">
    <div class="modal-content profile-card">
        <button class="modal-close">&times;</button>
        <h2>PAYMENT METHOD</h2>
        <div class="payment-methods">
            <div class="payment-method selected" data-method="card">
                <div class="payment-icon">💳</div>
                <div class="payment-details">
                    <div class="payment-title">Credit/Debit Card</div>
                    <div class="payment-desc">Pay securely with your card</div>
                </div>
            </div>

            <div class="payment-method" data-method="cash">
                <div class="payment-icon">💵</div>
                <div class="payment-details">
                    <div class="payment-title">Cash</div>
                    <div class="payment-desc">Cash on delivery</div>
                </div>
            </div>
        </div>

        <!-- Card Payment Form (shown when card is selected) -->
        <div id="card-form">
            <div class="form-group">
                <label class="form-label" for="card-number">Card Number</label>
                <div id="card-element" class="form-input"></div>
            </div>
        </div>

        <div class="btn-group">
            <button type="button" class="btn btn-prev" id="cancel-payment">Cancel</button>
            <button type="button" class="btn btn-next" id="confirm-payment">Confirm Payment</button>
        </div>
    </div>
</div>