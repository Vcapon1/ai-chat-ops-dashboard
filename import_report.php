<?php
session_start();
require_once 'includes/db_disparador.php';
require_once 'includes/campanhas/import_validator.php';

// Verificar se usuário está logado
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$report_id = $_GET['id'] ?? null;
if (!$report_id) {
    echo "Relatório não encontrado.";
    exit();
}

// Buscar relatório
$stmt = $pdo_disparador->prepare("SELECT * FROM import_reports WHERE id = ? AND user_id = ?");
$stmt->execute([$report_id, $_SESSION['user_id']]);
$report = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$report) {
    echo "Relatório não encontrado.";
    exit();
}

$erros = json_decode($report['erros'], true) ?? [];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório de Importação - <?= htmlspecialchars($report['nome_lista']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .report-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            margin: 20px;
            padding: 30px;
        }
        .status-card {
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            text-align: center;
        }
        .status-success {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
            color: white;
        }
        .status-warning {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            color: white;
        }
        .stat-box {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin: 10px 0;
            border-left: 4px solid #007bff;
        }
        .error-item {
            background: #fff5f5;
            border: 1px solid #fed7d7;
            border-radius: 5px;
            padding: 10px;
            margin: 5px 0;
        }
        .print-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
        }
        @media print {
            body { background: white !important; }
            .print-btn { display: none; }
        }
    </style>
</head>
<body>
    <div class="report-container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="text-primary">📊 Relatório de Importação</h1>
            <button class="print-btn" onclick="window.print()">🖨️ Imprimir</button>
        </div>

        <!-- Status Geral -->
        <div class="<?= $report['contatos_invalidos'] > 0 ? 'status-warning' : 'status-success' ?>">
            <h3>Lista: <?= htmlspecialchars($report['nome_lista']) ?></h3>
            <p>Importado em: <?= date('d/m/Y H:i:s', strtotime($report['timestamp'])) ?></p>
        </div>

        <!-- Estatísticas -->
        <div class="row">
            <div class="col-md-3">
                <div class="stat-box">
                    <strong>Total de Linhas</strong>
                    <h4 class="text-primary"><?= number_format($report['total_linhas']) ?></h4>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-box" style="border-left-color: #28a745;">
                    <strong>Contatos Válidos</strong>
                    <h4 class="text-success"><?= number_format($report['contatos_validos']) ?></h4>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-box" style="border-left-color: #dc3545;">
                    <strong>Contatos Inválidos</strong>
                    <h4 class="text-danger"><?= number_format($report['contatos_invalidos']) ?></h4>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-box" style="border-left-color: #17a2b8;">
                    <strong>Total Inseridos</strong>
                    <h4 class="text-info"><?= number_format($report['total_inseridos']) ?></h4>
                </div>
            </div>
        </div>

        <!-- Detalhamento dos Erros -->
        <?php if ($report['nomes_vazios'] > 0 || $report['telefones_invalidos'] > 0): ?>
        <div class="mt-4">
            <h4>📋 Detalhamento dos Problemas</h4>
            <div class="row">
                <?php if ($report['nomes_vazios'] > 0): ?>
                <div class="col-md-6">
                    <div class="alert alert-warning">
                        <strong>⚠️ Nomes Vazios:</strong> <?= $report['nomes_vazios'] ?> linhas
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if ($report['telefones_invalidos'] > 0): ?>
                <div class="col-md-6">
                    <div class="alert alert-danger">
                        <strong>📱 Telefones Inválidos:</strong> <?= $report['telefones_invalidos'] ?> linhas
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Lista de Erros Específicos -->
        <?php if (!empty($erros)): ?>
        <div class="mt-4">
            <h4>🔍 Primeiros Erros Encontrados</h4>
            <div class="mb-3">
                <small class="text-muted">Mostrando os primeiros 10 erros para análise</small>
            </div>
            
            <?php foreach ($erros as $erro): ?>
            <div class="error-item">
                <strong>Linha <?= $erro['linha'] ?>:</strong>
                <ul class="mb-0 mt-1">
                    <?php foreach ($erro['erros'] as $descricao): ?>
                    <li><?= htmlspecialchars($descricao) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Taxa de Sucesso -->
        <div class="mt-4">
            <h4>📈 Taxa de Sucesso</h4>
            <?php 
            $taxa_sucesso = $report['total_linhas'] > 0 ? ($report['contatos_validos'] / $report['total_linhas']) * 100 : 0;
            $cor_barra = $taxa_sucesso >= 80 ? 'success' : ($taxa_sucesso >= 60 ? 'warning' : 'danger');
            ?>
            <div class="progress" style="height: 30px;">
                <div class="progress-bar bg-<?= $cor_barra ?>" role="progressbar" 
                     style="width: <?= $taxa_sucesso ?>%" 
                     aria-valuenow="<?= $taxa_sucesso ?>" aria-valuemin="0" aria-valuemax="100">
                    <?= number_format($taxa_sucesso, 1) ?>% de sucesso
                </div>
            </div>
        </div>

        <!-- Recomendações -->
        <?php if ($report['contatos_invalidos'] > 0): ?>
        <div class="mt-4">
            <h4>💡 Recomendações</h4>
            <div class="alert alert-info">
                <ul class="mb-0">
                    <?php if ($report['nomes_vazios'] > 0): ?>
                    <li>Verifique se todas as linhas possuem o campo "Nome" preenchido</li>
                    <?php endif; ?>
                    
                    <?php if ($report['telefones_invalidos'] > 0): ?>
                    <li>Certifique-se de que os telefones estão no formato correto (10-11 dígitos)</li>
                    <li>Remova caracteres especiais dos telefones (parênteses, traços, espaços)</li>
                    <?php endif; ?>
                    
                    <li>Revise o arquivo CSV antes de importar novamente</li>
                    <li>Use a primeira linha para os cabeçalhos (Nome, Telefone)</li>
                </ul>
            </div>
        </div>
        <?php endif; ?>

        <div class="text-center mt-4">
            <button class="btn btn-secondary" onclick="window.close()">Fechar Janela</button>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>