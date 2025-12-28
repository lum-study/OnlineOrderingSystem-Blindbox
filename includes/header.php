<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'BlindeDoos | The Unknown' ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/fonts.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/header.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/cart_view.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/checkout.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/profile.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/toast.css">

    <script src="<?= BASE_URL ?>assets/js/lib/jquery-3.7.1.min.js"></script>
    <script src="https://js.stripe.com/v3/"></script>

    <?php
    // Determine saved theme from DB for logged-in member (default to null)
    $serverTheme = null;
    if (Auth::check()) {
        $uid = Session::getMember('user_id') ?? Session::get('user_id') ?? null;
        if ($uid) {
            require_once __DIR__ . '/../models/UserData.php';
            $ud = new UserData();
            $prefs = $ud->getPreferencesDecoded($uid);
            if (is_array($prefs) && isset($prefs['theme'])) {
                $serverTheme = $prefs['theme'];
            }
        }
    }
    ?>

    <script>
        window.APP = window.APP || {};
        window.APP.BASE_URL = '<?= BASE_URL ?>';
        window.APP.IS_LOGGED_IN = <?= Auth::check() ? 'true' : 'false' ?>;
        window.APP.CSRF_TOKEN = '<?= Security::generateCSRF() ?>';
        window.APP.SAVED_USER_THEME = <?= json_encode($serverTheme) ?>;
    </script>

    <script src="<?= BASE_URL ?>assets/js/theme.js"></script>
    <script src="<?= BASE_URL ?>assets/js/payment.js"></script>
    <script src="<?= BASE_URL ?>assets/js/header.js" defer></script>
    <script src="<?= BASE_URL ?>assets/js/cart.js"></script>
    <script src="<?= BASE_URL ?>assets/js/checkout.js"></script>
    <script src="<?= BASE_URL ?>assets/js/profile.js"></script>
    <script src="<?= BASE_URL ?>assets/js/toast.js"></script>
    <script src="<?= BASE_URL ?>assets/js/photo_upload.js"></script>
    <script src="<?= BASE_URL ?>assets/js/input.js"></script>
</head>

<body>
    <?php include __DIR__ . '/navbar.php'; ?>
    <!-- Page content starts here -->
    <div class="page-content">