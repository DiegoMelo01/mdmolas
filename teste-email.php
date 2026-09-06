<?php
/**
 * Teste rápido de envio de e-mail — MD Molas e Artefatos de Aço
 * -----------------------------------------------------------------
 * Acesse este arquivo pelo navegador (ex.: seusite.com.br/teste-email.php)
 * depois de preencher o config.php, para confirmar que o envio de
 * e-mail funciona na sua hospedagem antes de publicar o site.
 *
 * IMPORTANTE: apague este arquivo depois do teste — ele não tem
 * proteção contra uso indevido e não deve ficar acessível publicamente.
 */

declare(strict_types=1);

$config = require __DIR__ . '/config.php';

require __DIR__ . '/libs/PHPMailer/src/Exception.php';
require __DIR__ . '/libs/PHPMailer/src/PHPMailer.php';
require __DIR__ . '/libs/PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

header('Content-Type: text/html; charset=utf-8');

echo '<h1 style="font-family:sans-serif">Teste de envio — MD Molas</h1>';

try {
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
        $mail->SMTPDebug  = 2; // mostra o diálogo com o servidor SMTP na tela
        $mail->Debugoutput = function ($str) {
            echo '<pre style="background:#111;color:#0f0;padding:8px;white-space:pre-wrap">'
                . htmlspecialchars($str) . '</pre>';
        };
    } else {
        $mail->isMail();
    }

    $mail->CharSet = 'UTF-8';
    $mail->setFrom($config['from_email'], $config['from_name']);
    $mail->addAddress($config['to_email'], $config['to_name']);
    $mail->Subject = 'Teste de envio — site MD Molas';
    $mail->Body = "Se você recebeu este e-mail, o envio a partir do site está funcionando corretamente.";
    $mail->isHTML(false);

    $mail->send();

    echo '<p style="font-family:sans-serif;color:green;font-size:18px">
        ✅ E-mail de teste enviado para <strong>' . htmlspecialchars($config['to_email']) . '</strong>.<br>
        Confira a caixa de entrada (e a pasta de spam). Se chegou certinho, pode apagar este arquivo.
        </p>';
} catch (PHPMailerException $e) {
    echo '<p style="font-family:sans-serif;color:#c0392b;font-size:18px">
        ❌ Falha ao enviar: ' . htmlspecialchars($mail->ErrorInfo) . '
        </p>
        <p style="font-family:sans-serif">
        Causas comuns: dados de SMTP incorretos em <code>config.php</code>,
        e-mail de origem (<code>from_email</code>) não existe no seu domínio,
        ou a hospedagem exige autenticação para usar o <code>mail()</code> nativo
        (nesse caso, mude <code>use_smtp</code> para <code>true</code> e preencha os dados de SMTP).
        </p>';
}
