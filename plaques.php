<?php
declare(strict_types=1);
require __DIR__ . '/app/bootstrap.php'; require_login();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $plaqueId = (int)($_POST['plaque_id'] ?? 0); $companyId = (int)($_POST['company_id'] ?? 0);
    $stmt = db()->prepare("SELECT id FROM plaques WHERE id=? AND status='stock' AND company_id IS NULL");
    $stmt->execute([$plaqueId]);
    if (!$stmt->fetch()) { flash('Placa indisponível ou já vinculada.', 'error'); }
    else {
        $stmt = db()->prepare("UPDATE plaques SET company_id=?, status='active', activated_at=NOW() WHERE id=? AND status='stock' AND company_id IS NULL");
        $stmt->execute([$companyId, $plaqueId]);
        flash($stmt->rowCount() ? 'Placa ativada com sucesso.' : 'Não foi possível ativar a placa.', $stmt->rowCount() ? 'success' : 'error');
    }
    header('Location: plaques.php'); exit;
}
$companies = db()->query("SELECT id,name FROM companies WHERE status='active' ORDER BY name")->fetchAll();
$rows = db()->query("SELECT p.*,c.name company_name,(SELECT COUNT(*) FROM scans s WHERE s.plaque_id=p.id) scans_count FROM plaques p LEFT JOIN companies c ON c.id=p.company_id ORDER BY p.id DESC LIMIT 500")->fetchAll();
layout_start('Placas');
?><div class="heading"><div><h1>Placas</h1><p>Ative uma placa do estoque vinculando-a a uma clínica.</p></div><a class="button" href="plaque-batch.php">Gerar lote</a></div><form method="post" class="panel"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><div class="formgrid"><label>Placa disponível<select name="plaque_id" required><option value="">Selecione</option><?php foreach($rows as $r): if($r['status']==='stock'): ?><option value="<?=$r['id']?>"><?=e($r['code'])?></option><?php endif; endforeach; ?></select></label><label>Clínica<select name="company_id" required><option value="">Selecione</option><?php foreach($companies as $c): ?><option value="<?=$c['id']?>"><?=e($c['name'])?></option><?php endforeach; ?></select></label></div><button>Ativar placa</button></form><div class="panel"><div class="tablewrap"><table><thead><tr><th>Código</th><th>Cliente</th><th>Status</th><th>Acessos</th><th>Endereço QR/NFC</th></tr></thead><tbody><?php foreach($rows as $r): ?><tr><td><strong><?=e($r['code'])?></strong></td><td><?=e($r['company_name'] ?? '—')?></td><td><span class="pill"><?=e($r['status'])?></span></td><td><?=$r['scans_count']?></td><td><a target="_blank" rel="noopener" href="<?=e(app_url('r/'.$r['code']))?>">Abrir link</a></td></tr><?php endforeach; ?><?php if(!$rows): ?><tr><td colspan="5">Nenhuma placa cadastrada.</td></tr><?php endif; ?></tbody></table></div></div><?php layout_end(); ?>
