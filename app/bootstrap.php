<?php
declare(strict_types=1);

$configFile = dirname(__DIR__) . '/config.php';
if (!file_exists($configFile)) {
    http_response_code(500);
    exit('Configuração ausente. Copie config.example.php para config.php e configure o banco.');
}
$config = require $configFile;
date_default_timezone_set($config['timezone'] ?? 'America/Sao_Paulo');

$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
session_name($config['session_name'] ?? 'edtek_review_session');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => $isHttps,
    'httponly' => true,
    'samesite' => 'Lax',
]);
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

function db(): PDO {
    static $pdo = null;
    global $config;
    if ($pdo instanceof PDO) return $pdo;
    $d = $config['db'];
    $dsn = "mysql:host={$d['host']};dbname={$d['name']};charset=" . ($d['charset'] ?? 'utf8mb4');
    $pdo = new PDO($dsn, $d['user'], $d['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    return $pdo;
}
function e(?string $value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
function is_logged_in(): bool { return !empty($_SESSION['admin_id']); }
function require_login(): void {
    if (!is_logged_in()) { header('Location: login.php'); exit; }
}
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function verify_csrf(): void {
    $sent = $_POST['csrf'] ?? '';
    if (!is_string($sent) || !hash_equals(csrf_token(), $sent)) {
        http_response_code(419); exit('Sessão expirada. Atualize a página e tente novamente.');
    }
}
function app_url(string $path = ''): string {
    global $config;
    return rtrim($config['base_url'], '/') . '/' . ltrim($path, '/');
}
function flash(string $message, string $type = 'success'): void {
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
}
function get_flash(): ?array {
    $f = $_SESSION['flash'] ?? null; unset($_SESSION['flash']); return $f;
}
function valid_google_url(string $url): bool {
    $parts = parse_url($url);
    return filter_var($url, FILTER_VALIDATE_URL) !== false
        && isset($parts['scheme'], $parts['host'])
        && strtolower($parts['scheme']) === 'https'
        && (str_contains(strtolower($parts['host']), 'google.') || str_contains(strtolower($parts['host']), 'g.page'));
}
function layout_start(string $title): void {
    $flash = get_flash();
    $active = basename($_SERVER['PHP_SELF'] ?? 'dashboard.php');
    echo '<!doctype html><html lang="pt-br"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">';
    echo '<meta name="theme-color" content="#101d36"><title>' . e($title) . ' | Edtek Review</title><link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet"><link rel="stylesheet" href="assets/style.css"></head><body class="app-body">';
    if (is_logged_in()) {
        echo '<div class="app-layout"><aside class="sidebar" id="app-sidebar"><a href="dashboard.php" class="brand sidebar-brand"><img class="brand-logo" src="assets/logo%20edtek.png" alt="Edtek Soluções"><span class="brand-name">REVIEW<small>GESTÃO DE AVALIAÇÕES</small></span></a><div class="sidebar-label">MENU PRINCIPAL</div><nav class="sidebar-nav">';
        $items = [
            ['dashboard.php','Visão geral','◫'],
            ['companies.php','Clínicas','⌂'],
            ['plaques.php','Placas','▦'],
            ['plaque-batch.php','Gerar placas','＋'],
            ['generated-plaques.php','Placas geradas','▤'],
            ['projects.php','Projetos','▧'],
            ['resellers.php','Revendedores','♙'],
            ['email-settings.php','Configurar e-mail','✉'],
            ['https://wa.me/5521991928446','Suporte WhatsApp','?'],
        ];
        foreach ($items as [$href,$label,$icon]) {
            $isActive = $active === $href;
            echo '<a class="' . ($isActive ? 'active' : '') . '" href="' . $href . '"><span class="side-icon">' . $icon . '</span><span>' . $label . '</span>' . ($isActive ? '<i></i>' : '') . '</a>';
        }
        echo '</nav><div class="sidebar-bottom"><div class="sidebar-user"><span class="user-avatar">E</span><span><b>Administrador</b><small>Edtek Review</small></span></div><a class="sidebar-logout" href="logout.php">↪ <span>Sair do sistema</span></a></div></aside><div class="sidebar-scrim" data-sidebar-close></div><section class="app-main"><header class="app-topbar"><button class="nav-toggle" type="button" aria-label="Abrir menu" aria-expanded="false" data-sidebar-toggle><span></span><span></span><span></span></button><div class="topbar-title"><span>EDTEK REVIEW</span><b>' . e($title) . '</b></div><div class="topbar-right"><span class="online-dot"></span><span>Painel administrativo</span><span class="topbar-avatar">E</span></div></header><main class="wrap">';
    } else {
        echo '<header class="login-top"><a href="login.php" class="brand"><img class="brand-logo" src="assets/logo%20edtek.png" alt="Edtek Soluções"><span class="brand-name">REVIEW<small>GESTÃO DE AVALIAÇÕES</small></span></a></header><main class="wrap login-wrap">';
    }
    if ($flash) {
        $kind = ($flash['type'] ?? '') === 'error' ? 'error' : 'success';
        $symbol = $kind === 'error' ? '!' : '✓';
        echo '<div class="modal-backdrop is-visible" id="flash-modal" role="presentation"><section class="modal-card" role="dialog" aria-modal="true" aria-labelledby="flash-title"><div class="modal-symbol ' . $kind . '">' . $symbol . '</div><h2 id="flash-title">' . ($kind === 'error' ? 'Atenção' : 'Tudo certo!') . '</h2><p>' . e($flash['message']) . '</p><button type="button" class="button modal-close" data-modal-close>Entendi</button></section></div>';
    }
}
function layout_end(): void {
    if (is_logged_in()) echo '</main><footer class="app-footer">Edtek Review <span>·</span> Tecnologia para experiências melhores</footer></section></div>';
    else echo '</main><footer class="app-footer">Edtek Review <span>·</span> Tecnologia para experiências melhores</footer>';
    echo '<a class="support-float" href="https://wa.me/5521991928446?text=Ol%C3%A1%2C%20preciso%20de%20suporte%20no%20Edtek%20Review." target="_blank" rel="noopener" aria-label="Falar com o suporte pelo WhatsApp"><span class="support-float-icon">✆</span><span>Suporte</span></a>';
    echo '<script>document.addEventListener("keydown",function(e){if(e.key==="Escape"){document.body.classList.remove("sidebar-open");document.querySelectorAll(".modal-backdrop").forEach(function(m){m.remove()})}});document.querySelectorAll(".modal-backdrop").forEach(function(m){m.addEventListener("click",function(e){if(e.target===m)m.remove()})});document.querySelectorAll("[data-sidebar-toggle]").forEach(function(b){b.addEventListener("click",function(){document.body.classList.toggle("sidebar-open");b.setAttribute("aria-expanded",document.body.classList.contains("sidebar-open"))})});document.querySelectorAll("[data-sidebar-close]").forEach(function(b){b.addEventListener("click",function(){document.body.classList.remove("sidebar-open")})});document.querySelectorAll("[data-modal-close]").forEach(function(b){b.addEventListener("click",function(){var m=b.closest(".modal-backdrop");if(m)m.remove()})});</script></body></html>';
}
