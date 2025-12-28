<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Admin Panel' ?> | BlindeDoos</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/fonts.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/admin.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/orders.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/profile.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/toast.css">

    <script src="<?= BASE_URL ?>assets/js/lib/jquery-3.7.1.min.js"></script>
    <script src="<?= BASE_URL ?>assets/js/theme.js"></script>
    <script src="<?= BASE_URL ?>assets/js/admin_profile.js"></script>
    <script src="<?= BASE_URL ?>assets/js/toast.js"></script>
    <script src="<?= BASE_URL ?>assets/js/photo_upload.js"></script>
    <script src="<?= BASE_URL ?>assets/js/input.js"></script>

</head>

<body>
    <div class="admin-layout">
        <?php include __DIR__ . '/sidebar.php'; ?>
        <div class="admin-main">