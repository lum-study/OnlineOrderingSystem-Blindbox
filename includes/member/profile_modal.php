<!-- Change Username Modal -->

<?php
$canChange = true;
if ($profile['username_changed_at'] ?? null) {
    $lastChange = strtotime($profile['username_changed_at']);
    $daysSinceChange = (time() - $lastChange) / (60 * 60 * 24);
    if ($daysSinceChange < 30) {
        $canChange = false;
    }
}
?> <?php if ($canChange): ?>
    <div class="modal-overlay" id="username-modal">
        <div class="modal-content profile-card">
            <button class="modal-close">&times;</button>
            <h2>Change Username</h2>
            <form action="<?= BASE_URL ?>profile/change-username" method="POST" class="profile-form"
                id="change-username-form">
                <?php html_hidden('csrf_token', $csrfToken); ?>
                <div class="form-group">
                    <label>Current Username</label>
                    <input type="text" id="modal-current-username" value="<?= htmlspecialchars($profile['username']) ?>" disabled>
                </div>

                <div class="form-group">
                    <label>New Username</label>
                    <?php html_text('new_username'); ?>
                    <small>You can change username once every 30 days</small>
                </div>

                <button type="submit" class="user-btn">Change Username</button>
            </form>
        </div>
    </div>
<?php endif; ?>


<!-- Change Password Modal -->
<div class="modal-overlay" id="password-modal">
    <div class="modal-content profile-card">
        <button class="modal-close">&times;</button>
        <h2>Change Password</h2>
        <form action="<?= BASE_URL ?>profile/change-password" method="POST" class="profile-form"
            id="change-password-form">
            <?php html_hidden('csrf_token', $csrfToken); ?>

            <div class="form-group">
                <label>Old Password<span class="required">*</span></label>
                <?php html_password('old_password'); ?>
            </div>

            <div class="form-group">
                <label>New Password<span class="required">*</span></label>
                <?php html_password('new_password'); ?>
                <small>8+ chars with uppercase, lowercase, number, and special character</small>
            </div>

            <div class="form-group">
                <label>Confirm Password<span class="required">*</span></label>
                <?php html_password('confirm_password'); ?>
            </div>
            <button type="submit" class="user-btn">Change Password</button>
        </form>
    </div>
</div>

<!-- Add Address Modal
<div class="modal-overlay" id="add-address-modal">
    <div class="modal-content profile-card">
        <button class="modal-close">&times;</button>
        <h2>Add Address</h2>
        <form action="<?= BASE_URL ?>profile/address/add" method="POST" class="profile-form" id="add-address-form">
            <?php html_hidden('csrf_token', $csrfToken); ?>

            <div class="form-group">
                <label>Receiver Full Name</label>
                <?php html_text('receiver_name'); ?>
            </div>

            <div class="form-group">
                <label>Phone Number</label>
                <?php html_phone('phone_number'); ?>
            </div>

            <div class="form-group">
                <label>Email Address (Optional)</label>
                <?php $GLOBALS['email'] = $GLOBALS['addr_email'];
                html_email('email'); ?>
                <small>Receive order confirmation email</small>
            </div>

            <div class="form-group">
                <label>Address</label>
                <?php html_text('addr_address'); ?>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>City</label>
                    <?php html_text('addr_city'); ?>
                </div>
                <div class="form-group">
                    <label>State</label>
                    <?php html_text('addr_state'); ?>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Postal Code</label>
                    <?php html_text('addr_postal_code'); ?>
                </div>
                <div class="form-group">
                    <label>Country</label>
                    <?php html_select('addr_country', ['MY' => 'Malaysia', 'SG' => 'Singapore', 'ID' => 'Indonesia', 'TH' => 'Thailand'], '- Select Country -'); ?>
                </div>
            </div>

            <div class="form-group">
                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                    <input type="checkbox" name="is_default" value="1">
                    <span>Set as default address</span> </label>
            </div>

            <button type="submit" class="user-btn">Save Address</button>
        </form>
    </div>
</div> -->

