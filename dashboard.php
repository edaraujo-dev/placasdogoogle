<?php
declare(strict_types=1);
require __DIR__ . '/app/bootstrap.php'; require_login();
$companies = (int)db()->query("SELECT COUNT(*) FROM companies")->fetchColumn();
$stock = (int)db()->query("SELECT COUNT(*) FROM plaques WHERE status='stock'")->fetchColumn();
$active = (int)db()->query("SELECT COUNT(*) FROM plaques WHERE status='active'")->fetchColumn();
$paused = (int)db()->query("SELECT COUNT(*) FROM plaques WHERE status='paused'")->fetchColumn();
$scans = (int)db()->query("SELECT COUNT(*) FROM scans WHERE scanned_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchColumn();
$qr = (int)db()->query("SELECT COUNT(*) FROM scans WHERE source='qr' AND scanned_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchColumn();
$nfc = (int)db()->query("SELECT COUNT(*) FROM scans WHERE source='nfc' AND scanned_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchColumn();
$daily = db()->query("SELECT DATE(scanned_at) dia, COUNT(*) total FROM scans WHERE scanned_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) GROUP BY DATE(scanned_at) ORDER BY dia")->fetchAll();
$byDay = []; foreach ($daily as $d) $byDay[$d['dia']] = (int)$d['total'];
$chart = []; for ($i=6; $i>=0; $i--) { $day=date('Y-m-d',strtotime("-$i days")); $chart[]=['label'=>date('D',strtotime($day)),'value'=>$byDay[$day]??0]; }
$max = max(1, ...array_column($chart,'value'));
$recent = db()->query("SELECT p.code,c.name company_name,p.activated_at,(SELECT COUNT(*) FROM scans s WHERE s.plaque_id=p.id) scans_count FROM plaques p LEFT JOIN companies c ON c.id=p.company_id WHERE p.status='active' ORDER BY p.activated_at DESC LIMIT 5")->fetchAll();
layout_start('Visão geral');
?>
<div class="heading"><div><span class="eyebrow">CENTRAL DE CONTROLE</span><h1>Visão geral</h1><p>Acompanhe suas placas, clínicas e resultados em um só lugar.</p></div><a class="button" href="plaque-batch.php">＋ Gerar placas</a></div>
<div class="stats">
 <div class="stat"><small>Clínicas cadastradas</small><strong><?=$companies?></strong><span class="stat-foot">Clientes na plataforma</span></div>
 <div class="stat"><small>Placas em estoque</small><strong><?=$stock?></strong><span class="stat-foot">Prontas para ativação</span></div>
 <div class="stat"><small>Placas ativadas</small><strong><?=$active?></strong><span class="stat-foot">Em operação</span></div>
 <div class="stat"><small>Acessos · 30 dias</small><strong><?=$scans?></strong><span class="stat-foot">Leituras registradas</span></div>
</div>
<div class="dashboard-grid">
 <section class="panel chart-panel"><div class="section-head"><div><h2>Atividade recente</h2><p>Leituras registradas nos últimos 7 dias</p></div><span class="mini-tag">7 DIAS</span></div><div class="bar-chart" aria-label="Gráfico de leituras por dia">
 <?php foreach($chart as $point): $height=$point['value']?max(7,(int)round($point['value']/$max*100)):3; ?><div class="bar-column"><strong><?=$point['value']?></strong><div class="bar-track"><i style="height:<?=$height?>%"></i></div><small><?=e($point['label'])?></small></div><?php endforeach; ?>
 </div></section>
 <section class="panel source-panel"><div class="section-head"><div><h2>Origem dos acessos</h2><p>Últimos 30 dias</p></div></div><div class="source-row"><span class="source-icon qr-icon">▦</span><div><b>QR Code</b><small>Leituras pela câmera</small></div><strong><?=$qr?></strong></div><div class="source-row"><span class="source-icon nfc-icon">⌁</span><div><b>NFC</b><small>Leituras por aproximação</small></div><strong><?=$nfc?></strong></div><div class="source-total"><span>Total de leituras</span><b><?=$scans?></b></div></section>
</div>
<section class="panel recent-panel"><div class="section-head"><div><h2>Placas ativadas recentemente</h2><p>Últimas unidades vinculadas às clínicas</p></div><a href="plaques.php" class="text-link">Ver todas as placas →</a></div><div class="tablewrap"><table><thead><tr><th>Identificação</th><th>Clínica</th><th>Ativação</th><th>Leituras</th><th>Status</th></tr></thead><tbody><?php foreach($recent as $r): ?><tr><td><strong><?=e($r['code'])?></strong></td><td><?=e($r['company_name']??'—')?></td><td><?=e($r['activated_at']?date('d/m/Y',strtotime($r['activated_at'])):'—')?></td><td><?=$r['scans_count']?></td><td><span class="status-dot">Ativa</span></td></tr><?php endforeach; ?><?php if(!$recent): ?><tr><td colspan="5">Nenhuma placa ativada até o momento.</td></tr><?php endif; ?></tbody></table></div></section>
<div class="quick-links"><a href="companies.php"><span>＋</span><b>Cadastrar clínica</b><small>Adicionar novo cliente</small></a><a href="plaques.php"><span>▤</span><b>Gerenciar placas</b><small>Ativar e acompanhar estoque</small></a><a href="plaque-batch.php"><span>⌗</span><b>Produzir novo lote</b><small>Gerar QR Codes individuais</small></a></div>
<?php layout_end(); ?>
