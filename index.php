<?php include 'includes/header.php'; ?>

<h2>Dashboard</h2>

<div class="card">
    <h3 class="card-title">Resumo Financeiro</h3>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px;">
        <div style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; padding: 20px; border-radius: 10px; text-align: center;">
            <h4 style="margin-bottom: 10px; font-size: 16px;">Total de Entradas</h4>
            <div id="total-entradas" style="font-size: 24px; font-weight: bold;">R$ 0,00</div>
            <div style="font-size: 12px; opacity: 0.9;">Este mês</div>
        </div>
        
        <div style="background: linear-gradient(135deg, #dc3545 0%, #e83e8c 100%); color: white; padding: 20px; border-radius: 10px; text-align: center;">
            <h4 style="margin-bottom: 10px; font-size: 16px;">Total de Saídas</h4>
            <div id="total-saidas" style="font-size: 24px; font-weight: bold;">R$ 0,00</div>
            <div style="font-size: 12px; opacity: 0.9;">Este mês</div>
        </div>
        
        <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 10px; text-align: center;">
            <h4 style="margin-bottom: 10px; font-size: 16px;">Saldo do Mês</h4>
            <div id="saldo-mes" style="font-size: 24px; font-weight: bold;">R$ 0,00</div>
            <div style="font-size: 12px; opacity: 0.9;">Entradas - Saídas</div>
        </div>
        
        <div style="background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%); color: white; padding: 20px; border-radius: 10px; text-align: center;">
            <h4 style="margin-bottom: 10px; font-size: 16px;">Contas Pendentes</h4>
            <div id="contas-pendentes" style="font-size: 24px; font-weight: bold;">0</div>
            <div style="font-size: 12px; opacity: 0.9;">A pagar/receber</div>
        </div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px;">
    <div class="card">
        <h3 class="card-title">Gráfico de Entradas vs Saídas</h3>
        <canvas id="grafico-entradas-saidas" width="400" height="200"></canvas>
    </div>
    
    <div class="card">
        <h3 class="card-title">Distribuição por Centro de Custo</h3>
        <canvas id="grafico-centro-custos" width="400" height="200"></canvas>
    </div>
</div>

<div class="card">
    <h3 class="card-title">Últimos Lançamentos</h3>
    
    <div style="overflow-x: auto;">
        <table class="table" id="tabela-lancamentos">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Tipo</th>
                    <th>Descrição</th>
                    <th>Centro de Custo</th>
                    <th>Valor</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody id="tbody-lancamentos">
                <tr>
                    <td colspan="6" style="text-align: center; color: #666;">Carregando...</td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <div style="text-align: center; margin-top: 20px;">
        <a href="lancamentos.php" class="btn">Ver Todos os Lançamentos</a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    carregarDashboard();
    carregarUltimosLancamentos();
});

function carregarDashboard() {
    fetch('ajax/dashboard.php')
    .then(response => response.json())
    .then(data => {
        // Atualizar cards de resumo
        document.getElementById('total-entradas').textContent = formatarMoeda(data.total_entradas);
        document.getElementById('total-saidas').textContent = formatarMoeda(data.total_saidas);
        document.getElementById('saldo-mes').textContent = formatarMoeda(data.saldo_mes);
        document.getElementById('contas-pendentes').textContent = data.contas_pendentes;
        
        // Criar gráfico de entradas vs saídas
        const ctx1 = document.getElementById('grafico-entradas-saidas').getContext('2d');
        new Chart(ctx1, {
            type: 'bar',
            data: {
                labels: data.meses,
                datasets: [{
                    label: 'Entradas',
                    data: data.entradas_por_mes,
                    backgroundColor: 'rgba(40, 167, 69, 0.8)',
                    borderColor: 'rgba(40, 167, 69, 1)',
                    borderWidth: 1
                }, {
                    label: 'Saídas',
                    data: data.saidas_por_mes,
                    backgroundColor: 'rgba(220, 53, 69, 0.8)',
                    borderColor: 'rgba(220, 53, 69, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return formatarMoeda(value);
                            }
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ' + formatarMoeda(context.parsed.y);
                            }
                        }
                    }
                }
            }
        });
        
        // Criar gráfico de centro de custos
        if (data.centro_custos.length > 0) {
            const ctx2 = document.getElementById('grafico-centro-custos').getContext('2d');
            new Chart(ctx2, {
                type: 'doughnut',
                data: {
                    labels: data.centro_custos.map(item => item.nome),
                    datasets: [{
                        data: data.centro_custos.map(item => item.total),
                        backgroundColor: [
                            '#667eea',
                            '#764ba2',
                            '#28a745',
                            '#dc3545',
                            '#ffc107',
                            '#17a2b8',
                            '#6f42c1',
                            '#e83e8c'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.label + ': ' + formatarMoeda(context.parsed);
                                }
                            }
                        }
                    }
                }
            });
        }
    })
    .catch(error => {
        console.error('Erro ao carregar dashboard:', error);
    });
}

function carregarUltimosLancamentos() {
    fetch('ajax/ultimos_lancamentos.php')
    .then(response => response.json())
    .then(data => {
        const tbody = document.getElementById('tbody-lancamentos');
        
        if (data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; color: #666;">Nenhum lançamento encontrado</td></tr>';
        } else {
            let html = '';
            data.forEach(function(lancamento) {
                const tipoClass = lancamento.tipo === 'entrada' ? 'success' : 'danger';
                const tipoTexto = lancamento.tipo === 'entrada' ? 'Entrada' : 'Saída';
                const statusTexto = lancamento.forma_pagamento === 'a_vista' ? 'À Vista' : `${lancamento.parcelas}x`;
                
                html += `
                    <tr>
                        <td>${new Date(lancamento.data).toLocaleDateString('pt-BR')}</td>
                        <td><span style="color: ${lancamento.tipo === 'entrada' ? '#28a745' : '#dc3545'}; font-weight: bold;">${tipoTexto}</span></td>
                        <td>${lancamento.categoria_nome} > ${lancamento.subcategoria_nome}</td>
                        <td>${lancamento.centro_custo_nome}</td>
                        <td style="font-weight: bold;">${formatarMoeda(parseFloat(lancamento.valor))}</td>
                        <td>${statusTexto}</td>
                    </tr>
                `;
            });
            tbody.innerHTML = html;
        }
    })
    .catch(error => {
        console.error('Erro ao carregar últimos lançamentos:', error);
    });
}
</script>

<?php include 'includes/footer.php'; ?>
