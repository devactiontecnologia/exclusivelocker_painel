<?php

/**
 * Front controller na raiz do painel (URL sem /public).
 * Serve arquivos estáticos de /public e encaminha o resto ao Laravel.
 */

$publicDir = __DIR__ . DIRECTORY_SEPARATOR . 'public';

$uriPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
if ($scriptDir !== '/' && $scriptDir !== '' && $scriptDir !== '.') {
    if (str_starts_with($uriPath, $scriptDir)) {
        $uriPath = substr($uriPath, strlen($scriptDir)) ?: '/';
    }
}

$relative = rawurldecode(ltrim($uriPath, '/'));
if ($relative !== '' && !str_contains($relative, '..')) {
    $candidate = $publicDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
    if (is_file($candidate)) {
        $mime = mime_content_type($candidate) ?: 'application/octet-stream';
        // mime_content_type em Windows às vezes erra CSS/JS
        $ext = strtolower(pathinfo($candidate, PATHINFO_EXTENSION));
        $map = [
            'css' => 'text/css; charset=UTF-8',
            'js' => 'application/javascript; charset=UTF-8',
            'mjs' => 'application/javascript; charset=UTF-8',
            'json' => 'application/json; charset=UTF-8',
            'svg' => 'image/svg+xml',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'ico' => 'image/x-icon',
            'woff' => 'font/woff',
            'woff2' => 'font/woff2',
            'ttf' => 'font/ttf',
            'map' => 'application/json',
        ];
        if (isset($map[$ext])) {
            $mime = $map[$ext];
        }

        header('Content-Type: '.$mime);
        header('Content-Length: '.filesize($candidate));
        readfile($candidate);
        exit;
    }
}

require $publicDir . DIRECTORY_SEPARATOR . 'index.php';
