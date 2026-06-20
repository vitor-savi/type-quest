<?php
// Garante que o config esteja carregado (define WEB_BASE)
if (!defined('WEB_BASE')) {
    require_once __DIR__ . '/../config/config.php';
}
// Garante que as variáveis de página estejam definidas
$pageTitle  = $pageTitle  ?? 'TypeQuest';
$pageCss    = $pageCss    ?? [];
$bodyClass  = $bodyClass  ?? '';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> — TypeQuest</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700;900&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <!-- CSS global do projeto -->
    <link rel="stylesheet" href="<?= WEB_BASE ?>/assets/css/main.css">

    <!-- CSS específico da página -->
    <?php foreach ($pageCss as $css): ?>
    <link rel="stylesheet" href="<?= WEB_BASE . htmlspecialchars($css) ?>">
    <?php endforeach; ?>

    <!-- main.js carregado no <head> para estar disponível nos scripts inline das páginas -->
    <script src="<?= WEB_BASE ?>/assets/js/main.js"></script>
</head>
<body class="<?= htmlspecialchars($bodyClass) ?>">
