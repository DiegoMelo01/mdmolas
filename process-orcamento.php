<?php
/**
 * Processa o formulário de orçamento do site MD Molas e envia por e-mail.
 * -----------------------------------------------------------------------
 * Recebe o POST do formulário (assets/js/script.js faz o envio via fetch),
 * valida os campos, aplica proteções antispam e envia o e-mail usando
 * PHPMailer (mail() nativo ou SMTP, conforme config.php).
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

// ---------------------------------------------------------------------
// Só aceita POST
// ---------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'Método não permitido.']);
    exit;
}

$config = require __DIR__ . '/config.php';

// -----------------------------------------------------------------------
// Se o arquivo enviado for maior do que os limites configurados no
// servidor (upload_max_filesize / post_max_size do PHP), o PHP descarta
// $_POST e $_FILES silenciosamente. Detectamos isso aqui para devolver
// uma mensagem clara em vez de "campo obrigatório faltando".
// -----------------------------------------------------------------------
$contentLength = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);
if ($contentLength > 0 && empty($_POST) && empty($_FILES)) {
    respond(
        false,
        'O arquivo enviado é maior do que o limite atual do servidor. ' .
        'Verifique se "upload_max_filesize" e "post_max_size" estão configurados ' .
        'para pelo menos 20MB (veja o .htaccess/.user.ini incluído) ou envie um arquivo menor.',
        413
    );
}

require __DIR__ . '/libs/PHPMailer/src/Exception.php';
require __DIR__ . '/libs/PHPMailer/src/PHPMailer.php';
require __DIR__ . '/libs/PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

// ---------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------

function respond(bool $ok, string $message, int $code = 200): void
{
    http_response_code($code);
    echo json_encode(['ok' => $ok, 'message' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

/** Remove quebras de linha e caracteres de controle (evita header injection). */
function clean_line(string $value): string
{
    $value = str_replace(["\r", "\n"], ' ', $value);
    return trim(strip_tags($value));
}

function clean_multiline(string $value): string
{
    $value = str_replace("\r\n", "\n", $value);
    return trim(strip_tags($value));
}

function client_ip(): string
{
    foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $key) {
        if (!empty($_SERVER[$key])) {
            $parts = explode(',', $_SERVER[$key]);
            return trim($parts[0]);
        }
    }
    return '0.0.0.0';
}

// ---------------------------------------------------------------------
// Proteção 1 — origem (opcional, só valida se configurado)
// ---------------------------------------------------------------------
if (!empty($config['allowed_origins'])) {
    $origin = $_SERVER['HTTP_ORIGIN'] ?? $_SERVER['HTTP_REFERER'] ?? '';
    $allowed = false;
    foreach ($config['allowed_origins'] as $allowedOrigin) {
        if ($origin && str_starts_with($origin, $allowedOrigin)) {
            $allowed = true;
            break;
        }
    }
    if (!$allowed) {
        respond(false, 'Origem não permitida.', 403);
    }
}

// ---------------------------------------------------------------------
// Proteção 2 — honeypot (campo invisível que só bots preenchem)
// ---------------------------------------------------------------------
$honeypot = trim((string) ($_POST['website'] ?? ''));
$isHoneypotTriggered = $honeypot !== '';

// ---------------------------------------------------------------------
// Proteção 3 — tempo mínimo entre carregar a página e enviar
// ---------------------------------------------------------------------
$loadedAt = (int) ($_POST['ts'] ?? 0);
$now = (int) (microtime(true) * 1000);
$isTooFast = $loadedAt > 0 && ($now - $loadedAt) < ($config['min_seconds_to_submit'] * 1000);

// Bots: respondemos como se tivesse dado certo, sem processar nada —
// assim eles não descobrem que foram bloqueados e não tentam de novo.
if ($isHoneypotTriggered || $isTooFast) {
    respond(true, 'Solicitação enviada com sucesso.');
}

// ---------------------------------------------------------------------
// Proteção 4 — limite de envios por IP (rate limit simples em arquivo)
// ---------------------------------------------------------------------
$dataDir = __DIR__ . '/data';
if (!is_dir($dataDir)) {
    @mkdir($dataDir, 0775, true);
}
$rateFile = $dataDir . '/rate_limit.json';
$ip = client_ip();

if (is_dir($dataDir) && is_writable($dataDir)) {
    $fp = fopen($rateFile, 'c+');
    if ($fp) {
        flock($fp, LOCK_EX);
        $raw = stream_get_contents($fp);
        $log = $raw ? json_decode($raw, true) : [];
        if (!is_array($log)) {
            $log = [];
        }

        $windowStart = time() - $config['rate_limit_window'];
        $log = array_filter($log, fn ($entry) => $entry['t'] > $windowStart);

        $countForIp = count(array_filter($log, fn ($entry) => $entry['ip'] === $ip));

        if ($countForIp >= $config['rate_limit_max']) {
            flock($fp, LOCK_UN);
            fclose($fp);
            respond(false, 'Muitas solicitações em pouco tempo. Tente novamente mais tarde.', 429);
        }

        $log[] = ['ip' => $ip, 't' => time()];

        ftruncate($fp, 0);
        rewind($fp);
        fwrite($fp, json_encode(array_values($log)));
        flock($fp, LOCK_UN);
        fclose($fp);
    }
}

