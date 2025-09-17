<?php include 'includes/header.php'; ?>

<h2>Relatórios e Análises</h2>

<div class="card">
    <h3 class="card-title">Filtros de Período</h3>
    
    <div style="display: flex; gap: 15px; align-items: end; flex-wrap: wrap;">
        <div class="form-group" style="margin-bottom: 0;">
            <label for="periodo-inicio">Data Início:</label>
            <input type="date" id="periodo-inicio" class="form-control" 
                   value="<?php echo date('Y-m-01', strtotime('-5 months')); ?>">
        </div>
        
        <div class="form-group" style="margin-bottom: 0;">
            <label for="periodo-fim">Data Fim:</label>
            <input type="date" id="periodo-fim" class="form-control" 
                   value="<?php echo date('Y-m-t'); ?>">
        </div>
        
        <button type="button" class="btn" onclick="atualizarRelatorios()" style="height: fit-content;">
            Atualizar Relatórios
        </button>
        
        <button type="button" class="btn btn-secondary" onclick="exportarRelatorio()" style="height: fit-content;">
            Exportar PDF
        </button>
    </div>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px;">
    <div style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; padding: 20px; border-radius: 10px; text-align: center;">
        <h4 style="margin-bottom: 10px; font-size: 16px;">Total de Entradas</h4>
        <div id="relatorio-entradas" style="font-size: 24px; font-weight: bold;">R$ 0,00</div>
        <div style="font-size: 12px; opacity: 0.9;">No período selecionado</div>
    </div>
    
    <div style="background: linear-gradient(135deg, #dc3545 0%, #e83e8c 100%); color: white; padding: 20px; border-radius: 10px; text-align: center;">
        <h4 style="margin-bottom: 10px; font-size: 16px;">Total de Saídas</h4>
        <div id="relatorio-saidas" style="font-size: 24px; font-weight: bold;">R$ 0,00</div>
        <div style="font-size: 12px; opacity: 0.9;">No período selecionado</div>
    </div>
    
    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 10px; text-align: center;">
        <h4 style="margin-bottom: 10px; font-size: 16px;">Saldo do Período</h4>
        <div id="relatorio-saldo" style="font-size: 24px; font-weight: bold;">R$ 0,00</div>
        <div style="font-size: 12px; opacity: 0.9;">Entradas - Saídas</div>
    </div>
    
    <div style="background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%); color: white; padding: 20px; border-radius: 10px; text-align: center;">
        <h4 style="margin-bottom: 10px; font-size: 16px;">Média Mensal</h4>
        <div id="relatorio-media" style="font-size: 24px; font-weight: bold;">R$ 0,00</div>
        <div style="font-size: 12px; opacity: 0.9;">Saldo médio por mês</div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px;">
    <div class="card">
        <h3 class="card-title">Evolução Mensal</h3>
        <canvas id="grafico-evolucao" width="400" height="300"></canvas>
    </div>
    
    <div class="card">
        <h3 class="card-title">Distribuição por Tipo</h3>
        <canvas id="grafico-tipos" width="400" height="300"></canvas>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px;">
    <div class="card">
        <h3 class="card-title">Top 10 Categorias</h3>
        <canvas id="grafico-categorias" width="400" height="300"></canvas>
    </div>
    
    <div class="card">
        <h3 class="card-title">Centro de Custos</h3>
        <canvas id="grafico-centros" width="400" height="300"></canvas>
    </div>
</div>

<div class="card">
    <h3 class="card-title">Análise de Tendências</h3>
    <canvas id="grafico-tendencias" width="800" height="400"></canvas>
</div>

<div class="card">
    <h3 class="card-title">Relatório Detalhado por Categoria</h3>
    
    <div style="overflow-x: auto;">
        <table class="table" id="tabela-categorias-detalhada">
            <thead>
                <tr>
                    <th>Categoria</th>
                    <th>Subcategoria</th>
                    <th>Entradas</th>
                    <th>Saídas</th>
                    <th>Saldo</th>
                    <th>% do Total</th>
                </tr>
            </thead>
            <tbody id="tbody-categorias-detalhada">
                <tr>
                    <td colspan="6" style="text-align: center; color: #666;">Carregando...</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <h3 class="card-title">Análise de Sazonalidade</h3>
    
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
        <div>
            <h4 style="margin-bottom: 15px;">Por Mês do Ano</h4>
            <canvas id="grafico-sazonalidade-mes" width="400" height="300"></canvas>
        </div>
        
        <div>
            <h4 style="margin-bottom: 15px;">Por Dia da Semana</h4>
            <canvas id="grafico-sazonalidade-dia" width="400" height="300"></canvas>
        </div>
    </div>