<!-- Edit Address Modal -->
<div class="modal-overlay" id="edit-address-modal">
    <div class="modal-content profile-card">
        <button class="modal-close">&times;</button>
        <h2>Edit Address</h2>
        <form action="<?= BASE_URL ?>profile/address/edit" method="POST" class="profile-form"
            id="edit-address-form">
            <?php html_hidden('csrf_token', $csrfToken); ?>
            <input type="hidden" name="address_id" id="edit-address-id">

            <div class="form-group">
                <label>Receiver Full Name<span class="required">*</span></label>
                <input type="text" id="edit_receiver_name" name="receiver_name">
            </div>

            <div class="form-group">
                <label>Phone Number<span class="required">*</span></label>
                <div class="phone-input-group">
                    <select id="edit_phone_number_prefix" name="phone_number_prefix" class="phone-prefix">
                        <option value="+60">MY +60</option>
                        <option value="+65">SG +65</option>
                        <option value="+86">CN +86</option>
                    </select>
                    <input type="text" id="edit_phone_number" name="phone_number" class="phone-number">
                </div>
            </div>

            <div class="form-group">
                <label>Email Address<span class="required">*</span></label>
                <input type="email" id="edit_email" name="email">
            </div>

            <div class="form-group">
                <label>Address<span class="required">*</span></label>
                <input type="text" id="edit_addr_address" name="addr_address">
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>City<span class="required">*</span></label>
                    <input type="text" id="edit_addr_city" name="addr_city">
                </div>
                <div class="form-group">
                    <label>State<span class="required">*</span></label>
                    <input type="text" id="edit_addr_state" name="addr_state">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Postal Code<span class="required">*</span></label>
                    <input type="text" id="edit_addr_postal_code" name="addr_postal_code">
                </div>
                <div class="form-group">
                    <label>Country<span class="required">*</span></label>
                    <select id="edit_addr_country" name="addr_country">
                        <option value="MY">Malaysia</option>
                        <option value="SG">Singapore</option>
                        <option value="ID">Indonesia</option>
                        <option value="TH">Thailand</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                    <input type="checkbox" id="edit_is_default" name="is_default" value="1">
                    <span>Set as default address</span> </label>
            </div>

            <button type="submit" class="user-btn">Update Address</button>
        </form>
    </div>
</div>

<!-- Delete Address Confirmation Modal -->
<div class="modal-overlay" id="delete-address-modal">
    <div class="modal-content profile-card">
        <button class="modal-close">&times;</button>
        <h2>Delete Address</h2>
        <p>Are you sure you want to delete this address?</p>
        <form action="<?= BASE_URL ?>profile/address/delete" method="POST" id="delete-address-form">
            <?php html_hidden('csrf_token', $csrfToken); ?>
            <input type="hidden" name="address_id" id="delete-address-id">
            <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                <button type="submit" class="user-btn" style="background: #ef4444; border-color: #ef4444;">Delete</button>
                <button type="button" class="user-btn user-btn-secondary" onclick="closeDeleteAddressModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Account Modal -->

<div class="modal-overlay" id="delete-account-modal">
    <div class="modal-content profile-card">
        <button class="modal-close">&times;</button>
        <h2 style="color: #ef4444;">Delete Account</h2>
        <div class="warning-message">
            <p><strong>Warning: This action is permanent and cannot be undone!</strong></p>
            <p>Deleting your account will:</p>
            <ul>
                <li>Remove all your personal information</li>
                <li>Delete your order history</li>
                <li>Remove all saved addresses</li>
                <li>Cancel any pending orders</li>
            </ul>
        </div>
        <form action="<?= BASE_URL ?>profile/delete-account" method="POST" class="profile-form"
            id="delete-account-form">
            <?php html_hidden('csrf_token', $csrfToken); ?>
            <div class="form-group">
                <label>Enter Your Password to Confirm</label> <?php html_password('delete_password'); ?>
            </div>
            <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                <button type="submit" class="user-btn" style="background: #ef4444; border-color: #ef4444;">Delete My Account</button>
                <button type="button" class="user-btn user-btn-secondary" onclick="closeDeleteAccountModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- View Order Modal -->
<div class="modal-overlay" id="view-order-modal">
    <div class="modal-content profile-card">
        <button class="modal-close" onclick="closeViewOrderModal()">&times;</button>
        <h2>Order Details</h2>
        <div id="orderDetails"></div>
    </div>
</div>

<!-- Cancel Order Modal -->
<div class="modal-overlay" id="cancel-order-modal">
    <div class="modal-content profile-card">
        <button class="modal-close" onclick="closeCancelModal()">&times;</button>
        <h2 style="color: #ef4444;">Cancel Order</h2>
        <p>Are you sure you want to cancel this order?</p>
        <input type="hidden" id="cancel-order-id">
        <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
            <button type="button" onclick="confirmCancelOrder()" class="user-btn" style="background: #ef4444; border-color: #ef4444;">Yes, Cancel Order</button>
            <button type="button" class="user-btn user-btn-secondary" onclick="closeCancelModal()">No, Keep Order</button>
        </div>
    </div>
</div>

<!-- Complete Order Modal -->
<div class="modal-overlay" id="complete-order-modal">
    <div class="modal-content profile-card">
        <button class="modal-close" onclick="closeCompleteModal()">&times;</button>
        <h2 style="color: #10b981;">Complete Order</h2>
        <p>Confirm that your order has arrived?</p>
        <input type="hidden" id="complete-order-id">
        <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
            <button type="button" onclick="confirmCompleteOrder()" class="user-btn" style="background: #10b981; border-color: #10b981;">Yes, Order Received</button>
            <button type="button" class="user-btn user-btn-secondary" onclick="closeCompleteModal()">Cancel</button>
        </div>
    </div>
</div>