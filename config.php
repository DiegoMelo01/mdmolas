<?php
/**
 * Configuração do formulário de orçamento — MD Molas e Artefatos de Aço
 * -----------------------------------------------------------------
 * Preencha os dados abaixo com as informações da sua hospedagem/e-mail.
 * Depois de configurar, você pode apagar o arquivo teste-email.php
 * (usado só para testar o envio antes de publicar).
 */

return [

    // Para onde a solicitação de orçamento será enviada.
    'to_email' => 'contato@mdmolas.com.br',
    'to_name'  => 'MD Molas e Artefatos de Aço',

    // Prefixo do assunto do e-mail recebido.
    'subject_prefix' => '[Site MD Molas] Nova solicitação de orçamento',

    // Envia uma cópia automática de confirmação para quem preencheu o formulário?
    'send_auto_reply' => true,
    'auto_reply_subject' => 'Recebemos sua solicitação — MD Molas e Artefatos de Aço',

    // -----------------------------------------------------------------
    // ENVIO: mail() nativo ou SMTP autenticado
    // -----------------------------------------------------------------
    // 'use_smtp' => false  → usa a função mail() do PHP. Funciona na
    //   maioria das hospedagens cPanel sem configuração extra, mas tem
    //   mais chance de cair em spam dependendo do provedor.
    // 'use_smtp' => true   → envia autenticado via SMTP (recomendado).
    //   Use os dados de e-mail da sua hospedagem (geralmente em
    //   cPanel > Contas de E-mail > Configurar cliente de e-mail).
    'use_smtp' => false,

    'smtp' => [
        'host'     => 'mail.mdmolas.com.br',   // ex.: mail.seudominio.com.br
        'port'     => 587,                      // 465 (SSL) ou 587 (TLS)
        'secure'   => 'tls',                    // 'tls' ou 'ssl'
        'username' => 'contato@mdmolas.com.br',
        'password' => 'SUBSTITUA_PELA_SENHA_DO_E-MAIL',
    ],

    // E-mail que aparece como remetente técnico (deve ser do mesmo
    // domínio do servidor na maioria das hospedagens, para não cair
    // em spam). O e-mail da pessoa que preencheu o formulário entra
    // como "Responder a" (reply-to), não como remetente.
    'from_email' => 'nao-responda@mdmolas.com.br',
    'from_name'  => 'Site MD Molas',

    // -----------------------------------------------------------------
    // ANEXO
    // -----------------------------------------------------------------
    'max_upload_bytes' => 20 * 1024 * 1024, // 20 MB
    'allowed_extensions' => [
        'pdf', 'png', 'jpg', 'jpeg', 'webp', 'gif',
        'dwg', 'dxf', 'step', 'stp', 'igs', 'iges',
        'zip', 'rar', 'doc', 'docx', 'xls', 'xlsx',
    ],

    // -----------------------------------------------------------------
    // PROTEÇÃO ANTISPAM
    // -----------------------------------------------------------------
    // Rejeita silenciosamente envios mais rápidos que isso (segundos)
    // entre o carregamento da página e o envio — bots costumam enviar
    // quase instantaneamente.
    'min_seconds_to_submit' => 3,

    // Limite de envios por IP dentro da janela abaixo (proteção simples
    // contra flood). Requer que a pasta /data seja gravável.
    'rate_limit_max'    => 8,
    'rate_limit_window' => 3600, // 1 hora, em segundos

    // Domínio(s) permitido(s) a enviar para este endpoint (proteção
    // contra uso do formulário por outros sites). Deixe vazio para não
    // checar.
    'allowed_origins' => [
        // 'https://www.mdmolas.com.br',
    ],
];
