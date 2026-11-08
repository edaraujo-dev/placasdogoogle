<?php
declare(strict_types=1);
require __DIR__.'/app/bootstrap.php'; require __DIR__.'/app/customer.php';
if(customer_logged_in()){$go=(($_POST['next']??'')==='activate'&&$nextCode!=='')?'activate-plaque.php?code='.rawurlencode($nextCode):'customer-dashboard.php';header('Location: '.$go);exit;}
$nextCode=preg_match('/^ED[0-9]{8}$/',strtoupper($_GET['code']??($_POST['code']??'')))?strtoupper($_GET['code']):'';
$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 verify_csrf();
 $name=trim($_POST['name']??'');$company=trim($_POST['company_name']??'');$email=strtolower(trim($_POST['email']??''));$pass=(string)($_POST['password']??'');
 if($name===''||$company===''||!filter_var($email,FILTER_VALIDATE_EMAIL)||strlen($pass)<10){$error='Preencha os campos e use uma senha com pelo menos 10 caracteres.';}
 else{
  try{
   $pdo=db();$pdo->beginTransaction();
   $q=$pdo->prepare('SELECT id FROM customer_accounts WHERE email=?');$q->execute([$email]);if($q->fetch())throw new RuntimeException('Este e-mail já possui cadastro.');
   $q=$pdo->prepare("INSERT INTO companies(name,contact_name,email,google_review_url,product_type,status) VALUES(?,?,?,NULL,'google_review','active')");$q->execute([$company,$name,$email]);$companyId=(int)$pdo->lastInsertId();
   $q=$pdo->prepare("INSERT INTO customer_accounts(company_id,name,email,password_hash,status) VALUES(?,?,?,?, 'active')");$q->execute([$companyId,$name,$email,password_hash($pass,PASSWORD_DEFAULT)]);$id=(int)$pdo->lastInsertId();
   $pdo->commit();session_regenerate_id(true);$_SESSION['customer_id']=$id;$_SESSION['customer_name']=$name;
   @send_app_email($email,'Bem-vindo ao Edtek Review',mail_template('Bem-vindo ao Edtek Review','Sua conta foi criada. Agora você pode ativar suas placas e configurar seus destinos.',app_url('customer-dashboard.php'),'Acessar minha conta'));
   header('Location: customer-dashboard.php');exit;
  }catch(Throwable $e){if(isset($pdo)&&$pdo->inTransaction())$pdo->rollBack();$error=$e instanceof RuntimeException?$e->getMessage():'Não foi possível concluir o cadastro. Tente novamente.';}
 }
}
layout_start('Criar conta');
?><section class="narrow"><span class="eyebrow">ÁREA DO CLIENTE</span><h1>Crie sua conta</h1><p>Cadastre sua empresa para ativar e gerenciar suas placas.</p><?php if($error):?><div class="notice error"><?=e($error)?></div><?php endif;?><form method="post" class="panel"><input type="hidden" name="next" value="<?=e($_GET['next']??'')?>"><input type="hidden" name="code" value="<?=e($nextCode)?>"><?=client_csrf_field()?><label>Seu nome<input name="name" required autocomplete="name"></label><label>Nome da clínica ou empresa<input name="company_name" required></label><label>E-mail<input type="email" name="email" required autocomplete="email"></label><label>Senha (mínimo 10 caracteres)<input type="password" name="password" minlength="10" required autocomplete="new-password"></label><button type="submit">Criar minha conta</button><p style="margin-top:16px">Já tem cadastro? <a href="customer-login.php">Entrar</a></p></form></section><?php layout_end(); ?>