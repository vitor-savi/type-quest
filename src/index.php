<?php
/**
 * TypeQuest — Ponto de entrada principal
 * Redireciona para dashboard se autenticado, ou para login se não autenticado.
 */
require_once __DIR__ . '/config/config.php';

session_name(SESSION_NAME);
session_start();

if (isset($_SESSION['usuario'])) {
    header('Location: /pages/dashboard.php');
} else {
    header('Location: /pages/auth/login.php');
}
exit;
