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
<body class="auth-layout">

<section class="section">
    <div class="container">
        <div class="columns is-centered">
            <div class="column is-10-mobile is-8-tablet is-5-desktop is-4-widescreen">

                <div class="has-text-centered mb-5">
                    <a href="/" class="brand-link">
                        <p class="brand-name mb-1">
                            <span class="brand-icon"><i class="fas fa-shield-halved"></i></span>
                            <span><?= htmlspecialchars(APP_NAME) ?></span>
                        </p>
                        <p class="brand-tagline">Professional Risk Assessment</p>
                    </a>
                </div>

                <?php include APP_ROOT . '/templates/layout/flash.php'; ?>

                <?= $content ?? '' ?>

            </div>
        </div>
    </div>
</section>

<script src="/assets/js/app.js" type="module"></script>
</body>
</html>
