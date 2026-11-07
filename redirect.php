<?php
declare(strict_types=1);
require __DIR__ . '/app/bootstrap.php';
$code = trim($_GET['code'] ?? '');
if (!preg_match('/^[A-Za-z0-9_-]{3,40}$/', $code)) { http_response_code(404); $state='not-found'; }
else {
    $stmt = db()->prepare("SELECT p.id,p.status,c.google_review_url,c.status company_status,c.name company_name FROM plaques p LEFT JOIN companies c ON c.id=p.company_id WHERE p.code=? LIMIT 1");
    $stmt->execute([$code]); $row = $stmt->fetch();
    if (!$row) { http_response_code(404); $state='not-found'; }
    elseif ($row['status'] !== 'active' || $row['company_status'] !== 'active' || empty($row['google_review_url'])) { http_response_code(200); $state='inactive'; }
    else {
        $source = ($_GET['src'] ?? '') === 'nfc' ? 'nfc' : (($_GET['src'] ?? '') === 'qr' ? 'qr' : 'unknown');
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $ipHash = $ip !== '' ? hash('sha256', $ip . 'edtek-review-salt') : null;
        $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500);
        $log = db()->prepare('INSERT INTO scans (plaque_id,source,ip_hash,user_agent) VALUES (?,?,?,?)');
        $log->execute([$row['id'], $source, $ipHash, $ua]);
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Location: ' . $row['google_review_url'], true, 302); exit;
    }
}
$title = $state === 'inactive' ? 'Esta placa ainda não foi ativada' : 'Não encontramos esta placa';
$subtitle = $state === 'inactive'
    ? 'A placa foi reconhecida, mas ainda não está vinculada a uma clínica. Peça ao responsável para concluir a ativação.'
    : 'O código informado não corresponde a uma placa cadastrada. Confira o material ou fale com quem forneceu a placa.';
?>
<!doctype html>
<html lang="pt-br">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<meta name="theme-color" content="#101d36"><title><?=e($title)?> | Edtek Review</title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?=e(app_url('assets/style.css'))?>">
</head>
<body class="public-state public-modal-page">
<div class="public-backdrop-glow" aria-hidden="true"></div>
<header class="public-top-brand"><img class="brand-logo" src="<?=e(app_url('assets/logo%20edtek.png'))?>" alt="Edtek Soluções"><span>REVIEW<small>TECNOLOGIA PARA AVALIAÇÕES</small></span></header>
<main class="public-modal-stage">
  <section class="public-modal-card" role="dialog" aria-modal="true" aria-labelledby="public-title">
    <div class="public-modal-symbol <?=$state==='inactive'?'warning':'error'?>"><span><?=$state==='inactive'?'!':'×'?></span></div>
    <span class="public-kicker">EDTEK REVIEW · SISTEMA DE AVALIAÇÕES</span>
    <h1 id="public-title"><?=e($title)?></h1>
    <p><?=e($subtitle)?></p>
    <?php if($state==='inactive'): ?>
      <div class="public-code-box"><span>Identificação da placa</span><strong><?=e($code)?></strong></div>
      <div class="public-hint"><span>i</span><span>Assim que o responsável ativar esta placa, o QR Code e o NFC funcionarão automaticamente.</span></div>
    <?php else: ?>
      <div class="public-hint"><span>i</span><span>Verifique se o QR Code pertence a uma placa Edtek Review.</span></div>
    <?php endif; ?>
    <a class="button public-modal-action" href="javascript:history.back()">Voltar</a>
  </section>
</main>
<footer class="public-modal-footer">Desenvolvido por <strong>Edtek Soluções em Informática</strong></footer>
<a class="support-float" href="https://wa.me/5521991928446" target="_blank" rel="noopener" aria-label="Falar com o suporte pelo WhatsApp"><span class="support-float-icon">✆</span><span>Suporte</span></a>
</body></html>
