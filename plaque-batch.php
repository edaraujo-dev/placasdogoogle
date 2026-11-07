<?php
declare(strict_types=1);
require __DIR__ . '/app/bootstrap.php';
require_login();

$created = [];
$error = '';
$qty = 5;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $raw = (($_POST['quantity_choice'] ?? '') === 'custom') ? ($_POST['custom_quantity'] ?? '') : ($_POST['quantity_choice'] ?? '');
    $qty = filter_var($raw, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 500]]) ?: 0;
    if (!$qty) {
        $error = 'Informe uma quantidade entre 1 e 500.';
    } else {
        $pdo = db();
        $pdo->beginTransaction();
        try {
            $insert = $pdo->prepare("INSERT INTO plaques (code, status, activation_code_hash, activation_code_hint, batch_ref) VALUES (?, 'stock', ?, ?, ?)");
            $update = $pdo->prepare('UPDATE plaques SET code = ? WHERE id = ?');
            $batchRef = strtoupper(bin2hex(random_bytes(8)));
            for ($i = 0; $i < $qty; $i++) {
                $placeholder = 'TMP' . bin2hex(random_bytes(12));
                $activation = strtoupper(substr(bin2hex(random_bytes(6)), 0, 10));
                $insert->execute([$placeholder, password_hash($activation, PASSWORD_DEFAULT), substr($activation, -4), $batchRef]);
                $id = (int)$pdo->lastInsertId();
                $code = 'ED' . str_pad((string)$id, 8, '0', STR_PAD_LEFT);
                $update->execute([$code, $id]);
                $created[] = [
                    'code' => $code,
                    'url' => app_url('r/' . $code),
                    'activation' => $activation,
                ];
            }
            $pdo->commit();
        } catch (Throwable $ex) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $created = [];
            $error = 'Não foi possível gerar o lote. Confirme se a atualização v0.4 do banco foi importada.';
        }
    }
}

layout_start('Gerar placas');
?>
<div class="heading">
  <div><h1>Gerar placas</h1><p>Crie unidades individuais com QR dinâmico e código de ativação.</p></div>
  <a class="button secondary" href="plaques.php">Voltar às placas</a>
</div>
<?php if ($error): ?><div class="notice error"><?=e($error)?></div><?php endif; ?>
<form method="post" class="panel batch-form">
  <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
  <label>Quantidade de placas
    <select name="quantity_choice" id="quantity" required>
      <option value="1" <?=$qty===1?'selected':''?>>1 placa</option>
      <option value="5" <?=$qty===5?'selected':''?>>Kit com 5 placas</option>
      <option value="10" <?=$qty===10?'selected':''?>>Kit com 10 placas</option>
      <option value="50" <?=$qty===50?'selected':''?>>50 placas</option>
      <option value="custom">Personalizada</option>
    </select>
  </label>
  <label id="custom-wrap" hidden>Quantidade personalizada (máximo 500)
    <input type="number" name="custom_quantity" id="custom-quantity" min="1" max="500" value="<?=$qty?>">
  </label>
  <button type="submit">Gerar placas e códigos</button>
</form>
<?php if ($created): ?>
<section class="panel generated-panel">
  <div class="heading"><div><h2>Lote criado: <?=count($created)?> placas</h2><p>As placas abaixo estão em estoque. Use a impressão em escala de 100%, sem ajustar à página.</p></div><button type="button" onclick="window.print()">Imprimir placas / Salvar PDF</button></div>
  <div class="print-grid">
  <?php foreach ($created as $item): ?>
    <article class="review-plaque exact-template">
      <img class="plaque-art" src="assets/plaque-template.png" alt="Arte oficial da placa Edtek Review com NFC e QR Code">
      <div class="qr-overlay" data-qr="<?=e($item['url'])?>" aria-label="QR Code dinâmico individual"></div>
      <div class="plaque-identification"><strong><?=e($item['code'])?></strong><span>ATIVAÇÃO: <?=e($item['activation'])?></span></div>
    </article>
  <?php endforeach; ?>
  </div>
  <div class="notice warning-print"><strong>Importante:</strong> grave no chip NTAG213 o mesmo endereço exibido no QR Code. O código de ativação fica visível na frente e deve ser tratado como código de resgate de uso único, não como senha.</div>
  <div class="tablewrap inventory"><table id="batch-table"><thead><tr><th>Placa</th><th>URL dinâmica (QR e NFC)</th><th>Ativação</th></tr></thead><tbody>
  <?php foreach ($created as $item): ?><tr><td><?=e($item['code'])?></td><td><?=e($item['url'])?></td><td><?=e($item['activation'])?></td></tr><?php endforeach; ?>
  </tbody></table></div>
  <button type="button" class="secondary" onclick="downloadCsv()">Baixar inventário CSV</button>
</section>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js" referrerpolicy="no-referrer"></script>
<script>
(function(){
 const nodes=document.querySelectorAll('.qr-overlay');
 if(typeof QRCode==='undefined'){nodes.forEach(n=>{n.textContent='QR indisponível';n.style.color='#b00020';});return;}
 nodes.forEach(n=>new QRCode(n,{text:n.dataset.qr,width:300,height:300,colorDark:'#000000',colorLight:'#ffffff',correctLevel:QRCode.CorrectLevel.H}));
})();
function downloadCsv(){
 const rows=[['codigo_placa','url_qr_nfc','codigo_ativacao'],...Array.from(document.querySelectorAll('#batch-table tbody tr')).map(tr=>Array.from(tr.children).map(td=>td.textContent.trim()))];
 const csv=rows.map(r=>r.map(v=>'"'+v.replaceAll('"','""')+'"').join(';')).join('\r\n');
 const a=document.createElement('a');a.href=URL.createObjectURL(new Blob(['\ufeff'+csv],{type:'text/csv;charset=utf-8'}));a.download='edtek-review-inventario.csv';a.click();URL.revokeObjectURL(a.href);
}
const select=document.getElementById('quantity'),wrap=document.getElementById('custom-wrap'),custom=document.getElementById('custom-quantity');
select.addEventListener('change',()=>{const isCustom=select.value==='custom';wrap.hidden=!isCustom;custom.required=isCustom;});
document.querySelector('.batch-form').addEventListener('submit',e=>{if(select.value==='custom' && !custom.value){e.preventDefault();custom.focus();}});
</script>
<?php endif; ?>
<?php layout_end(); ?>
