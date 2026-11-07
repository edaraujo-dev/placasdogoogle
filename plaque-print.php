<?php
declare(strict_types=1);
require __DIR__ . '/app/bootstrap.php';
require_login();

$raw = (string)($_GET['ids'] ?? '');
$ids = array_values(array_unique(array_filter(array_map('intval', explode(',', $raw)), static fn($v) => $v > 0)));
if (!$ids || count($ids) > 500) {
    http_response_code(400);
    exit('Selecione de 1 a 500 placas para imprimir.');
}
$marks = implode(',', array_fill(0, count($ids), '?'));
$stmt = db()->prepare("SELECT id, code, activation_code FROM plaques WHERE id IN ($marks) ORDER BY id ASC");
$stmt->execute($ids);
$plaques = $stmt->fetchAll();
if (!$plaques) { http_response_code(404); exit('Nenhuma placa encontrada.'); }
$base = rtrim($config['base_url'], '/');
?><!doctype html>
<html lang="pt-br"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Impressão de placas | Edtek Review</title>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<style>
@page{size:A4;margin:8mm}
*{box-sizing:border-box}
body{margin:0;font-family:Arial,sans-serif;color:#16345a;background:#eef3f8}
.toolbar{position:sticky;top:0;background:#102b50;color:#fff;padding:12px 18px;display:flex;justify-content:space-between;align-items:center;z-index:2}
.toolbar button{border:0;border-radius:7px;padding:10px 18px;background:#1680d5;color:#fff;font-weight:bold;cursor:pointer}
.sheet{display:grid;grid-template-columns:100mm;gap:5mm;justify-content:center;justify-items:center;padding:10mm}
.plaque{width:100mm;height:100mm;background:#fff;border:1px solid #d8e5f2;border-radius:3mm;overflow:hidden;position:relative;text-align:center;padding:7mm 6mm 5mm;break-inside:avoid;page-break-inside:avoid}
.brand{font-weight:800;font-size:17pt;letter-spacing:1.5px;color:#123c70}
.brand span{color:#1685d6}
.tag{font-size:10pt;color:#5b718b;margin-top:1mm}
.stars{font-size:22pt;letter-spacing:3px;color:#f5b400;margin:4mm 0 1mm}
.headline{font-weight:800;font-size:17pt;line-height:1.12;color:#143b69}
.sub{font-size:9pt;color:#63758b;margin:2mm 0}
.qr{width:36mm;height:36mm;margin:2mm auto 1mm;padding:2mm;border:1px solid #e1eaf3;border-radius:2mm;display:flex;align-items:center;justify-content:center}
.qr img{max-width:100%;max-height:100%}
.call{font-size:9pt;font-weight:bold;color:#176fba}
.activation{position:absolute;bottom:8mm;left:5mm;right:5mm;border-top:1px solid #dbe5ef;padding-top:2mm;display:flex;justify-content:space-between;align-items:center;font-size:7pt;color:#52677f}
.activation strong{font-size:9pt;letter-spacing:1.2px;color:#123c70}
.footer{position:absolute;bottom:2.5mm;left:0;right:0;font-size:6pt;color:#8090a4}
@media print{
 body{background:#fff}.toolbar{display:none}.sheet{padding:0;gap:5mm}
 .plaque{box-shadow:none;-webkit-print-color-adjust:exact;print-color-adjust:exact}
}
@media screen{.plaque{box-shadow:0 4px 20px #18395b18}}
</style></head><body>
<div class="toolbar"><span>Edtek Review · <?=count($plaques)?> placa(s) · Frente 10 × 10 cm</span><button onclick="window.print()">Imprimir / Salvar PDF</button></div>
<div class="sheet">
<?php foreach ($plaques as $p): $qrUrl = $base . '/r/' . rawurlencode($p['code']) . '?src=qr'; ?>
<section class="plaque">
 <div class="brand">EDTEK <span>REVIEW</span></div>
 <div class="tag">AVALIAÇÃO GOOGLE</div>
 <div class="stars">★★★★★</div>
 <div class="headline">Sua avaliação é<br>muito importante!</div>
 <div class="sub">Aponte a câmera do celular para o QR Code</div>
 <div class="qr" data-url="<?=e($qrUrl)?>"></div>
 <div class="call">AVALIE AGORA</div>
 <div class="activation"><span>PLACA: <strong><?=e($p['code'])?></strong></span><span>ATIVAÇÃO: <strong><?=e($p['activation_code'] ?? 'N/D')?></strong></span></div>
 <div class="footer">Desenvolvido por Edtek Soluções em Informática</div>
</section>
<?php endforeach; ?>
</div>
<script>
(function(){
 if(typeof QRCode==='undefined'){alert('Não foi possível carregar o gerador de QR Code. Verifique a internet e recarregue a página.');return;}
 document.querySelectorAll('.qr').forEach(el=>new QRCode(el,{text:el.dataset.url,width:300,height:300,colorDark:'#102b50',colorLight:'#ffffff',correctLevel:QRCode.CorrectLevel.M}));
})();
</script></body></html>
