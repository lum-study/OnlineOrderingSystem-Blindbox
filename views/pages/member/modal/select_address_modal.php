<div class="modal-overlay" id="select-address-modal">
    <div class="modal-content">
        <button class="modal-close">&times;</button>
        <h2>Select Address</h2>
        <?php foreach ($userAddresses as $addr): ?>
            <div class="address-item">
                <div class="address-header">
                    <strong><?= htmlspecialchars($addr->getReceiverName()) ?></strong>
                    <button type="button" class="address-select-btn" data-address="<?= htmlspecialchars(json_encode([
                        'address_id' => $addr->getAddressId(),
                        'receiver_name' => $addr->getReceiverName(),
                        'email' => $addr->getEmail(),
                        'phone_number' => $addr->getPhoneNumber(),
                        'address' => $addr->getAddress(),
                        'city' => $addr->getCity(),
                        'state' => $addr->getState(),
                        'postal_code' => $addr->getPostalCode(),
                        'country' => $addr->getCountry()
                    ]), ENT_QUOTES, 'UTF-8') ?>">Select</button>

                </div>
                <div class="address-details">
                    <p><?= htmlspecialchars($addr->getPhoneNumber()) ?></p>
                    <?php if ($addr->getEmail()): ?>
                        <p><?= htmlspecialchars($addr->getEmail()) ?></p>
                    <?php endif; ?>
                    <p><?= htmlspecialchars($addr->getAddress()) ?></p>
                    <p><?= htmlspecialchars($addr->getCity()) ?>, <?= htmlspecialchars($addr->getState()) ?>
                        <?= htmlspecialchars($addr->getPostalCode()) ?>
                    </p>
                    <p><?= htmlspecialchars($addr->getCountry()) ?></p>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if (!$userAddresses): ?>
            <div>
                Please add an address.
            </div>
        <?php endif; ?>
    </div>
</div>