# Formulário de orçamento — envio por e-mail

O formulário de contato agora envia por e-mail de verdade (com anexo de
até 20MB) em vez de só abrir o WhatsApp. Veja abaixo como colocar no ar.

## 1. Suba os arquivos

Envie **todo** o conteúdo desta pasta para a raiz do seu site (via FTP ou
Gerenciador de Arquivos do cPanel), incluindo os arquivos que começam com
ponto (`.htaccess`, `.user.ini`) — em muitos clientes de FTP eles ficam
ocultos por padrão; ative "mostrar arquivos ocultos".

## 2. Configure o `config.php`

Abra `config.php` e ajuste pelo menos:

- `to_email` — para qual e-mail as solicitações devem chegar.
- `from_email` — um e-mail do **mesmo domínio do site** (ex.:
  `nao-responda@mdmolas.com.br`). Isso evita que a mensagem caia em spam.
  Crie essa conta de e-mail no cPanel (não precisa ser uma caixa de
  verdade, só precisa existir).

Se o envio simples (`use_smtp => false`) cair em spam ou não chegar,
mude para `use_smtp => true` e preencha os dados de SMTP — normalmente
em **cPanel → Contas de E-mail → Conectar Dispositivos/Configurar
Cliente de E-mail**, que mostra host, porta e usuário/senha.

## 3. Teste antes de publicar

Acesse `https://seusite.com.br/teste-email.php` pelo navegador. Se
aparecer "✅ E-mail de teste enviado", está tudo certo. Se der erro, a
mensagem na tela já indica a causa mais provável.

**Apague o arquivo `teste-email.php` depois do teste** — ele não tem
proteção e não deve ficar publicado.

## 4. Confirme o limite de 20MB

Os arquivos `.htaccess` e `.user.ini` já pedem ao servidor para aceitar
uploads de até 20MB (a maioria das hospedagens tem um limite padrão bem
menor, geralmente 2MB a 8MB). Depois de publicar, teste anexando um
arquivo grande (~15–18MB) pelo formulário do site.

Se o formulário disser que o arquivo passou do limite do servidor mesmo
assim, peça ao suporte da sua hospedagem para aumentar
`upload_max_filesize` e `post_max_size` para 20M/21M — em alguns planos
isso só pode ser mudado pelo próprio cPanel, em **Selecionar versão do
PHP → Opções**.

## O que já vem pronto

- **Envio por e-mail** com anexo (PDF, imagem, DWG/DXF, ZIP, etc., até
  20MB), usando a biblioteca PHPMailer (incluída na pasta `libs/`).
- **E-mail de confirmação automática** para quem preenche o formulário
  (pode desligar em `config.php` → `send_auto_reply`).
- **Proteção antispam**:
  - Campo "honeypot" invisível — bots que preenchem tudo caem nessa
    armadilha e a mensagem é descartada silenciosamente.
  - Verificação de tempo mínimo entre abrir a página e enviar (bots
    costumam enviar quase instantaneamente).
  - Limite de envios por IP por hora (configurável em `config.php`).
  - Sanitização de todos os campos contra injeção de cabeçalho de
    e-mail.
- **Validação do anexo**: tamanho máximo e extensões permitidas,
  configuráveis em `config.php`.
- **Alternativa por WhatsApp**: o link "Chame no WhatsApp" ao lado do
  botão de enviar continua disponível, abrindo a conversa com um resumo
  do que a pessoa preencheu — sem precisar enviar o formulário.

## Arquivos envolvidos

```
config.php                → configurações (e-mail, SMTP, limites)
process-orcamento.php     → recebe o POST do formulário e envia o e-mail
teste-email.php           → teste manual (apagar depois de usar)
libs/PHPMailer/           → biblioteca de envio de e-mail
data/                     → guarda o controle de limite de envios por IP
.htaccess / .user.ini     → elevam o limite de upload para 20MB
```
