<?php
declare(strict_types=1);

/** Funções compartilhadas da área do cliente e envio SMTP. */
function customer_logged_in(): bool { return !empty($_SESSION['customer_id']); }
function require_customer(): void { if (!customer_logged_in()) { header('Location: customer-login.php'); exit; } }
function current_customer(): array {
    static $account = null;
    if ($account !== null) return $account;
    $q = db()->prepare('SELECT a.id,a.name,a.email,a.company_id,c.name company_name FROM customer_accounts a JOIN companies c ON c.id=a.company_id WHERE a.id=? AND a.status=\'active\' LIMIT 1');
    $q->execute([(int)($_SESSION['customer_id'] ?? 0)]);
    $account = $q->fetch() ?: [];
    if (!$account) { unset($_SESSION['customer_id']); header('Location: customer-login.php'); exit; }
    return $account;
}
function client_csrf_field(): string { return '<input type="hidden" name="csrf" value="'.e(csrf_token()).'">'; }

function app_crypto_key(): string {
    global $config;
    return hash('sha256',(string)($config['db']['pass']??'').'|'.(string)($config['base_url']??'').'|edtek-review-mail',true);
}
function encrypt_app_secret(string $plain): ?string {
    if (!function_exists('openssl_encrypt')) return null;
    $iv=random_bytes(16);$cipher=openssl_encrypt($plain,'aes-256-cbc',app_crypto_key(),OPENSSL_RAW_DATA,$iv);
    return $cipher===false?null:base64_encode($iv.$cipher);
}
function decrypt_app_secret(?string $encoded): string {
    if (!$encoded || !function_exists('openssl_decrypt')) return '';
    $raw=base64_decode($encoded,true);
    if ($raw===false||strlen($raw)<=16) return '';
    return openssl_decrypt(substr($raw,16),'aes-256-cbc',app_crypto_key(),OPENSSL_RAW_DATA,substr($raw,0,16))?:'';
}

function send_app_email(string $to, string $subject, string $html): bool {
    $s = db()->query('SELECT * FROM email_settings WHERE id=1')->fetch();
    if (!$s || !$s['host'] || !$s['from_email']) return false;
    $crypto = hash('sha256', (string)($GLOBALS['config']['db']['pass'] ?? '').'|'.(string)($GLOBALS['config']['base_url'] ?? '').'|edtek-review-mail', true);
    $password = '';
    if (!empty($s['password_encrypted'])) {
        $raw = base64_decode($s['password_encrypted'], true);
        if ($raw !== false && strlen($raw) > 16) $password = openssl_decrypt(substr($raw,16), 'aes-256-cbc', $crypto, OPENSSL_RAW_DATA, substr($raw,0,16)) ?: '';
    }
    $transport = $s['encryption']==='ssl' ? 'ssl://' : 'tcp://';
    $errno=0; $errstr='';
    $fp = @stream_socket_client($transport.$s['host'].':'.(int)$s['port'], $errno, $errstr, 15, STREAM_CLIENT_CONNECT);
    if (!$fp) return false;
    stream_set_timeout($fp,15);
    $read = static function() use ($fp): string { $out=''; do { $line=fgets($fp,515); if ($line===false) break; $out.=$line; } while (isset($line[3]) && $line[3]==='-'); return $out; };
    $cmd = static function(string $line, array $codes) use ($fp,$read): bool { fwrite($fp,$line."\r\n"); $r=$read(); foreach($codes as $code) if (str_starts_with($r,(string)$code)) return true; return false; };
    $greeting=$read();
    if (!str_starts_with($greeting,'220') || !$cmd('EHLO '.($_SERVER['HTTP_HOST'] ?? 'localhost'),[250])) { fclose($fp); return false; }
    if ($s['encryption']==='tls') {
        if (!$cmd('STARTTLS',[220]) || !stream_socket_enable_crypto($fp,true,STREAM_CRYPTO_METHOD_TLS_CLIENT) || !$cmd('EHLO '.($_SERVER['HTTP_HOST'] ?? 'localhost'),[250])) { fclose($fp); return false; }
    }
    if ($s['username']!=='') {
        if (!$cmd('AUTH LOGIN',[334]) || !$cmd(base64_encode($s['username']),[334]) || !$cmd(base64_encode($password),[235])) { fclose($fp); return false; }
    }
    $from=(string)$s['from_email'];
    $encodeHeader=static fn(string $value): string => function_exists('mb_encode_mimeheader') ? mb_encode_mimeheader($value) : $value;
    if (!$cmd('MAIL FROM:<'.$from.'>',[250]) || !$cmd('RCPT TO:<'.$to.'>',[250,251]) || !$cmd('DATA',[354])) { fclose($fp); return false; }
    $headers='From: '.$encodeHeader((string)$s['from_name']).' <'.$from.">\r\nTo: <".$to.">\r\nSubject: ".$encodeHeader($subject)."\r\nMIME-Version: 1.0\r\nContent-Type: text/html; charset=UTF-8\r\n";
    if (!empty($s['reply_to'])) $headers.='Reply-To: '.$s['reply_to']."\r\n";
    $body=$headers."\r\n".$html;
    fwrite($fp,preg_replace('/\r?\n\./', "\r\n..", $body)."\r\n.\r\n");
    $ok=str_starts_with($read(),'250');
    $cmd('QUIT',[221]); fclose($fp); return $ok;
}
function mail_template(string $heading,string $message,string $url,string $button='Continuar'): string {
    return '<div style="font-family:Arial,sans-serif;max-width:560px;margin:30px auto;padding:32px;border:1px solid #e8edf5;border-radius:18px;color:#20304b"><h2 style="margin-top:0">'.e($heading).'</h2><p style="line-height:1.7;color:#62718a">'.e($message).'</p><p style="margin:28px 0"><a href="'.e($url).'" style="background:#2868e8;color:#fff;text-decoration:none;padding:13px 20px;border-radius:9px;font-weight:bold">'.e($button).'</a></p><small style="color:#8995a8">Edtek Review · Edtek Soluções</small></div>';
}
function customer_company_destination(array $input): array {
    $type=$input['product_type'] ?? 'google_review';
    if (!in_array($type,['google_review','whatsapp','instagram'],true)) throw new RuntimeException('Tipo de placa inválido.');
    if ($type==='google_review') {
        $url=trim((string)($input['destination_url'] ?? ''));
        if (!valid_google_url($url)) throw new RuntimeException('Informe um link HTTPS válido do Google.');
        return [$type,$url,null,null,null];
    }
    if ($type==='instagram') {
        $url=trim((string)($input['instagram_url'] ?? ''));
        if (!filter_var($url,FILTER_VALIDATE_URL) || !in_array(strtolower((string)parse_url($url,PHP_URL_SCHEME)),['https'],true) || !str_contains(strtolower((string)parse_url($url,PHP_URL_HOST)),'instagram.com')) throw new RuntimeException('Informe o link válido do perfil do Instagram.');
        return [$type,$url,null,null,null];
    }
    $phone=preg_replace('/\D+/','',(string)($input['whatsapp_phone'] ?? ''));
    if (strlen($phone)<10 || strlen($phone)>15) throw new RuntimeException('Informe o WhatsApp com DDI e DDD.');
    $message=trim((string)($input['whatsapp_message'] ?? ''));
    $url='https://wa.me/'.$phone.($message!==''?'?text='.rawurlencode($message):'');
    return [$type,$url,$phone,$message,null];
}
