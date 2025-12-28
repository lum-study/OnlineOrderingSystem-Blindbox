<?php
require_once __DIR__ . '/../../../config/init.php';
require_once __DIR__ . '/../../../includes/auth_required.php';
require_once __DIR__ . '/../../../controllers/ProfileController.php';
require_once __DIR__ . '/../../../controllers/OrderController.php';
require_once __DIR__ . '/../../../controllers/ReviewController.php';

$profile = ProfileController::view();
$addresses = ProfileController::getAddresses();

// Handle order AJAX requests
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    try {
        $userId = Session::get('user_id');
        $orderController = new OrderController();

        if ($_GET['action'] === 'get_order' && isset($_GET['order_id'])) {
            $order = $orderController->getUserOrderDetails($_GET['order_id'], $userId);
            if ($order) {
                echo json_encode(['success' => true, 'order' => $order]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Order not found']);
            }
            exit;
        }

        if ($_GET['action'] === 'complete_order' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Security::verifyCSRF($_POST['csrf_token'] ?? '')) {
                echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
                exit;
            }
            $result = $orderController->completeOrder($_POST['order_id'], $userId);
            echo json_encode($result);
            exit;
        }

        if ($_GET['action'] === 'cancel_order' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Security::verifyCSRF($_POST['csrf_token'] ?? '')) {
                echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
                exit;
            }
            $result = $orderController->cancelOrder($_POST['order_id'], $userId);
            echo json_encode($result);
            exit;
        }

        if ($_GET['action'] === 'get_order_items' && isset($_GET['order_id'])) {
            $items = ReviewController::getOrderItemsForReview($_GET['order_id'], $userId);
            echo json_encode(['success' => true, 'items' => $items]);
            exit;
        }

        if ($_GET['action'] === 'submit_review' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Security::verifyCSRF($_POST['csrf_token'] ?? '')) {
                echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
                exit;
            }
            $result = ReviewController::createReview(
                $_POST['order_id'] ?? '',
                $_POST['order_item_id'] ?? '',
                $userId,
                $_POST['blindbox_id'] ?? '',
                $_POST['rating'] ?? '',
                $_POST['comment'] ?? '',
                $_FILES ?? []
            );
            echo json_encode($result);
            exit;
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}

// Get user orders
$orderController = new OrderController();
$orders = $orderController->getUserOrders(Session::get('user_id'));
$csrfToken = Security::generateCSRF();
$message = $_GET['msg'] ?? '';
$messageType = $_GET['type'] ?? '';
$activeSection = $_GET['section'] ?? 'my-orders';

// Set GLOBALS for HTML helpers
$GLOBALS['fullname'] = $profile['fullname'];
$GLOBALS['email'] = $profile['email'];
$GLOBALS['contact_number'] = $profile['contact_number'] ?? '';
$GLOBALS['birth_date'] = $profile['birth_date'] ?? '';
$GLOBALS['gender'] = $profile['gender'] ?? '';
$GLOBALS['new_username'] = '';
$GLOBALS['old_password'] = '';
$GLOBALS['new_password'] = '';
$GLOBALS['confirm_password'] = '';
$GLOBALS['receiver_name'] = $profile['fullname'];
$GLOBALS['phone_number'] = '';
$GLOBALS['current_username'] = htmlspecialchars($profile['username']);
$last_login_value = $profile['last_login'] ? date('Y-m-d H:i', strtotime($profile['last_login'])) : 'Never';
$GLOBALS['last_login'] = $last_login_value;

$pageTitle = 'Profile';
$favIcon = file_get_contents(__DIR__ . "/../../../assets/images/icons/fa-heart.svg");
?>
<!DOCTYPE html>
<html lang="en" class="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/fonts.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/profile.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/review.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/toast.css">
</head>

