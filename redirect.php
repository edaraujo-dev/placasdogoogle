<?php
declare(strict_types=1);
require __DIR__ . '/app/bootstrap.php';
$code = strtoupper(trim($_GET['code'] ?? ''));
$state='not-found';$row=null;
if (preg_match('/^ED[0-9]{8}$/', $code)) {
 $stmt=db()->prepare("SELECT p.id,p.status,p.product_type,p.destination_url,p.whatsapp_phone,p.whatsapp_message,p.instagram_url,c.google_review_url,c.destination_url company_destination,c.status company_status FROM plaques p LEFT JOIN companies c ON c.id=p.company_id WHERE p.code=? LIMIT 1");
 $stmt->execute([$code]);$row=$stmt->fetch();
 if($row){
  if($row['status']!=='active'||$row['company_status']!=='active'){$state='inactive';}
  else{
   $destination=(string)($row['destination_url']?:($row['google_review_url']?:$row['company_destination']));
   if($row['product_type']==='whatsapp'&&!empty($row['whatsapp_phone']))$destination='https://wa.me/'.preg_replace('/\D+/','',$row['whatsapp_phone']).(!empty($row['whatsapp_message'])?'?text='.rawurlencode($row['whatsapp_message']):'');
   if($row['product_type']==='instagram'&&!empty($row['instagram_url']))$destination=$row['instagram_url'];
   $valid=filter_var($destination,FILTER_VALIDATE_URL)!==false&&strtolower((string)parse_url($destination,PHP_URL_SCHEME))==='https';
   if($valid){
    $source=($_GET['src']??'')==='nfc'?'nfc':(($_GET['src']??'')==='qr'?'qr':'unknown');
    $ip=$_SERVER['REMOTE_ADDR']??'';$ipHash=$ip!==''?hash('sha256',$ip.'edtek-review-salt'):null;$ua=substr($_SERVER['HTTP_USER_AGENT']??'',0,500);
    $log=db()->prepare('INSERT INTO scans(plaque_id,source,ip_hash,user_agent) VALUES(?,?,?,?)');$log->execute([$row['id'],$source,$ipHash,$ua]);
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');header('Location: '.$destination,true,302);exit;
   }
   $state='inactive';
  }
 }
}
$title=$state==='inactive'?'Esta placa ainda não foi ativada':'Não encontramos esta placa';
$subtitle=$state==='inactive'?'A placa foi reconhecida, mas ainda precisa ser vinculada a uma conta e configurada.':'O código informado não corresponde a uma placa cadastrada. Confira o material ou fale com quem forneceu a placa.';
$encoded=rawurlencode($code);
?>
<!doctype html><html lang="pt-br"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover"><meta name="theme-color" content="#101d36"><title><?=e($title)?> | Edtek Review</title><link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet"><link rel="stylesheet" href="<?=e(app_url('assets/style.css'))?>"></head>
<body class="public-state public-modal-page"><div class="public-backdrop-glow" aria-hidden="true"></div>
<header class="public-top-brand"><img class="brand-logo" src="<?=e(app_url('assets/logo%20edtek.png'))?>" alt="Edtek Soluções"><span>EDTEK <b>REVIEW</b><small>TECNOLOGIA PARA AVALIAÇÕES</small></span></header>
<main class="public-modal-stage"><section class="public-modal-card" role="dialog" aria-modal="true" aria-labelledby="public-title"><div class="public-modal-symbol <?=$state==='inactive'?'warning':'error'?>"><span><?=$state==='inactive'?'!':'×'?></span></div><span class="public-kicker">EDTEK REVIEW · SISTEMA DE AVALIAÇÕES</span><h1 id="public-title"><?=e($title)?></h1><p><?=e($subtitle)?></p>
<?php if($state==='inactive'):?><div class="public-code-box"><span>Identificação da placa</span><strong><?=e($code)?></strong></div><div class="public-hint"><span>i</span><span>Entre ou crie sua conta. Depois, informe a chave individual impressa na placa para ativá-la.</span></div><a class="button public-modal-action" href="<?=e(app_url('customer-login.php?next=activate&code='.$encoded))?>">Entrar</a><a class="button secondary public-modal-action" href="<?=e(app_url('customer-register.php?next=activate&code='.$encoded))?>">Criar minha conta</a><?php else:?><div class="public-hint"><span>i</span><span>Verifique se o QR Code pertence a uma placa Edtek Review.</span></div><a class="button public-modal-action" href="javascript:history.back()">Voltar</a><?php endif;?></section></main>
<a class="floating-support public-support" href="https://wa.me/5521991928446" target="_blank" rel="noopener"><span>◔</span><b>Falar com suporte</b></a><footer class="public-modal-footer">Desenvolvido por <strong>Edtek Soluções em Informática</strong></footer></body></html>