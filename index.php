<?php

declare(strict_types=1);

use App\Config;
use App\DocumentService;
use App\MailerService;

require __DIR__ . '/vendor/autoload.php';

$message = null;
$error = null;
$document = null;
$data = ['name' => '', 'email' => '', 'amount' => '', 'description' => ''];
$env = Config::all(__DIR__ . '/.env');
$mailer = new MailerService($env);
$smtpConfigured = $mailer->isConfigured();

if (isset($_GET['download']) && isset($_GET['code']) && is_string($_GET['code'])) {
    $code = preg_replace('/[^A-Z0-9-]/i', '', $_GET['code']);
    $paths = [
        __DIR__ . '/storage/' . $code . '.pdf',
        sys_get_temp_dir() . '/' . $code . '.pdf',
    ];

    foreach ($paths as $path) {
        if (is_file($path)) {
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . basename($path) . '"');
            readfile($path);
            exit;
        }
    }

    http_response_code(404);
    echo 'Arquivo não encontrado.';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'name' => trim((string) ($_POST['name'] ?? '')),
        'email' => trim((string) ($_POST['email'] ?? '')),
        'amount' => trim((string) ($_POST['amount'] ?? '')),
        'description' => trim((string) ($_POST['description'] ?? '')),
    ];

    if ($data['name'] === '' || !filter_var($data['email'], FILTER_VALIDATE_EMAIL) || $data['amount'] === '') {
        $error = 'Preencha o nome, um e-mail válido e o valor.';
    } else {
        try {
            $document = (new DocumentService(__DIR__ . '/storage'))->create($data);
            $document['download'] = '?download=1&code=' . urlencode($document['code']);
            if ($smtpConfigured) {
                $mailer->send($data['name'], $data['email'], $document['pdf'], $document['code']);
                $message = 'Documento gerado e enviado por e-mail com sucesso.';
            } else {
                $message = 'Documento e QR Code gerados. Configure o SMTP para habilitar o envio por e-mail.';
            }
        } catch (Throwable $exception) {
            $error = 'Não foi possível gerar o documento: ' . $exception->getMessage();
        }
    }
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>DocFlow | Novo documento</title>
    <style>
        :root { --navy:#111c34; --blue:#3461f5; --blue-dark:#2549c5; --bg:#f5f7fb; --line:#e7ebf3; --text:#18233a; --muted:#71809a; --success:#0e9f6e; }
        * { box-sizing:border-box; }
        body { margin:0; background:var(--bg); color:var(--text); font-family:Inter,Segoe UI,Arial,sans-serif; font-size:14px; }
        .app { min-height:100vh; display:grid; grid-template-columns:250px 1fr; }
        .sidebar { padding:28px 16px; background:var(--navy); color:#b5c0d7; display:flex; flex-direction:column; }
        .brand { display:flex; gap:11px; align-items:center; padding:0 12px 31px; color:#fff; font-size:21px; font-weight:750; letter-spacing:-.6px; }
        .brand-mark { width:32px; height:32px; display:grid; place-items:center; border-radius:10px; background:#4f74ff; box-shadow:0 7px 18px #0004; }
        .brand-mark svg { width:19px; height:19px; }
        .nav-label { margin:5px 12px 11px; color:#697894; font-size:10px; font-weight:800; letter-spacing:1.1px; text-transform:uppercase; }
        .nav { display:grid; gap:5px; }
        .nav a { display:flex; align-items:center; gap:12px; padding:11px 12px; color:#b5c0d7; border-radius:9px; text-decoration:none; font-weight:600; }
        .nav a:hover { color:#fff; background:#ffffff10; }
        .nav a.active { color:#fff; background:#4269f5; box-shadow:0 6px 15px #0002; }
        .nav svg { width:18px; height:18px; fill:none; stroke:currentColor; stroke-width:1.8; stroke-linecap:round; stroke-linejoin:round; }
        .nav .badge { margin-left:auto; min-width:21px; padding:2px 6px; border-radius:10px; background:#ffffff23; color:#fff; font-size:11px; text-align:center; }
        .sidebar-footer { margin-top:auto; padding:20px 12px 0; border-top:1px solid #ffffff15; }
        .profile { display:flex; gap:10px; align-items:center; color:#fff; font-weight:650; }
        .avatar { width:31px; height:31px; border-radius:50%; display:grid; place-items:center; background:#e8edff; color:#3757c9; font-size:12px; }
        .profile small { display:block; margin-top:2px; color:#8492aa; font-weight:500; }
        .content { min-width:0; }
        .topbar { height:76px; display:flex; justify-content:space-between; align-items:center; padding:0 42px; background:#fff; border-bottom:1px solid var(--line); }
        .crumb { color:var(--muted); font-size:13px; } .crumb strong { color:var(--text); }
        .top-actions { display:flex; align-items:center; gap:16px; }
        .status { display:flex; gap:7px; align-items:center; padding:7px 10px; border:1px solid var(--line); border-radius:20px; color:#64728a; font-size:12px; font-weight:650; }
        .dot { width:7px; height:7px; border-radius:50%; background:<?= $smtpConfigured ? '#19b27b' : '#e5a833' ?>; }
        .icon-button { width:36px; height:36px; display:grid; place-items:center; border:1px solid var(--line); border-radius:9px; background:#fff; color:#506078; }
        .icon-button svg { width:18px; fill:none; stroke:currentColor; stroke-width:1.8; stroke-linecap:round; stroke-linejoin:round; }
        .workspace { max-width:1190px; margin:0 auto; padding:39px 42px 48px; }
        .page-heading { display:flex; justify-content:space-between; align-items:end; gap:20px; margin-bottom:28px; }
        h1 { margin:0 0 8px; font-size:27px; letter-spacing:-.8px; } .page-heading p { margin:0; color:var(--muted); }
        .date { color:var(--muted); font-size:12px; }
        .overview { display:grid; grid-template-columns:1.65fr repeat(2, .8fr); gap:18px; margin-bottom:23px; }
        .metric { min-height:118px; padding:20px; background:#fff; border:1px solid var(--line); border-radius:13px; }
        .metric.main { position:relative; overflow:hidden; color:#fff; border:0; background:linear-gradient(116deg,#315cf1,#6f7efd); }
        .metric.main:after { content:""; position:absolute; width:150px; height:150px; right:-52px; top:-52px; border:25px solid #ffffff18; border-radius:50%; }
        .metric-label { display:block; margin-bottom:11px; color:var(--muted); font-size:12px; font-weight:650; } .main .metric-label { color:#dfe6ff; }
        .metric strong { display:block; font-size:25px; letter-spacing:-.7px; } .metric small { display:block; margin-top:7px; color:#8c9ab0; } .main small { color:#e7ebff; }
        .layout { display:grid; grid-template-columns:minmax(0,1fr) 300px; gap:22px; align-items:start; }
        .panel { background:#fff; border:1px solid var(--line); border-radius:14px; box-shadow:0 8px 24px #1a294408; }
        .panel-head { display:flex; justify-content:space-between; align-items:center; padding:24px 26px 19px; border-bottom:1px solid var(--line); }
        .panel-head h2 { margin:0; font-size:16px; letter-spacing:-.2px; } .panel-head p { margin:5px 0 0; color:var(--muted); font-size:12px; }
        .step { padding:5px 8px; border-radius:5px; color:#5268b7; background:#edf1ff; font-size:11px; font-weight:800; }
        form { padding:25px 26px 28px; } .fields { display:grid; grid-template-columns:1fr 1fr; gap:18px; }
        .field.full { grid-column:1 / -1; } label { display:block; margin-bottom:8px; color:#36435a; font-size:12px; font-weight:750; }
        input,textarea { width:100%; border:1px solid #dce2ed; border-radius:8px; outline:none; padding:12px 13px; color:var(--text); background:#fff; font:inherit; transition:border .2s,box-shadow .2s; }
        input::placeholder,textarea::placeholder { color:#a1acbd; } input:focus,textarea:focus { border-color:#6b88ff; box-shadow:0 0 0 3px #436afb18; }
        textarea { min-height:105px; resize:vertical; } .form-foot { display:flex; justify-content:space-between; align-items:center; gap:15px; margin-top:23px; }
        .hint { color:#8390a4; font-size:11px; line-height:1.45; } button,.download { display:inline-flex; align-items:center; justify-content:center; gap:8px; padding:12px 16px; border:0; border-radius:8px; background:var(--blue); color:#fff; font:inherit; font-size:13px; font-weight:750; text-decoration:none; cursor:pointer; box-shadow:0 7px 14px #3b63e637; transition:background .2s,transform .2s; }
        button:hover,.download:hover { background:var(--blue-dark); transform:translateY(-1px); } button svg,.download svg { width:16px; fill:none; stroke:currentColor; stroke-width:2; stroke-linecap:round; stroke-linejoin:round; }
        .side-panel { padding:23px; } .side-panel h2 { margin:0; font-size:15px; } .checklist { display:grid; gap:17px; margin:23px 0 25px; }
        .check { display:flex; gap:11px; align-items:flex-start; color:#516078; font-size:12px; line-height:1.4; } .check i { flex:0 0 20px; height:20px; display:grid; place-items:center; border-radius:50%; background:#e8f8f1; color:var(--success); font-style:normal; font-size:12px; font-weight:bold; }
        .notice { padding:13px; border-radius:8px; background:#f5f7fd; color:#6b7890; font-size:11px; line-height:1.5; } .notice strong { color:#42506a; }
        .alert { display:flex; align-items:flex-start; gap:10px; padding:13px 15px; margin:0 26px 2px; border-radius:8px; font-size:12px; line-height:1.45; } .alert:before { content:'✓'; font-weight:800; } .success { background:#eaf9f1; color:#08734b; } .error { background:#fff0f0; color:#b23434; } .error:before { content:'!'; }
        .result { margin:0 26px 26px; padding:17px; border:1px solid #dbe5ff; border-radius:9px; background:#f7f9ff; } .result strong { font-size:12px; } .code { color:#3657d1; font-family:Consolas,monospace; font-size:12px; font-weight:bold; } .result .download { margin-top:14px; padding:9px 12px; font-size:12px; box-shadow:none; }
        @media (max-width:900px) { .app { grid-template-columns:68px 1fr; } .sidebar { padding:25px 10px; align-items:center; } .brand { padding:0 0 28px; } .brand span,.nav-label,.nav a span,.nav .badge,.sidebar-footer { display:none; } .nav a { padding:11px; } .topbar,.workspace { padding-left:26px; padding-right:26px; } .overview { grid-template-columns:1fr 1fr; } .metric.main { grid-column:1 / -1; } .layout { grid-template-columns:1fr; } .side-panel { display:none; } }
        @media (max-width:590px) { .app { display:block; } .sidebar { display:none; } .topbar { height:62px; padding:0 18px; } .status { display:none; } .workspace { padding:28px 18px; } .page-heading { display:block; } .date { margin-top:10px; } .overview { grid-template-columns:1fr; } .metric.main { grid-column:auto; } .fields { grid-template-columns:1fr; } .field.full { grid-column:auto; } .form-foot { align-items:flex-start; flex-direction:column; } button { width:100%; } }
    </style>
</head>
<body>
<div class="app">
    <aside class="sidebar">
        <div class="brand"><span class="brand-mark"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M7 3h7l4 4v14H7z"/><path d="M14 3v5h5M10 13h5M10 17h5"/></svg></span><span>DocFlow</span></div>
        <p class="nav-label">Principal</p>
        <nav class="nav" aria-label="Navegação principal">
            <a href="#inicio"><svg viewBox="0 0 24 24"><path d="m3 10 9-7 9 7v10a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1z"/><path d="M9 21v-7h6v7"/></svg><span>Visão geral</span></a>
            <a class="active" href="#novo-documento"><svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M12 18v-6M9 15h6"/></svg><span>Novo documento</span></a>
            <a href="#historico"><svg viewBox="0 0 24 24"><path d="M3 12a9 9 0 1 0 3-6.7"/><path d="M3 4v5h5M12 7v5l3 2"/></svg><span>Histórico</span><b class="badge">0</b></a>
            <a href="#configuracoes"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .34 1.88l.06.06-2 2-.06-.06a1.7 1.7 0 0 0-1.88-.34 1.7 1.7 0 0 0-1 1.55V20h-2.8v-.09a1.7 1.7 0 0 0-1-1.55 1.7 1.7 0 0 0-1.88.34l-.06.06-2-2 .06-.06A1.7 1.7 0 0 0 7.56 15a1.7 1.7 0 0 0-1.55-1H6v-2.8h.01a1.7 1.7 0 0 0 1.55-1A1.7 1.7 0 0 0 7.22 8.3l-.06-.06 2-2 .06.06a1.7 1.7 0 0 0 1.88.34 1.7 1.7 0 0 0 1-1.55V5h2.8v.09a1.7 1.7 0 0 0 1 1.55 1.7 1.7 0 0 0 1.88-.34l.06-.06 2 2-.06.06a1.7 1.7 0 0 0-.34 1.88 1.7 1.7 0 0 0 1.55 1H21V14h-.01a1.7 1.7 0 0 0-1.59 1z"/></svg><span>Configurações</span></a>
        </nav>
        <div class="sidebar-footer"><div class="profile"><span class="avatar">DF</span><span>DocFlow Admin<small>Administrador</small></span></div></div>
    </aside>

    <main class="content" id="inicio">
        <header class="topbar"><div class="crumb">Documentos <span>/</span> <strong>Novo documento</strong></div><div class="top-actions"><span class="status"><i class="dot"></i><?= $smtpConfigured ? 'SMTP configurado' : 'SMTP pendente' ?></span><button class="icon-button" type="button" aria-label="Notificações"><svg viewBox="0 0 24 24"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/></svg></button></div></header>
        <section class="workspace" id="novo-documento">
            <div class="page-heading"><div><h1>Gerar novo documento</h1><p>Preencha os dados para criar um comprovante seguro e verificável.</p></div><span class="date">Painel DocFlow</span></div>
            <div class="overview"><article class="metric main"><span class="metric-label">Fluxo de documentos</span><strong>Pronto para emitir</strong><small>PDF profissional + QR Code de validação</small></article><article class="metric"><span class="metric-label">Documentos emitidos</span><strong>0</strong><small>Nenhum registro nesta sessão</small></article><article class="metric"><span class="metric-label">Canal de e-mail</span><strong><?= $smtpConfigured ? 'Ativo' : 'Pendente' ?></strong><small><?= $smtpConfigured ? 'Envio automático habilitado' : 'Configure o arquivo .env' ?></small></article></div>
            <div class="layout">
                <section class="panel">
                    <div class="panel-head"><div><h2>Dados do comprovante</h2><p>As informações serão inseridas no PDF.</p></div><span class="step">ETAPA 1 DE 1</span></div>
                    <?php if ($message): ?><div class="alert success"><?= e($message) ?></div><?php endif; ?>
                    <?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>
                    <form method="post">
                        <div class="fields">
                            <div class="field"><label for="name">Nome do beneficiário</label><input id="name" name="name" value="<?= e($data['name']) ?>" placeholder="Ex.: Maria da Silva" required></div>
                            <div class="field"><label for="email">E-mail para envio</label><input id="email" type="email" name="email" value="<?= e($data['email']) ?>" placeholder="nome@empresa.com" required></div>
                            <div class="field"><label for="amount">Valor do documento</label><input id="amount" name="amount" placeholder="Ex.: 1.250,00" value="<?= e($data['amount']) ?>" required></div>
                            <div class="field"><label for="reference">Referência</label><input id="reference" value="Gerada automaticamente" disabled></div>
                            <div class="field full"><label for="description">Descrição</label><textarea id="description" name="description" placeholder="Descreva brevemente a finalidade deste documento."><?= e($data['description']) ?></textarea></div>
                        </div>
                        <div class="form-foot"><span class="hint">Um código único e um QR Code serão incluídos<br>automaticamente no arquivo PDF.</span><button type="submit">Gerar documento <svg viewBox="0 0 24 24"><path d="M5 12h14M13 6l6 6-6 6"/></svg></button></div>
                    </form>
                    <?php if ($document): ?><div class="result"><strong>Documento criado · Código de validação:</strong> <span class="code"><?= e($document['code']) ?></span><br><a class="download" href="<?= e($document['download'] ?? '?download=1') ?>" target="_blank">Baixar PDF <svg viewBox="0 0 24 24"><path d="M12 3v12M7 10l5 5 5-5M5 21h14"/></svg></a></div><?php endif; ?>
                </section>
                <aside class="panel side-panel"><h2>Antes de emitir</h2><div class="checklist"><div class="check"><i>✓</i><span>O PDF será criado automaticamente.</span></div><div class="check"><i>✓</i><span>Um QR Code permite validar o documento.</span></div><div class="check"><i><?= $smtpConfigured ? '✓' : '!' ?></i><span><?= $smtpConfigured ? 'O comprovante será enviado por e-mail.' : 'O envio por e-mail precisa de SMTP.' ?></span></div></div><div class="notice"><strong>Dica de configuração</strong><br>Para ativar e-mails, preencha as credenciais SMTP no arquivo <code>.env</code>.</div></aside>
            </div>
        </section>
    </main>
</div>
</body>
</html>
