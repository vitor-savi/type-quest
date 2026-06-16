<?php
$pageJs = $pageJs ?? [];
if (!defined('WEB_BASE')) {
    require_once __DIR__ . '/../config/config.php';
}
?>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- JS global do projeto -->
    <script src="<?= WEB_BASE ?>/assets/js/main.js"></script>

    <!-- JS específico da página -->
    <?php foreach ($pageJs as $js): ?>
    <script src="<?= WEB_BASE . htmlspecialchars($js) ?>"></script>
    <?php endforeach; ?>
</body>
</html>
