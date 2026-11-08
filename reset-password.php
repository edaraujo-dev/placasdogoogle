<?php
declare(strict_types=1);require __DIR__.'/app/bootstrap.php';
$type=$_GET['type']??'customer';$token=(string)($_GET['token']??'');$error='';$ok=false;$reset=null;
$allowed=['admin'=>'admins','customer'=>'customer_accounts','reseller'=>'reseller_accounts'];
if(isset($allowed[$type])){$q=db()->prepare("SELECT * FROM password_resets WHERE token_hash=? AND account_type=? AND used_at IS NULL AND expires_at>NOW() LIMIT 1");$q->execute([hash('sha256',$token),$type]);$reset=$q->fetch();}
if($_SERVER['REQUEST_METHOD']==='POST'){
 verify_csrf();$pass=(string)($_POST['password']??'');$confirm=(string)($_POST['confirm']??'');
 if(!isset($allowed[$type]))$error='Tipo de conta inválido.';
 elseif(strlen($pass)<10||$pass!==$confirm)$error='As senhas devem ser iguais e ter pelo menos 10 caracteres.';
 else{
  $pdo=db();$pdo->beginTransaction();
  try{
   $q=$pdo->prepare("SELECT * FROM password_resets WHERE token_hash=? AND account_type=? AND used_at IS NULL AND expires_at>NOW() LIMIT 1 FOR UPDATE");$q->execute([hash('sha256',$token),$type]);$locked=$q->fetch();
   if(!$locked)throw new RuntimeException('Link inválido, expirado ou já utilizado.');
   $table=$allowed[$type];$q=$pdo->prepare("UPDATE {$table} SET password_hash=? WHERE id=?");$q->execute([password_hash($pass,PASSWORD_DEFAULT),$locked['account_id']]);
   if($q->rowCount()!==1)throw new RuntimeException('Conta não encontrada.');
   $q=$pdo->prepare('UPDATE password_resets SET used_at=NOW() WHERE id=?');$q->execute([$locked['id']]);$pdo->commit();$ok=true;
  }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();$error=$e instanceof RuntimeException?$e->getMessage():'Não foi possível atualizar a senha.';}
 }
}
layout_start('Nova senha');
?>
<section class="narrow"><h1>Crie uma nova senha</h1><?php if($ok):?><div class="notice">Senha atualizada. <a href="<?=$type==='admin'?'login.php':($type==='reseller'?'reseller-login.php':'customer-login.php')?>">Acesse sua conta.</a></div><?php else:?><?php if($error||!$reset):?><div class="notice error"><?=e($error?:'Link inválido ou expirado.')?></div><?php else:?><form method="post" class="panel"><?= '<input type="hidden" name="csrf" value="'.e(csrf_token()).'">' ?><label>Nova senha<input type="password" name="password" minlength="10" required autocomplete="new-password"></label><label>Confirme a senha<input type="password" name="confirm" minlength="10" required autocomplete="new-password"></label><button>Salvar nova senha</button></form><?php endif;?><?php endif;?></section>
<?php layout_end(); ?>