</div>

<div class="card">
    <h3 class="card-title">Projeções e Metas</h3>
    
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
        <div>
            <h4 style="margin-bottom: 15px;">Projeção Linear (próximos 3 meses)</h4>
            <canvas id="grafico-projecao" width="400" height="300"></canvas>
        </div>
        
        <div>
            <h4 style="margin-bottom: 15px;">Comparativo com Período Anterior</h4>
            <canvas id="grafico-comparativo" width="400" height="300"></canvas>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns"></script>
<script>
let graficos = {};

document.addEventListener('DOMContentLoaded', function() {
    atualizarRelatorios();
});

function atualizarRelatorios() {
    const dataInicio = document.getElementById('periodo-inicio').value;
    const dataFim = document.getElementById('periodo-fim').value;
    
    if (!dataInicio || !dataFim) {
        alert('Por favor, selecione o período para análise.');
        return;
    }
    
    const params = new URLSearchParams({
        data_inicio: dataInicio,
        data_fim: dataFim
    });
    
    fetch('ajax/relatorios.php?' + params)
    .then(response => response.json())
    .then(data => {
        atualizarCards(data);
        criarGraficos(data);
        atualizarTabelaDetalhada(data);
    })
    .catch(error => {
        console.error('Erro ao carregar relatórios:', error);
        alert('Erro ao carregar relatórios. Tente novamente.');
    });
}

function atualizarCards(data) {
    document.getElementById('relatorio-entradas').textContent = formatarMoeda(data.resumo.total_entradas);
    document.getElementById('relatorio-saidas').textContent = formatarMoeda(data.resumo.total_saidas);
    document.getElementById('relatorio-saldo').textContent = formatarMoeda(data.resumo.saldo_periodo);
    document.getElementById('relatorio-media').textContent = formatarMoeda(data.resumo.media_mensal);
}