<body>
    <?php include __DIR__ . '/../../../includes/header.php'; ?>
    <div class="container" style="padding: 100px 24px 40px;">
        <h1>My Profile</h1>

        <?php if ($message): ?>
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    showToast('<?= addslashes(htmlspecialchars($message)) ?>', '<?= $messageType === 'success' ? 'success' : 'error' ?>');
                });
            </script>
        <?php endif; ?>

        <div class="profile-layout" id="profile-layout">
            <!-- Sidebar -->
            <aside class="profile-sidebar" id="profile-sidebar">
                <div class="profile-user-card">
                    <?php if ($profile['profile_photo']): ?>
                        <img src="<?= BASE_URL ?>assets/images/uploads/profile/<?= htmlspecialchars($profile['profile_photo']) ?>?t=<?= time() ?>"
                            alt="Profile" class="profile-avatar">
                    <?php else: ?>
                        <div class="profile-avatar-placeholder">?</div>
                    <?php endif; ?>
                    <div class="profile-user-name"><?= htmlspecialchars($profile['fullname']) ?></div>
                    <div class="profile-user-role">MEMBER</div>
                </div>
                <nav class="profile-nav">
                    <a href="#" class="profile-nav-item <?= $activeSection === 'my-orders' ? 'active' : '' ?>"
                        data-section="my-orders">
                        <svg viewBox="0 0 24 24">
                            <path
                                d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V5h14v14zm-7-2h2v-2h2v-2h-2v-2h-2v2H9v2h2z" />
                        </svg>
                        My Orders
                    </a>
                    <a href="#" class="profile-nav-item <?= $activeSection === 'edit-profile' ? 'active' : '' ?>"
                        data-section="edit-profile">
                        <svg viewBox="0 0 24 24">
                            <path
                                d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z" />
                        </svg>
                        Edit Profile
                    </a>
                    <a href="#" class="profile-nav-item <?= $activeSection === 'address-book' ? 'active' : '' ?>"
                        data-section="address-book">
                        <svg viewBox="0 0 24 24">
                            <path
                                d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z" />
                        </svg>
                        Address Book
                    </a>
                    <a href="#" class="profile-nav-item <?= $activeSection === 'wishlist' ? 'active' : '' ?>"
                        data-section="wishlist">
                        <?= $favIcon ?>
                        Wishlist
                    </a>
                </nav>
            </aside>

            <!-- Content -->
            <div class="profile-content">
                <!-- Edit Profile Section -->
                <div class="profile-section <?= $activeSection === 'edit-profile' ? 'active' : '' ?>" id="edit-profile">
                    <!-- Profile Photo -->
                    <div class="profile-card">
                        <h2>Profile Photo</h2>
                        <form action="<?= BASE_URL ?>profile/upload-photo" method="POST" enctype="multipart/form-data"
                            id="photo-upload-form">
                            <?php html_hidden('csrf_token', $csrfToken); ?>
                            <input type="hidden" name="action" value="upload_photo">
                            <input type="hidden" name="cropped_image" id="cropped-image-data">
                            <div class="photo-upload-container">
                                <div class="photo-upload-frame" id="photo-upload-frame">
                                    <?php if ($profile['profile_photo']): ?>
                                        <img src="<?= BASE_URL ?>assets/images/uploads/profile/<?= htmlspecialchars($profile['profile_photo']) ?>?t=<?= time() ?>"
                                            alt="Profile" id="photo-preview">
                                    <?php else: ?>
                                        <div class="photo-upload-placeholder">
                                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                                xmlns="http://www.w3.org/2000/svg">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M12 4v16m8-8H4"></path>
                                            </svg>
                                            <div>Click to upload</div>
                                        </div>
                                    <?php endif; ?>
                                    <div class="photo-upload-overlay">
                                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        <span>Upload Picture</span>
                                    </div>
                                </div>
                                <?php html_file('profile_photo', 'photo-file-input', 'accept="image/jpeg,image/png,image/gif"'); ?>
                                <small>JPG, PNG, GIF - Max 2MB</small>
                                <button type="submit" class="user-btn" id="upload-photo-btn">Upload Photo</button>
                            </div>
                        </form>
                    </div> <!-- Profile Information -->
                    <div class="profile-card">
                        <h2>Profile Information</h2>
                        <form action="<?= BASE_URL ?>profile/update" method="POST" class="profile-form"
                            id="update-profile-form">
                            <?php html_hidden('csrf_token', $csrfToken); ?>
                            <div class="form-group">
                                <label>Username</label>
                                <div class="username-flex">
                                    <?php html_text('current_username', 'id="current-username-display" disabled'); ?>

                                    <?php
                                    $canChange = true;
                                    $daysLeft = 0;
                                    if ($profile['username_changed_at'] ?? null) {
                                        $lastChange = strtotime($profile['username_changed_at']);
                                        $daysSinceChange = (time() - $lastChange) / (60 * 60 * 24);
                                        if ($daysSinceChange < 30) {
                                            $canChange = false;
                                            $daysLeft = ceil(30 - $daysSinceChange);
                                        }
                                    }
                                    ?>
                                    <?php if ($canChange): ?>
                                        <a href="#" id="open-username-modal" class="username-change-link">Change</a>
                                    <?php else: ?>
                                        <small>(<?= $daysLeft ?> days)</small>
                                    <?php endif; ?>

                                </div>
                            </div>

                            <div class="form-group">
                                <label>Full Name<span class="required">*</span></label>
                                <?php html_text('fullname'); ?>
                            </div>

                            <div class="form-group">
                                <label>Contact Number</label>
                                <?php html_phone('contact_number'); ?>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label>Birth Date</label>
                                    <?php html_date('birth_date'); ?>
                                </div>

                                <div class="form-group">
                                    <label>Gender</label>
                                    <?php html_select('gender', ['male' => 'Male', 'female' => 'Female', 'other' => 'Other'], '- Prefer not to say -'); ?>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Email</label>
                                <?php
                                html_email('email', 'disabled');
                                echo '<small style="color: var(--accent-gray);">✓ Email verified and cannot be changed</small>';
                                ?>
                            </div>

                            <div class="form-group">
                                <label>Last Login</label>
                                <?php html_text('last_login', 'disabled'); ?>
                            </div><button type="submit" class="user-btn">Update Profile</button>
                            <button type="button" id="open-password-modal" class="user-btn"
                                style="background: #ef4444; border-color: #ef4444;">Change Password</button>

                            <button type="button" id="open-delete-account-modal" class="user-btn"
                                style="background: #ef4444; border-color: #ef4444; margin-top: 1rem;">Delete
                                Account</button>

                        </form>
                    </div>
                </div>

                <!-- My Orders Section -->
                <div class="profile-section <?= $activeSection === 'my-orders' ? 'active' : '' ?>" id="my-orders">
                    <div class="profile-card">
                        <h2>My Orders</h2>
                        <?php if (empty($orders)): ?>
                            <p class="empty-state">No orders yet</p>
                        <?php else: ?>
                            <?php foreach ($orders as $order): ?>
                                <?php
                                $toPay = $order['status'] == 'to_pay';
                                $onclick = $toPay
                                    ? 'onclick="payNowOnClick(\'' . htmlspecialchars($order['order_id'], ENT_QUOTES) . '\')"'
                                    : '';
                                ?>

                                <div class="order-item">
                                    <div class="order-header">
                                        <div>
                                            <strong><?= htmlspecialchars($order['order_id']) ?></strong>
                                            <span class="order-date"><?= date('M d, Y', strtotime($order['created_date'])) ?></span>
                                        </div>
                                        <span <?= $onclick ?> data-order-id="<?= htmlspecialchars($order['order_id']) ?>"
                                            class="order-status status-<?= $order['status'] ?>"><?= ucfirst(str_replace('_', ' ', $order['status'])) ?></span>
                                    </div>
                                    <div class="order-body">
                                        <div><strong>Items:</strong> <?= $order['item_count'] ?></div>
                                        <div><strong>Total:</strong> RM <?= number_format($order['total_amount'], 2) ?></div>
                                    </div>
                                    <div class="order-actions">
                                        <button type="button" onclick="viewOrder('<?= $order['order_id'] ?>')"
                                            class="user-btn user-btn-small">View</button>
                                        <?php if ($order['status'] === 'completed'): ?>
                                            <?php if ($order['review_count'] > 0): ?>
                                                <button type="button" class="user-btn user-btn-small" style="background:#9ca3af;border-color:#9ca3af;cursor:not-allowed;" disabled>Reviewed</button>
                                            <?php else: ?>
                                                <button type="button" onclick="openReviewModal('<?= $order['order_id'] ?>')" class="user-btn user-btn-small" style="background:#f59e0b;border-color:#f59e0b;">Review</button>
                                            <?php endif; ?>
                                        <?php elseif ($order['status'] === 'shipped'): ?>
                                            <button type="button" onclick="completeOrder('<?= $order['order_id'] ?>')"
                                                class="user-btn user-btn-small">Complete Order</button>
                                        <?php elseif ($order['status'] === 'paid' || $order['status'] === 'pending'): ?>
                                            <button type="button" onclick="cancelOrder('<?= $order['order_id'] ?>')"
                                                class="user-btn user-btn-small"
                                                style="background:#ef4444;border-color:#ef4444;">Cancel</button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Address Book Section -->
                <div class="profile-section <?= $activeSection === 'address-book' ? 'active' : '' ?>" id="address-book">
                    <div class="profile-card">
                        <div
                            style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                            <h2 style="margin: 0;">Address Book</h2>
                            <button type="button" id="open-address-modal" class="user-btn"
                                style="padding: 0.5rem 1.5rem; font-size: 1rem;">Add Address</button>
                        </div>
                        <?php if (empty($addresses)): ?>
                            <p class="empty-state">No addresses saved</p>
                        <?php else: ?>
                            <div class="address-list">
                                <?php foreach ($addresses as $addr): ?>
                                    <div class="address-item">
                                        <div class="address-header">
                                            <strong><?= htmlspecialchars($addr->getReceiverName()) ?></strong>
                                            <?php if ($addr->getIsDefault()): ?>
                                                <span class="address-badge">Default</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="address-details">
                                            <p><?= htmlspecialchars($addr->getPhoneNumber()) ?></p>
                                            <?php if ($addr->getEmail()): ?>
                                                <p><?= htmlspecialchars($addr->getEmail()) ?></p>
                                            <?php endif; ?>
                                            <p><?= htmlspecialchars($addr->getAddress()) ?></p>
                                            <p><?= htmlspecialchars($addr->getCity()) ?>,
                                                <?= htmlspecialchars($addr->getState()) ?>
                                                <?= htmlspecialchars($addr->getPostalCode()) ?>
                                            </p>
                                            <p><?= htmlspecialchars($addr->getCountry()) ?></p>
                                        </div>
                                        <div style="display: flex; gap: 0.5rem; margin-top: 0.5rem;">
                                            <?php if (!$addr->getIsDefault()): ?>
                                                <button type="button" class="address-default-btn"
                                                    data-address-id="<?= $addr->getAddressId() ?>"
                                                    onclick="setDefaultAddress(this)">Set Default</button>
                                            <?php endif; ?>
                                            <button type="button" class="address-default-btn"
                                                data-address-id="<?= $addr->getAddressId() ?>"
                                                data-receiver="<?= htmlspecialchars($addr->getReceiverName()) ?>"
                                                data-phone="<?= htmlspecialchars($addr->getPhoneNumber()) ?>"
                                                data-email="<?= htmlspecialchars($addr->getEmail()) ?>"
                                                data-address="<?= htmlspecialchars($addr->getAddress()) ?>"
                                                data-city="<?= htmlspecialchars($addr->getCity()) ?>"
                                                data-state="<?= htmlspecialchars($addr->getState()) ?>"
                                                data-postal="<?= htmlspecialchars($addr->getPostalCode()) ?>"
                                                data-country="<?= htmlspecialchars($addr->getCountry()) ?>"
                                                data-default="<?= $addr->getIsDefault() ?>"
                                                onclick="openEditAddressModal(this)">Edit</button>
                                            <button type="button" class="address-delete-btn"
                                                data-address-id="<?= $addr->getAddressId() ?>"
                                                onclick="openDeleteAddressModal(this)">Delete</button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div> <?php endif; ?>
                    </div>
                </div>

                <?php include_once __DIR__ . "/../../../includes/member/wishlist_section.php" ?>
            </div>
        </div>

        <?php include __DIR__ . '/../../../includes/member/profile_modal.php'; ?>
        <?php include __DIR__ . '/../../../includes/member/review_modal.php'; ?>
        <?php include __DIR__ . '/../../../includes/photo_upload_modal.php'; ?>
        <?php include __DIR__ . '/modal/payment_modal.php'; ?>
        <?php include __DIR__ . '/modal/add_address_modal.php'; ?>

        <script>
            function viewOrder(orderId) {
                console.log('Fetching order:', orderId);
                fetch('<?= BASE_URL ?>views/pages/member/profile.php?action=get_order&order_id=' + orderId)
                    .then(response => response.json())
                    .then(data => {
                        console.log('Order data:', data);
                        if (data.success && data.order) {
                            displayOrderDetails(data.order);
                            const modal = document.getElementById('view-order-modal');
                            if (modal) {
                                modal.classList.add('active');
                            }
                        } else {
                            showToast(data.message || 'Failed to load order details', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showToast('Error loading order details', 'error');
                    });
            }

            function displayOrderDetails(order) {
                let html = '<div style="display:flex;flex-direction:column;gap:1rem;">';
                html += '<div><strong>Order ID:</strong> ' + order.order_id + '</div>';
                html += '<div><strong>Status:</strong> ' + order.status.toUpperCase() + '</div>';
                html += '<div><strong>Date:</strong> ' + new Date(order.created_date).toLocaleDateString() + '</div>';

                if (order.items && order.items.length > 0) {
                    html += '<div style="margin-top:1rem;"><strong>Items:</strong></div>';
                    html += '<table style="width:100%;border-collapse:collapse;">';
                    html += '<tr style="border-bottom:1px solid var(--border-color);"><th style="text-align:left;padding:0.5rem;">Item</th><th style="text-align:right;padding:0.5rem;">Qty</th><th style="text-align:right;padding:0.5rem;">Price</th></tr>';

                    order.items.forEach(item => {
                        html += '<tr style="border-bottom:1px solid var(--border-dim);">';
                        html += '<td style="padding:0.5rem;">' + (item.product_name || item.blindbox_id) + '</td>';
                        html += '<td style="text-align:right;padding:0.5rem;">' + item.quantity + '</td>';
                        html += '<td style="text-align:right;padding:0.5rem;">RM ' + parseFloat(item.price).toFixed(2) + '</td>';
                        html += '</tr>';
                    });
                    html += '</table>';
                }

                html += '<div style="margin-top:1rem;padding-top:1rem;border-top:1px solid var(--border-color);">';
                html += '<div style="display:flex;justify-content:space-between;"><span>Subtotal:</span><span>RM ' + parseFloat(order.sub_total_amount).toFixed(2) + '</span></div>';
                html += '<div style="display:flex;justify-content:space-between;"><span>Tax:</span><span>RM ' + parseFloat(order.tax_amount).toFixed(2) + '</span></div>';
                html += '<div style="display:flex;justify-content:space-between;font-weight:700;font-size:1.1rem;margin-top:0.5rem;"><span>Total:</span><span>RM ' + parseFloat(order.total_amount).toFixed(2) + '</span></div>';
                html += '</div></div>';

                document.getElementById('orderDetails').innerHTML = html;
            }

            function closeViewOrderModal() {
                document.getElementById('view-order-modal').classList.remove('active');
            }

            function completeOrder(orderId) {
                document.getElementById('complete-order-id').value = orderId;
                document.getElementById('complete-order-modal').classList.add('active');
            }

            function closeCompleteModal() {
                document.getElementById('complete-order-modal').classList.remove('active');
            }

            function confirmCompleteOrder() {
                const orderId = document.getElementById('complete-order-id').value;
                closeCompleteModal();

                fetch('<?= BASE_URL ?>views/pages/member/profile.php?action=complete_order', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'order_id=' + orderId + '&csrf_token=<?= $csrfToken ?>'
                })
                    .then(response => response.json())
                    .then(data => {
                        showToast(data.message || (data.success ? 'Order completed successfully' : 'Error completing order'), data.success ? 'success' : 'error');
                        if (data.success) setTimeout(() => location.reload(), 800);
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showToast('Error completing order', 'error');
                    });
            }

            function cancelOrder(orderId) {
                document.getElementById('cancel-order-id').value = orderId;
                document.getElementById('cancel-order-modal').classList.add('active');
            }

            function closeCancelModal() {
                document.getElementById('cancel-order-modal').classList.remove('active');
            }

            function confirmCancelOrder() {
                const orderId = document.getElementById('cancel-order-id').value;
                closeCancelModal();

                fetch('<?= BASE_URL ?>views/pages/member/profile.php?action=cancel_order', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'order_id=' + orderId + '&csrf_token=<?= $csrfToken ?>'
                })
                    .then(response => response.json())
                    .then(data => {
                        showToast(data.message || (data.success ? 'Order cancelled successfully' : 'Error cancelling order'), data.success ? 'success' : 'error');
                        if (data.success) setTimeout(() => location.reload(), 800);
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showToast('Error cancelling order', 'error');
                    });
            }

            // Modal close handlers
            document.addEventListener('DOMContentLoaded', function () {
                const viewOrderModal = document.getElementById('view-order-modal');
                if (viewOrderModal) {
                    const closeBtn = viewOrderModal.querySelector('.modal-close');
                    if (closeBtn) {
                        closeBtn.addEventListener('click', closeViewOrderModal);
                    }
                    viewOrderModal.addEventListener('click', function (e) {
                        if (e.target === viewOrderModal) closeViewOrderModal();
                    });
                }
            });
        </script>
        <script src="<?= BASE_URL ?>assets/js/review.js"></script>
        <?php include __DIR__ . '/../../../includes/footer.php'; ?>
</body>

</html>