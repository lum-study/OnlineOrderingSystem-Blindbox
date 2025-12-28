<?php
$profile = ProfileController::view();
$GLOBALS['fullname'] = $profile['fullname'];
$GLOBALS['email'] = $profile['email'];
$GLOBALS['contact_number'] = $profile['contact_number'] ?? '';
$GLOBALS['birth_date'] = $profile['birth_date'] ?? '';
$GLOBALS['gender'] = $profile['gender'] ?? '';
$GLOBALS['receiver_name'] = $profile['fullname'];
$GLOBALS['addr_email'] = $profile['email'];
?>
<div class="modal-overlay" id="add-address-modal" data-context="<?= $context ?? 'profile' ?>">
    <div class="modal-content profile-card">
        <button class="modal-close">&times;</button>
        <h2>Add Address</h2>
        <form action="<?= BASE_URL ?>profile/address/add" method="POST" class="profile-form" id="add-address-form">
            <?php html_hidden('csrf_token', $csrfToken); ?>

            <div class="form-group">
                <label>Receiver Full Name<span class="required">*</span></label>
                <?php html_text('receiver_name'); ?>
            </div>

            <div class="form-group">
                <label>Phone Number<span class="required">*</span></label>
                <?php html_phone('phone_number'); ?>
            </div>

            <div class="form-group">
                <label>Email Address<span class="required">*</span></label>
                <?php $GLOBALS['email'] = $GLOBALS['addr_email'];
                html_email('email'); ?>
                <small>Receive order confirmation email</small>
            </div>

            <div class="form-group">
                <label>Address<span class="required">*</span></label>
                <?php html_text('addr_address'); ?>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>City<span class="required">*</span></label>
                    <?php html_text('addr_city'); ?>
                </div>
                <div class="form-group">
                    <label>State<span class="required">*</span></label>
                    <?php html_text('addr_state'); ?>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Postal Code<span class="required">*</span></label>
                    <?php html_text('addr_postal_code'); ?>
                </div>
                <div class="form-group">
                    <label>Country<span class="required">*</span></label>
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
</div>