function criarGraficos(data) {
    // Destruir gráficos existentes
    Object.values(graficos).forEach(grafico => {
        if (grafico) grafico.destroy();
    });
    
    // Gráfico de Evolução Mensal
    const ctx1 = document.getElementById('grafico-evolucao').getContext('2d');
    graficos.evolucao = new Chart(ctx1, {
        type: 'line',
        data: {
            labels: data.evolucao_mensal.meses,
            datasets: [{
                label: 'Entradas',
                data: data.evolucao_mensal.entradas,
                borderColor: '#28a745',
                backgroundColor: 'rgba(40, 167, 69, 0.1)',
                tension: 0.4
            }, {
                label: 'Saídas',
                data: data.evolucao_mensal.saidas,
                borderColor: '#dc3545',
                backgroundColor: 'rgba(220, 53, 69, 0.1)',
                tension: 0.4
            }, {
                label: 'Saldo',
                data: data.evolucao_mensal.saldo,
                borderColor: '#667eea',
                backgroundColor: 'rgba(102, 126, 234, 0.1)',
                tension: 0.4
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
    
    // Gráfico de Distribuição por Tipo
    const ctx2 = document.getElementById('grafico-tipos').getContext('2d');
    graficos.tipos = new Chart(ctx2, {
        type: 'doughnut',
        data: {
            labels: ['Entradas', 'Saídas'],
            datasets: [{
                data: [data.resumo.total_entradas, data.resumo.total_saidas],
                backgroundColor: ['#28a745', '#dc3545']
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
    
    // Gráfico de Top 10 Categorias
    const ctx3 = document.getElementById('grafico-categorias').getContext('2d');
    graficos.categorias = new Chart(ctx3, {
        type: 'bar',
        data: {
            labels: data.top_categorias.map(item => item.nome),
            datasets: [{
                label: 'Valor Total',
                data: data.top_categorias.map(item => item.total),
                backgroundColor: '#667eea'
            }]
        },
        options: {
            responsive: true,
            indexAxis: 'y',
            scales: {
                x: {
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
                            return formatarMoeda(context.parsed.x);
                        }
                    }
                }
            }
        }
    });
    
    // Gráfico de Centro de Custos
    const ctx4 = document.getElementById('grafico-centros').getContext('2d');
    graficos.centros = new Chart(ctx4, {
        type: 'pie',
        data: {
            labels: data.centro_custos.map(item => item.nome),
            datasets: [{
                data: data.centro_custos.map(item => item.total),
                backgroundColor: [
                    '#667eea', '#764ba2', '#28a745', '#dc3545', 
                    '#ffc107', '#17a2b8', '#6f42c1', '#e83e8c'
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
    
    // Gráfico de Tendências
    const ctx5 = document.getElementById('grafico-tendencias').getContext('2d');
    graficos.tendencias = new Chart(ctx5, {
        type: 'line',
        data: {
            labels: data.tendencias.datas,
            datasets: [{
                label: 'Saldo Acumulado',
                data: data.tendencias.saldo_acumulado,
                borderColor: '#667eea',
                backgroundColor: 'rgba(102, 126, 234, 0.1)',
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
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
                            return 'Saldo: ' + formatarMoeda(context.parsed.y);
                        }
                    }
                }
            }
        }
    });
    
    // Gráfico de Sazonalidade por Mês
    const ctx6 = document.getElementById('grafico-sazonalidade-mes').getContext('2d');
    graficos.sazonalidade_mes = new Chart(ctx6, {
        type: 'radar',
        data: {
            labels: ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'],
            datasets: [{
                label: 'Média por Mês',
                data: data.sazonalidade.por_mes,
                borderColor: '#667eea',
                backgroundColor: 'rgba(102, 126, 234, 0.2)'
            }]
        },
        options: {
            responsive: true,
            scales: {
                r: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return formatarMoeda(value);
                        }
                    }
                }
            }
        }
    });
    
    // Gráfico de Sazonalidade por Dia da Semana
    const ctx7 = document.getElementById('grafico-sazonalidade-dia').getContext('2d');
    graficos.sazonalidade_dia = new Chart(ctx7, {
        type: 'bar',
        data: {
            labels: ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'],
            datasets: [{
                label: 'Média por Dia',
                data: data.sazonalidade.por_dia_semana,
                backgroundColor: '#28a745'
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
            }
        }
    });
    
    // Gráfico de Projeção
    const ctx8 = document.getElementById('grafico-projecao').getContext('2d');
    graficos.projecao = new Chart(ctx8, {
        type: 'line',
        data: {
            labels: data.projecao.meses,
            datasets: [{
                label: 'Histórico',
                data: data.projecao.historico,
                borderColor: '#667eea',
                backgroundColor: 'rgba(102, 126, 234, 0.1)'
            }, {
                label: 'Projeção',
                data: data.projecao.projetado,
                borderColor: '#ffc107',
                backgroundColor: 'rgba(255, 193, 7, 0.1)',
                borderDash: [5, 5]
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    ticks: {
                        callback: function(value) {
                            return formatarMoeda(value);
                        }
                    }
                }
            }
        }
    });
    
    // Gráfico Comparativo
    const ctx9 = document.getElementById('grafico-comparativo').getContext('2d');
    graficos.comparativo = new Chart(ctx9, {
        type: 'bar',
        data: {
            labels: ['Período Anterior', 'Período Atual'],
            datasets: [{
                label: 'Entradas',
                data: [data.comparativo.anterior.entradas, data.comparativo.atual.entradas],
                backgroundColor: '#28a745'
            }, {
                label: 'Saídas',
                data: [data.comparativo.anterior.saidas, data.comparativo.atual.saidas],
                backgroundColor: '#dc3545'
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
            }
        }
    });
}

function atualizarTabelaDetalhada(data) {
    const tbody = document.getElementById('tbody-categorias-detalhada');
    
    if (data.detalhado_categorias.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; color: #666;">Nenhum dado encontrado para o período selecionado</td></tr>';
        return;
    }
    
    let html = '';
    data.detalhado_categorias.forEach(function(item) {
        const saldo = item.entradas - item.saidas;
        const percentual = data.resumo.total_entradas + data.resumo.total_saidas > 0 
            ? ((Math.abs(saldo) / (data.resumo.total_entradas + data.resumo.total_saidas)) * 100).toFixed(1)
            : 0;
        
        html += `
            <tr>
                <td><strong>${item.categoria_nome}</strong></td>
                <td>${item.subcategoria_nome}</td>
                <td style="color: #28a745; font-weight: bold;">${formatarMoeda(item.entradas)}</td>
                <td style="color: #dc3545; font-weight: bold;">${formatarMoeda(item.saidas)}</td>
                <td style="color: ${saldo >= 0 ? '#28a745' : '#dc3545'}; font-weight: bold;">${formatarMoeda(saldo)}</td>
                <td>${percentual}%</td>
            </tr>
        `;
    });
    
    tbody.innerHTML = html;
}

function exportarRelatorio() {
    const dataInicio = document.getElementById('periodo-inicio').value;
    const dataFim = document.getElementById('periodo-fim').value;
    
    if (!dataInicio || !dataFim) {
        alert('Por favor, selecione o período para exportação.');
        return;
    }
    
    window.open(`ajax/exportar_relatorio.php?data_inicio=${dataInicio}&data_fim=${dataFim}`, '_blank');
}
</script>

<?php include 'includes/footer.php'; ?>
