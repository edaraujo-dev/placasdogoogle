<?php
declare(strict_types=1);
require __DIR__ . '/app/bootstrap.php';
if (is_logged_in()) { header('Location: dashboard.php'); exit; }
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = strtolower(trim($_POST['email'] ?? ''));
    $stmt = db()->prepare('SELECT id,name,password_hash FROM admins WHERE email=? LIMIT 1');
    $stmt->execute([$email]); $admin = $stmt->fetch();
    if ($admin && password_verify($_POST['password'] ?? '', $admin['password_hash'])) {
        session_regenerate_id(true);
        unset($_SESSION['customer_id'],$_SESSION['reseller_id']);
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_name'] = $admin['name'];
        header('Location: dashboard.php'); exit;
    }
    $error = 'E-mail ou senha inválidos.';
}
layout_start('Entrar');
?><section class="narrow"><h1>Acessar painel</h1><?php if($error): ?><div class="notice error"><?=e($error)?></div><?php endif; ?><form method="post" class="panel"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><label>E-mail<input type="email" name="email" required autocomplete="username"></label><label>Senha<input type="password" name="password" required autocomplete="current-password"></label><button>Entrar</button></form><p><a href="forgot-password.php?type=admin">Esqueci minha senha</a></p><p>É cliente? <a href="customer-login.php">Acessar minha conta</a> · <a href="customer-register.php">Criar conta</a></p><p>Revendedor? <a href="reseller-login.php">Entrar no portal</a></p></section><?php layout_end(); ?>