// ---------------------------------------------------------------------
// Validação dos campos
// ---------------------------------------------------------------------
$nome      = clean_line((string) ($_POST['nome'] ?? ''));
$empresa   = clean_line((string) ($_POST['empresa'] ?? ''));
$email     = clean_line((string) ($_POST['email'] ?? ''));
$whatsapp  = clean_line((string) ($_POST['whatsapp'] ?? ''));
$segmento  = clean_line((string) ($_POST['segmento'] ?? ''));
$tipoMola  = clean_line((string) ($_POST['tipo_mola'] ?? ''));
$quantidade = clean_line((string) ($_POST['quantidade'] ?? ''));
$desenho   = clean_line((string) ($_POST['desenho'] ?? ''));
$amostra   = clean_line((string) ($_POST['amostra'] ?? ''));
$mensagem  = clean_multiline((string) ($_POST['mensagem'] ?? ''));

if ($nome === '' || mb_strlen($nome) > 150) {
    respond(false, 'Informe um nome válido.', 422);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respond(false, 'Informe um e-mail válido.', 422);
}
if ($whatsapp === '' || mb_strlen($whatsapp) > 40) {
    respond(false, 'Informe um WhatsApp válido.', 422);
}

// ---------------------------------------------------------------------
// Validação do anexo (opcional)
// ---------------------------------------------------------------------
$attachmentPath = null;
$attachmentName = null;

if (!empty($_FILES['anexo']) && $_FILES['anexo']['error'] !== UPLOAD_ERR_NO_FILE) {
    $file = $_FILES['anexo'];

    if ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE) {
        respond(false, 'O arquivo enviado é maior do que o limite permitido pelo servidor.', 413);
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        respond(false, 'Não foi possível processar o arquivo enviado.', 422);
    }
    if ($file['size'] > $config['max_upload_bytes']) {
        $limitMb = (int) ($config['max_upload_bytes'] / 1024 / 1024);
        respond(false, "O arquivo excede o limite de {$limitMb}MB.", 413);
    }

    $originalName = $file['name'];
    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

    if (!in_array($ext, $config['allowed_extensions'], true)) {
        respond(false, 'Formato de arquivo não permitido.', 422);
    }

    if (!is_uploaded_file($file['tmp_name'])) {
        respond(false, 'Falha ao enviar o arquivo.', 422);
    }

    $attachmentPath = $file['tmp_name'];
    $attachmentName = preg_replace('/[^A-Za-z0-9._\-]/', '_', $originalName);
}

// ---------------------------------------------------------------------
// Monta e envia o e-mail
// ---------------------------------------------------------------------
function build_mailer(array $config): PHPMailer
{
    $mail = new PHPMailer(true);

    if ($config['use_smtp']) {
        $mail->isSMTP();
        $mail->Host       = $config['smtp']['host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $config['smtp']['username'];
        $mail->Password   = $config['smtp']['password'];
        $mail->SMTPSecure = $config['smtp']['secure'] === 'ssl'
            ? PHPMailer::ENCRYPTION_SMTPS
            : PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = $config['smtp']['port'];
    } else {
        $mail->isMail();
    }

    $mail->CharSet = 'UTF-8';

    return $mail;
}

try {
    $mail = build_mailer($config);
    $mail->setFrom($config['from_email'], $config['from_name']);
    $mail->addAddress($config['to_email'], $config['to_name']);
    $mail->addReplyTo($email, $nome);

    $mail->Subject = $config['subject_prefix'] . ' — ' . $nome;

    $linhas = [
        "Nova solicitação de orçamento recebida pelo site.",
        "",
        "Nome: {$nome}",
        "Empresa: " . ($empresa ?: '-'),
        "E-mail: {$email}",
        "WhatsApp: {$whatsapp}",
        "Segmento: " . ($segmento ?: '-'),
        "Tipo de mola: " . ($tipoMola ?: '-'),
        "Quantidade estimada: " . ($quantidade ?: '-'),
        "Possui desenho técnico: " . ($desenho ?: '-'),
        "Possui amostra: " . ($amostra ?: '-'),
        "",
        "Mensagem:",
        $mensagem ?: '(sem mensagem)',
    ];
    $mail->Body = implode("\n", $linhas);
    $mail->isHTML(false);

    if ($attachmentPath && $attachmentName) {
        $mail->addAttachment($attachmentPath, $attachmentName);
    }

    $mail->send();

    // Confirmação automática para quem preencheu o formulário
    if (!empty($config['send_auto_reply'])) {
        try {
            $reply = build_mailer($config);
            $reply->setFrom($config['from_email'], $config['from_name']);
            $reply->addAddress($email, $nome);
            $reply->Subject = $config['auto_reply_subject'];
            $reply->Body =
                "Olá, {$nome}!\n\n" .
                "Recebemos sua solicitação de orçamento e nossa equipe vai avaliar " .
                "as informações enviadas. Em breve entraremos em contato pelo " .
                "e-mail ou WhatsApp informado.\n\n" .
                "Resumo do que você enviou:\n" .
                "- Segmento: " . ($segmento ?: '-') . "\n" .
                "- Tipo de mola: " . ($tipoMola ?: '-') . "\n" .
                "- Quantidade estimada: " . ($quantidade ?: '-') . "\n\n" .
                "Atenciosamente,\n" .
                $config['to_name'];
            $reply->isHTML(false);
            $reply->send();
        } catch (PHPMailerException $e) {
            // Falha no e-mail de confirmação não deve impedir a resposta de sucesso.
        }
    }

    respond(true, 'Solicitação enviada com sucesso! Em breve entraremos em contato.');
} catch (PHPMailerException $e) {
    respond(false, 'Não foi possível enviar sua solicitação agora. Tente novamente em instantes ou fale pelo WhatsApp.', 500);
}
