<?php

// ─── Serverless entry point for Vercel ───────────────────────────────────────
// Redireciona a requisição Serverless do Vercel para o coração do Laravel

$_SERVER['SCRIPT_FILENAME'] = dirname(__DIR__).'/public/index.php';
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['PHP_SELF'] = '/index.php';

// Force APP_URL for Vercel so asset URLs resolve correctly
if (!empty($_SERVER['HTTP_X_FORWARDED_HOST'])) {
    $_SERVER['HTTP_HOST'] = $_SERVER['HTTP_X_FORWARDED_HOST'];
}

require __DIR__ . '/../public/index.php';
