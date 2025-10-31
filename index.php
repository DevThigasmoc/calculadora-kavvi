<?php
declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';

require_auth();

$user = current_user();
$service = new ProposalService($appConfig);
$proposalRepo = new ProposalRepository();
$message = null;
$error = null;
$currentProposal = null;
$proposalItems = [];
$shareUrl = null;

function build_share_url(string $token): string
{
    $host = $_SERVER['HTTP_HOST'] ?? '';
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    if ($host) {
        return $scheme . '://' . $host . '/propostas/ver.php?token=' . urlencode($token);
    }
    return '/propostas/ver.php?token=' . urlencode($token);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token($_POST['_token'] ?? '');
    $action = $_POST['action'] ?? 'save_proposal';
    try {
        if ($action === 'save_proposal') {
            $result = $service->saveProposal($_POST, $user);
            $message = 'Proposta salva com sucesso!';
            $currentProposal = $proposalRepo->findById($result['id']);
            $proposalItems = $proposalRepo->findItems($result['id']);
            $shareUrl = build_share_url($currentProposal['share_token']);
        } elseif ($action === 'accept_proposal') {
            $service->acceptProposal((int) $_POST['proposal_id'], $user);
            $message = 'Proposta aceita. Você já pode gerar o contrato.';
            $currentProposal = $proposalRepo->findById((int) $_POST['proposal_id']);
            $proposalItems = $proposalRepo->findItems((int) $_POST['proposal_id']);
            $shareUrl = build_share_url($currentProposal['share_token']);
        } elseif ($action === 'generate_contract') {
            $result = $service->generateContract((int) $_POST['proposal_id'], $user);
            $message = 'Contrato gerado com sucesso!';
            $currentProposal = $proposalRepo->findById((int) $_POST['proposal_id']);
            $proposalItems = $proposalRepo->findItems((int) $_POST['proposal_id']);
            $shareUrl = build_share_url($currentProposal['share_token']);
        }
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
} elseif (isset($_GET['proposal'])) {
    $proposalId = (int) $_GET['proposal'];
    $proposal = $proposalRepo->findById($proposalId);
    if ($proposal && ($user['perfil'] !== 'vendedor' || (int) $proposal['user_id'] === (int) $user['id'])) {
        $currentProposal = $proposal;
        $proposalItems = $proposalRepo->findItems($proposalId);
        $shareUrl = build_share_url($proposal['share_token']);
    }
}

$csrfToken = csrf_token();
$plans = $appConfig['plans'];
$addons = $appConfig['addons'];
$recentProposals = $proposalRepo->list([], $user);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>KAVVI Calculadora Comercial</title>
    <link rel="stylesheet" href="<?= asset('assets/css/style.css'); ?>">
</head>
<body>
<header class="app-header">
    <div class="logo">KAVVI Calculadora</div>
    <nav>
        <a href="/admin/index.php">Painel</a>
        <a href="/auth/logout.php">Sair</a>
    </nav>
</header>
<main class="layout">
    <section class="calculator">
        <h1>Gerador de Propostas</h1>
        <?php if ($message): ?>
            <div class="alert alert-success"><?= sanitize($message); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><?= sanitize($error); ?></div>
        <?php endif; ?>
        <form method="post" id="proposal-form">
            <input type="hidden" name="_token" value="<?= sanitize($csrfToken); ?>">
            <input type="hidden" name="proposal_id" value="<?= sanitize((string) ($currentProposal['id'] ?? '')); ?>">
            <input type="hidden" name="share_token" value="<?= sanitize((string) ($currentProposal['share_token'] ?? '')); ?>">
            <input type="hidden" name="status" value="<?= sanitize((string) ($currentProposal['status'] ?? 'rascunho')); ?>">

            <section class="card">
                <h2>Dados do Cliente</h2>
                <div class="grid">
                    <label>
                        Tipo de Pessoa
                        <select name="cliente_pessoa_tipo">
                            <option value="PJ" <?= (($currentProposal['client_pessoa_tipo'] ?? 'PJ') === 'PJ') ? 'selected' : ''; ?>>Pessoa Jurídica</option>
                            <option value="PF" <?= (($currentProposal['client_pessoa_tipo'] ?? '') === 'PF') ? 'selected' : ''; ?>>Pessoa Física</option>
                        </select>
                    </label>
                    <label>
                        CPF/CNPJ
                        <input type="text" name="cliente_doc" value="<?= sanitize((string) ($currentProposal['client_doc'] ?? '')); ?>" required>
                    </label>
                    <label>
                        Razão/Nome Fantasia
                        <input type="text" name="cliente_empresa" value="<?= sanitize((string) ($currentProposal['client_empresa_nome'] ?? '')); ?>">
                    </label>
                    <label>
                        Contato Responsável
                        <input type="text" name="cliente_contato" value="<?= sanitize((string) ($currentProposal['client_contato_nome'] ?? '')); ?>" required>
                    </label>
                    <label>
                        Telefone
                        <input type="text" name="cliente_telefone" value="<?= sanitize((string) ($currentProposal['client_telefone'] ?? '')); ?>">
                    </label>
                    <label>
                        CEP
                        <input type="text" name="cliente_cep" value="<?= sanitize((string) ($currentProposal['client_cep'] ?? '')); ?>">
                    </label>
                    <label>
                        Endereço
                        <input type="text" name="cliente_endereco" value="<?= sanitize((string) ($currentProposal['client_endereco'] ?? '')); ?>">
                    </label>
                    <label>
                        Número
                        <input type="text" name="cliente_numero" value="<?= sanitize((string) ($currentProposal['client_numero'] ?? '')); ?>">
                    </label>
                    <label>
                        Complemento
                        <input type="text" name="cliente_complemento" value="<?= sanitize((string) ($currentProposal['client_complemento'] ?? '')); ?>">
                    </label>
                    <label>
                        Bairro
                        <input type="text" name="cliente_bairro" value="<?= sanitize((string) ($currentProposal['client_bairro'] ?? '')); ?>">
                    </label>
                    <label>
                        Cidade
                        <input type="text" name="cliente_cidade" value="<?= sanitize((string) ($currentProposal['client_cidade'] ?? '')); ?>">
                    </label>
                    <label>
                        UF
                        <input type="text" name="cliente_uf" value="<?= sanitize((string) ($currentProposal['client_uf'] ?? '')); ?>" maxlength="2">
                    </label>
                </div>
            </section>

            <section class="card">
                <h2>Plano e Mensalidade</h2>
                <div class="grid">
                    <label>
                        Plano
                        <select name="plano_key" id="plano_key">
                            <?php foreach ($plans as $key => $plan): ?>
                                <option value="<?= sanitize($key); ?>" <?= (($currentProposal['plano_key'] ?? 'kavvi_start') === $key) ? 'selected' : ''; ?>><?= sanitize($plan['label']); ?> - <?= format_currency((float) $plan['base_price']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>
                        Qtde. Usuários
                        <input type="number" name="usuarios_qtd" id="usuarios_qtd" min="1" value="<?= sanitize((string) ($currentProposal['usuarios_qtd'] ?? 1)); ?>">
                    </label>
                    <label>
                        Descontos Mensalidade
                        <input type="number" step="0.01" name="descontos_mensal" id="descontos_mensal" value="<?= sanitize((string) ($currentProposal['descontos_mensal'] ?? 0)); ?>">
                    </label>
                    <label>
                        Descontos Periféricos
                        <input type="number" step="0.01" name="descontos_addons" id="descontos_addons" value="<?= sanitize((string) ($currentProposal['descontos_addons'] ?? 0)); ?>">
                    </label>
                    <label>
                        % Pague em Dia
                        <input type="number" step="0.01" name="pague_em_dia_percent" id="pague_em_dia_percent" value="<?= sanitize((string) ($currentProposal['pague_em_dia_percent'] ?? 0)); ?>">
                    </label>
                    <label>
                        Mensalidade Base
                        <input type="number" step="0.01" name="mensalidade_base" id="mensalidade_base" value="<?= sanitize((string) ($currentProposal['mensalidade_base'] ?? 0)); ?>">
                    </label>
                    <label>
                        Mensalidade com Pague em Dia
                        <input type="number" step="0.01" name="mensalidade_pague" id="mensalidade_pague" value="<?= sanitize((string) ($currentProposal['mensalidade_pague'] ?? 0)); ?>">
                    </label>
                    <label>
                        1º Vencimento Mensalidade
                        <input type="date" name="primeiro_venc_mensal" value="<?= sanitize((string) ($currentProposal['primeiro_venc_mensal'] ?? '')); ?>">
                    </label>
                </div>
            </section>

            <section class="card">
                <h2>Implantação</h2>
                <div class="grid">
                    <label>
                        Tipo
                        <select name="implantacao_tipo">
                            <option value="única" <?= (($currentProposal['implantacao_tipo'] ?? 'única') === 'única') ? 'selected' : ''; ?>>Pagamento Único</option>
                            <option value="parcelada" <?= (($currentProposal['implantacao_tipo'] ?? '') === 'parcelada') ? 'selected' : ''; ?>>Parcelada</option>
                        </select>
                    </label>
                    <label>
                        Valor Implantação
                        <input type="number" step="0.01" name="implantacao_valor" id="implantacao_valor" value="<?= sanitize((string) ($currentProposal['implantacao_valor'] ?? 0)); ?>">
                    </label>
                    <label>
                        Qtde. Parcelas
                        <input type="number" name="implantacao_parcelas" value="<?= sanitize((string) ($currentProposal['implantacao_parcelas'] ?? 1)); ?>" min="1">
                    </label>
                    <label>
                        1º Vencimento Implantação
                        <input type="date" name="primeiro_venc_implant" value="<?= sanitize((string) ($currentProposal['primeiro_venc_implant'] ?? '')); ?>">
                    </label>
                </div>
            </section>

            <section class="card">
                <h2>Periféricos e Serviços</h2>
                <div id="items-container">
                    <table class="items-table">
                        <thead>
                            <tr><th>Item</th><th>Por usuário?</th><th>Valor Unitário</th><th>Qtd.</th><th>Subtotal</th><th></th></tr>
                        </thead>
                        <tbody id="items-body">
                            <?php if ($proposalItems): ?>
                                <?php foreach ($proposalItems as $index => $item): ?>
                                    <tr>
                                        <td><input type="text" name="item_label[]" value="<?= sanitize($item['item_label']); ?>" required></td>
                                        <td class="center"><input type="checkbox" name="item_per_user[<?= $index; ?>]" value="1" <?= $item['per_user'] ? 'checked' : ''; ?>></td>
                                        <td><input type="number" step="0.01" name="item_unit_price[]" value="<?= sanitize((string) $item['unit_price']); ?>" required></td>
                                        <td><input type="number" step="0.01" name="item_qty[]" value="<?= sanitize((string) $item['qty']); ?>" required></td>
                                        <td><input type="number" step="0.01" name="item_subtotal[]" value="<?= sanitize((string) $item['subtotal']); ?>" required></td>
                                        <td><button type="button" class="btn-link remove-item">Remover</button></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr class="empty"><td colspan="6">Nenhum item adicionado.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    <button type="button" class="btn-secondary" id="add-item">Adicionar Item</button>
                </div>
            </section>

            <section class="card">
                <h2>Texto da Proposta</h2>
                <textarea name="texto_proposta" rows="6" placeholder="Detalhes, condições e observações da proposta."><?= sanitize((string) ($currentProposal['texto_proposta'] ?? '')); ?></textarea>
            </section>

            <div class="actions">
                <button type="submit" name="action" value="save_proposal" class="btn-primary">Salvar Proposta</button>
                <?php if ($currentProposal && in_array($currentProposal['status'], ['rascunho', 'enviada', 'aceita'], true)): ?>
                    <button type="submit" name="action" value="accept_proposal" class="btn-secondary">Aceitar Proposta</button>
                <?php endif; ?>
                <?php if ($currentProposal && $currentProposal['status'] === 'aceita'): ?>
                    <button type="submit" name="action" value="generate_contract" class="btn-secondary">Gerar Contrato</button>
                <?php endif; ?>
            </div>
        </form>
        <?php if ($currentProposal): ?>
            <div class="card info">
                <h2>Compartilhar</h2>
                <p>Status atual: <strong><?= sanitize($currentProposal['status']); ?></strong></p>
                <?php if ($shareUrl): ?>
                    <p>Link público: <input type="text" readonly value="<?= sanitize($shareUrl); ?>" class="share-link"></p>
                <?php endif; ?>
                <?php if (!empty($currentProposal['pdf_path'])): ?>
                    <p>Último HTML gerado: <code><?= sanitize(basename($currentProposal['pdf_path'])); ?></code></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </section>
    <aside class="sidebar">
        <div class="card">
            <h2>Minhas Propostas</h2>
            <ul class="proposal-list">
                <?php foreach ($recentProposals as $proposal): ?>
                    <li>
                        <a href="?proposal=<?= (int) $proposal['id']; ?>">#<?= (int) $proposal['id']; ?> - <?= sanitize($proposal['empresa_nome']); ?></a>
                        <span class="badge badge-status-<?= sanitize($proposal['status']); ?>"><?= sanitize($proposal['status']); ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <div class="card">
            <h2>Adicionar periférico rápido</h2>
            <ul class="addon-list">
                <?php foreach ($addons as $addon): ?>
                    <li data-label="<?= sanitize($addon['label']); ?>" data-price="<?= sanitize((string) $addon['unit_price']); ?>">
                        <span><?= sanitize($addon['label']); ?></span>
                        <span><?= format_currency((float) $addon['unit_price']); ?></span>
                        <button type="button" class="btn-link add-addon">Adicionar</button>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </aside>
</main>
<script>
document.getElementById('add-item').addEventListener('click', function () {
    addItemRow('', false, '', '', '');
});

document.querySelectorAll('.add-addon').forEach(function (button) {
    button.addEventListener('click', function () {
        var parent = button.closest('li');
        addItemRow(parent.dataset.label, false, parent.dataset.price, 1, parent.dataset.price);
    });
});

function addItemRow(label, perUser, unitPrice, qty, subtotal) {
    var body = document.getElementById('items-body');
    var empty = body.querySelector('.empty');
    if (empty) empty.remove();
    var index = body.querySelectorAll('tr').length;
    var tr = document.createElement('tr');
    tr.innerHTML = '<td><input type="text" name="item_label[]" required></td>' +
        '<td class="center"><input type="checkbox" name="item_per_user[' + index + ']" value="1"></td>' +
        '<td><input type="number" step="0.01" name="item_unit_price[]" required></td>' +
        '<td><input type="number" step="0.01" name="item_qty[]" required></td>' +
        '<td><input type="number" step="0.01" name="item_subtotal[]" required></td>' +
        '<td><button type="button" class="btn-link remove-item">Remover</button></td>';
    body.appendChild(tr);
    var inputs = tr.querySelectorAll('input');
    inputs[0].value = label || '';
    if (perUser) inputs[1].checked = true;
    inputs[2].value = unitPrice || '';
    inputs[3].value = qty || '';
    inputs[4].value = subtotal || '';
    tr.querySelector('.remove-item').addEventListener('click', function () {
        tr.remove();
        if (!body.querySelector('tr')) {
            var emptyRow = document.createElement('tr');
            emptyRow.classList.add('empty');
            emptyRow.innerHTML = '<td colspan="6">Nenhum item adicionado.</td>';
            body.appendChild(emptyRow);
        }
    });
}

document.querySelectorAll('.remove-item').forEach(function (button) {
    button.addEventListener('click', function () {
        var row = button.closest('tr');
        row.remove();
    });
});
</script>
</body>
</html>
