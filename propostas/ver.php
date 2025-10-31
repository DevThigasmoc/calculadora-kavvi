<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

$token = $_GET['token'] ?? '';
$repo = new ProposalRepository();
$proposal = $token ? $repo->findByShareToken($token) : null;

if (!$proposal) {
    http_response_code(404);
    exit('Proposta não encontrada ou expirada.');
}

$items = $repo->findItems((int) $proposal['id']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Proposta #<?= (int) $proposal['id']; ?> - KAVVI</title>
    <link rel="stylesheet" href="<?= asset('assets/css/style.css'); ?>">
</head>
<body class="public-proposal">
    <div class="public-container">
        <header>
            <h1>Proposta Comercial</h1>
            <p><strong>Cliente:</strong> <?= sanitize(($proposal['client_empresa_nome'] ?: $proposal['client_contato_nome'])); ?></p>
            <p><strong>Gerada por:</strong> <?= sanitize($proposal['vendedor_nome']); ?> (<?= sanitize($proposal['vendedor_email']); ?>)</p>
            <p><strong>Status:</strong> <?= sanitize($proposal['status']); ?></p>
        </header>
        <section>
            <h2>Resumo</h2>
            <ul>
                <li><strong>Plano:</strong> <?= sanitize($appConfig['plans'][$proposal['plano_key']]['label'] ?? $proposal['plano_key']); ?></li>
                <li><strong>Usuários:</strong> <?= (int) $proposal['usuarios_qtd']; ?></li>
                <li><strong>Mensalidade Base:</strong> <?= format_currency((float) $proposal['mensalidade_base']); ?></li>
                <li><strong>Mensalidade com Pague em Dia:</strong> <?= format_currency((float) $proposal['mensalidade_pague']); ?></li>
                <li><strong>Implantação:</strong> <?= format_currency((float) $proposal['implantacao_valor']); ?> (<?= sanitize($proposal['implantacao_tipo']); ?>)</li>
            </ul>
        </section>
        <?php if ($items): ?>
            <section>
                <h2>Periféricos e Serviços</h2>
                <table class="items-table">
                    <thead>
                        <tr><th>Item</th><th>Por usuário?</th><th>Qtd.</th><th>Subtotal</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td><?= sanitize($item['item_label']); ?></td>
                                <td><?= $item['per_user'] ? 'Sim' : 'Não'; ?></td>
                                <td><?= sanitize((string) $item['qty']); ?></td>
                                <td><?= format_currency((float) $item['subtotal']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </section>
        <?php endif; ?>
        <section>
            <h2>Detalhes</h2>
            <p><?= nl2br(sanitize($proposal['texto_proposta'])); ?></p>
        </section>
        <footer>
            <p>Gerado em <?= date('d/m/Y H:i', strtotime($proposal['created_at'])); ?> • Proposta #<?= (int) $proposal['id']; ?></p>
        </footer>
    </div>
</body>
</html>
