<?php
use App\Core\Session;
Session::start();
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? '') ?><?= ($pageTitle ?? '') ? ' — ' : '' ?><?= htmlspecialchars(APP_NAME) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@500;600;700&family=Source+Sans+3:ital,wght@0,400;0,600;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bulma@1.0.2/css/bulma.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous">
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body class="has-navbar-fixed-top">

<?php include APP_ROOT . '/templates/layout/navbar.php'; ?>

<section class="section">
    <div class="container is-fluid">
        <?php include APP_ROOT . '/templates/layout/flash.php'; ?>
        <?= $content ?? '' ?>
    </div>
</section>

<script src="/assets/js/app.js" defer></script>
</body>
</html>
