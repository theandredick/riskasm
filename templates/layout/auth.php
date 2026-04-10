<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? '') ?><?= ($pageTitle ?? '') ? ' — ' : '' ?><?= htmlspecialchars(APP_NAME) ?></title>
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
                    <a href="/" class="has-text-dark">
                        <p class="title is-4 mb-1">
                            <span class="icon-text">
                                <span class="icon has-text-link"><i class="fas fa-shield-halved"></i></span>
                                <span><?= htmlspecialchars(APP_NAME) ?></span>
                            </span>
                        </p>
                        <p class="subtitle is-6 has-text-grey">Professional Risk Assessment</p>
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
