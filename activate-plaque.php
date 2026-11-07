<?php
declare(strict_types=1);
require __DIR__.'/app/bootstrap.php';require __DIR__.'/app/customer.php';require_customer();
$account=current_customer();$error='';$success='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 verify_csrf();$code=strtoupper(trim($_POST['code']??''));$key=strtoupper(preg_replace('/\s+/','',trim($_POST['activation_key']??'')));
 try{
  if(!preg_match('/^ED[0-9]{8}$/',$code)||!preg_match('/^[A-Z0-9]{8,16}$/',$key))throw new RuntimeException('Confira o código da placa e a chave impressa.');
  $pdo=db();$pdo->beginTransaction();
  $q=$pdo->prepare('SELECT id,status,activation_code_hash FROM plaques WHERE code=? FOR UPDATE');$q->execute([$code]);$p=$q->fetch();
  if(!$p||$p['status']!=='stock'||empty($p['activation_code_hash']))throw new RuntimeException('Placa não disponível para ativação. Ela pode já estar ativada.');
  if(!password_verify($key,$p['activation_code_hash']))throw new RuntimeException('Chave de ativação inválida.');
  $q=$pdo->prepare("UPDATE plaques SET company_id=?,activated_by_user_id=?,status='active',activated_at=NOW(),activation_code_hash=NULL,activation_code_hint=NULL WHERE id=? AND status='stock'");
  $q->execute([$account['company_id'],$account['id'],$p['id']]);if($q->rowCount()!==1)throw new RuntimeException('A placa foi ativada em outra sessão. Atualize a página.');
  $pdo->commit();$success='Placa '.$code.' ativada e vinculada à sua empresa. Agora configure o destino.';
 }catch(Throwable $e){if(isset($pdo)&&$pdo->inTransaction())$pdo->rollBack();$error=$e instanceof RuntimeException?$e->getMessage():'Não foi possível ativar a placa.';}
}
layout_start('Ativar placa');
?><div class="heading"><div><span class="eyebrow">ATIVAÇÃO SEGURA</span><h1>Ativar minha placa</h1><p>Use o código da placa e a chave impressa na frente.</p></div><a class="button secondary" href="customer-dashboard.php">Voltar</a></div><?php if($error):?><div class="notice error"><?=e($error)?></div><?php endif;?><?php if($success):?><div class="notice"><?=e($success)?> <a href="customer-dashboard.php">Ir para minhas placas</a></div><?php endif;?><form method="post" class="panel narrow"><?=client_csrf_field()?><label>Código da placa<input name="code" placeholder="ED00000001" maxlength="10" required autocomplete="off"></label><label>Chave de ativação<input name="activation_key" placeholder="Código impresso na placa" maxlength="16" required autocomplete="off"></label><button>Validar e ativar placa</button></form><?php layout_end(); ?>