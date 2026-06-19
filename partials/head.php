<?php
if (!isset($pageTitle) || empty($pageTitle)) {
    $pageTitle = 'Yti School Hub';
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <meta http-equiv="Content-Security-Policy" content="default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; connect-src 'self'; font-src 'self'; frame-ancestors 'none'; base-uri 'self';">
    <meta name="referrer" content="strict-origin-when-cross-origin">
    <?= csrfMetaTag() ?>
    <link rel="stylesheet" href="<?= htmlspecialchars(assetUrl('styleapp.css')) ?>">
    <script src="security.js"></script>
</head>
<body>
