<?php
if (!isset($pageTitle) || empty($pageTitle)) {
    $pageTitle = 'Yti School Hub';
}
$pageDescription = $pageDescription ?? 'Yti School Hub — Nowoczesna platforma edukacyjna. Przeglądaj notatki, prezentacje, lekcje i ucz się efektywniej.';
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0b0f19">
    <meta name="color-scheme" content="dark">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <meta name="description" content="<?= htmlspecialchars($pageDescription) ?>">
    <meta name="referrer" content="strict-origin-when-cross-origin">
    <?= csrfMetaTag() ?>
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%238b5cf6' stroke-width='2'><path d='M22 10v6M2 10l10-5 10 5-10 5z'/><path d='M6 12v5c0 2 2 3 6 3s6-1 6-3v-5'/></svg>">
    <link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <link rel="stylesheet" href="<?= htmlspecialchars(assetUrl('styleapp.css')) ?>">
    <script src="security.js"></script>
</head>
<body class="animate__animated animate__fadeIn">
