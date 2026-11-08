<?php
declare(strict_types=1);
require __DIR__ . '/app/bootstrap.php';
require __DIR__ . '/app/customer.php';
require_login();

$raw = (string)($_GET['ids'] ?? '');
$ids = array_values(array_unique(array_filter(array_map('intval', explode(',', $raw)), static fn($v) => $v > 0)));
if (!$ids || count($ids) > 500) {
    http_response_code(400);
    exit('Selecione de 1 a 500 placas para imprimir.');
}
$marks = implode(',', array_fill(0, count($ids), '?'));
$stmt = db()->prepare("SELECT id, code, product_type, activation_code_encrypted FROM plaques WHERE id IN ($marks) ORDER BY id ASC");
$stmt->execute($ids);
$plaques = $stmt->fetchAll();
foreach ($plaques as &$plaque) {
    $plaque['activation_code'] = decrypt_app_secret($plaque['activation_code_encrypted'] ?? null);
    $plaque['product_type'] = in_array(($plaque['product_type'] ?? ''), ['google_review', 'whatsapp', 'instagram'], true) ? $plaque['product_type'] : 'google_review';
    $plaque['artwork'] = [
        'google_review' => 'assets/avaliacao_google.jpeg',
        'whatsapp' => 'assets/acesso_whatsapp.jpeg',
        'instagram' => 'assets/acesso_instagram.jpeg',
    ][$plaque['product_type']];
    $plaque['label'] = [
        'google_review' => 'Google Avaliações',
        'whatsapp' => 'WhatsApp',
        'instagram' => 'Instagram',
    ][$plaque['product_type']];
}
unset($plaque);
if (!$plaques) {
    http_response_code(404);
    exit('Nenhuma placa encontrada.');
}
?>
<!doctype html>
<html lang="pt-br">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Impressão de placas | Edtek Review</title>
<link rel="stylesheet" href="<?=e(app_url('assets/style.css'))?>">
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js" referrerpolicy="no-referrer"></script>
<style>
@page{size:A4;margin:8mm}
body{background:#eef3f8}
.print-toolbar{position:sticky;top:0;z-index:10;display:flex;justify-content:space-between;align-items:center;gap:12px;padding:12px 18px;background:#102b50;color:#fff;font:700 14px Arial,sans-serif}
.print-toolbar button{border:0;border-radius:7px;padding:10px 18px;background:#1680d5;color:#fff;font-weight:bold;cursor:pointer}
.print-sheet{display:flex;flex-wrap:wrap;gap:5mm;justify-content:center;padding:10mm}
.print-sheet .review-plaque.exact-template{flex:0 0 100mm;width:100mm;height:100mm;break-inside:avoid;page-break-inside:avoid}
@media print{
 body{background:#fff}
 .print-toolbar{display:none}
 .print-sheet{display:block;padding:0}
 .print-sheet .review-plaque.exact-template{margin:0 0 5mm;break-after:page;page-break-after:always;-webkit-print-color-adjust:exact;print-color-adjust:exact}
 .print-sheet .review-plaque.exact-template:last-child{break-after:auto;page-break-after:auto}
}
</style>
</head>
<body>
<div class="print-toolbar"><span>Edtek Review · <?=count($plaques)?> placa(s) · Frente 10 × 10 cm</span><button type="button" onclick="window.print()">Imprimir / Salvar PDF</button></div>
<div class="print-sheet">
<?php foreach ($plaques as $p): $qrUrl = app_url('r/' . $p['code']); ?>
  <article class="review-plaque exact-template product-<?=e($p['product_type'])?>">
    <img class="plaque-art" src="<?=e(app_url($p['artwork']))?>" alt="<?=e('Arte original da placa '.$p['label'])?>">
    <div class="qr-overlay" data-qr="<?=e($qrUrl)?>" aria-label="QR Code dinâmico individual"></div>
    <div class="plaque-identification">
      <strong><?=e($p['code'])?></strong>
      <span>ATIVAÇÃO: <?=e($p['activation_code'] ?: 'N/D')?></span>
    </div>
  </article>
<?php endforeach; ?>
</div>
<script>
(function(){
  const nodes=document.querySelectorAll('.qr-overlay');
  if(typeof QRCode==='undefined'){
    alert('Não foi possível carregar o gerador de QR Code. Verifique a internet e recarregue a página.');
    return;
  }
  nodes.forEach(node=>new QRCode(node,{
    text:node.dataset.qr,
    width:300,
    height:300,
    colorDark:'#000000',
    colorLight:'#ffffff',
    correctLevel:QRCode.CorrectLevel.H
  }));
})();
</script>
</body>
</html>
