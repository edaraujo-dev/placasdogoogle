<?php
declare(strict_types=1);
require __DIR__ . '/app/bootstrap.php'; require_login();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $name = trim($_POST['name'] ?? '');
    $url = trim($_POST['google_review_url'] ?? '');
    if ($name === '' || !valid_google_url($url)) {
        flash('Informe o nome e um link HTTPS válido do Google.', 'error');
    } else {
        $stmt = db()->prepare('INSERT INTO companies (name,contact_name,email,phone,google_review_url) VALUES (?,?,?,?,?)');
        $stmt->execute([$name, trim($_POST['contact_name'] ?? '') ?: null, trim($_POST['email'] ?? '') ?: null, trim($_POST['phone'] ?? '') ?: null, $url]);
        flash('Clínica cadastrada. Agora vincule uma placa disponível.');
    }
    header('Location: companies.php'); exit;
}
$rows = db()->query("SELECT c.*, (SELECT COUNT(*) FROM plaques p WHERE p.company_id=c.id AND p.status='active') plaque_count FROM companies c ORDER BY c.created_at DESC")->fetchAll();
layout_start('Clínicas');
?><div class="heading"><div><h1>Clínicas</h1><p>Cadastre empresas e seus links de avaliação.</p></div></div><form method="post" class="panel"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><div class="formgrid"><label>Nome da clínica *<input name="name" required></label><label>Responsável<input name="contact_name"></label><label>E-mail<input type="email" name="email"></label><label>Telefone<input name="phone"></label><label class="full">Link de avaliação do Google *<input type="url" name="google_review_url" placeholder="https://g.page/r/..." required></label></div><button>Cadastrar clínica</button></form><div class="panel"><h2>Clientes cadastrados</h2><div class="tablewrap"><table><thead><tr><th>Clínica</th><th>Contato</th><th>Placas ativas</th><th>Status</th></tr></thead><tbody><?php foreach($rows as $r): ?><tr><td><?=e($r['name'])?></td><td><?=e($r['contact_name'] ?? '—')?></td><td><?=$r['plaque_count']?></td><td><?=e($r['status'])?></td></tr><?php endforeach; ?><?php if(!$rows): ?><tr><td colspan="4">Nenhuma clínica cadastrada.</td></tr><?php endif; ?></tbody></table></div></div><?php layout_end(); ?>
