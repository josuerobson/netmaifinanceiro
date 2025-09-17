            </main>
            
            <aside class="sidebar">
                <div class="fluxo-caixa">
                    <h3 class="fluxo-title">
                        <img src="images/icon_fluxo_caixa.png" alt="Fluxo de Caixa" style="width: 20px; height: 20px; margin-right: 10px;">
                        Fluxo de Caixa
                    </h3>
                    
                    <div class="saldo-atual">
                        <div class="saldo-label">Saldo do Período</div>
                        <div class="saldo-valor" id="saldo-atual">R$ 0,00</div>
                    </div>
                    
                    <div class="card">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                            <span>Entradas do Mês:</span>
                            <span id="entradas-mes" style="color: #28a745; font-weight: bold;">R$ 0,00</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                            <span>Saídas do Mês:</span>
                            <span id="saidas-mes" style="color: #dc3545; font-weight: bold;">R$ 0,00</span>
                        </div>
                        <hr>
                        <div style="display: flex; justify-content: space-between;">
                            <span><strong>Saldo Projetado:</strong></span>
                            <span id="saldo-projetado" style="font-weight: bold;">R$ 0,00</span>
                        </div>
                    </div>
                    
                    <div class="card">
                        <h4 style="margin-bottom: 15px; font-size: 16px;">Filtro de Período</h4>
                        <div class="form-group">
                            <label for="data-inicio">Data Início:</label>
                            <input type="date" id="data-inicio" class="form-control" 
                                   value="<?php echo date('Y-m-01'); ?>">
                        </div>
                        <div class="form-group">
                            <label for="data-fim">Data Fim:</label>
                            <input type="date" id="data-fim" class="form-control" 
                                   value="<?php echo date('Y-m-t'); ?>">
                        </div>
                        <button type="button" class="btn" onclick="atualizarFluxoCaixa()" style="width: 100%; font-size: 14px; padding: 8px;">
                            Atualizar
                        </button>
                    </div>
                    
                    <div class="card">
                        <h4 style="margin-bottom: 15px; font-size: 16px;">Contas a Pagar</h4>
                        <div id="contas-pagar">
                            <div style="text-align: center; color: #666; font-size: 14px;">
                                Carregando...
                            </div>
                        </div>
                    </div>
                    
                    <div class="card">
                        <h4 style="margin-bottom: 15px; font-size: 16px;">Contas a Receber</h4>
                        <div id="contas-receber">
                            <div style="text-align: center; color: #666; font-size: 14px;">
                                Carregando...
                            </div>
                        </div>
                    </div>
                </div>
            </aside>
        </div>
    </div>
    
    <script src="js/script.js"></script>
    <script>
        // Carregar dados do fluxo de caixa ao carregar a página
        document.addEventListener('DOMContentLoaded', function() {
            atualizarFluxoCaixa();
            carregarContasPagar();
            carregarContasReceber();
        });
        
        // Função para carregar contas a pagar
        function carregarContasPagar() {
            fetch('ajax/contas_pagar.php')
            .then(response => response.json())
            .then(data => {
                const container = document.getElementById('contas-pagar');
                if (data.length === 0) {
                    container.innerHTML = '<div style="text-align: center; color: #666; font-size: 14px;">Nenhuma conta a pagar</div>';
                } else {
                    let html = '';
                    data.forEach(function(conta) {
                        const vencimento = new Date(conta.data_vencimento);
                        const hoje = new Date();
                        const diasVencimento = Math.ceil((vencimento - hoje) / (1000 * 60 * 60 * 24));
                        
                        let corStatus = '#666';
                        if (diasVencimento < 0) {
                            corStatus = '#dc3545'; // Vencido
                        } else if (diasVencimento <= 7) {
                            corStatus = '#ffc107'; // Vence em breve
                        }
                        
                        const dataFormatada = new Date(conta.data_vencimento + 'T00:00:00').toLocaleDateString('pt-BR');
                        
                        html += `
                            <div style="border-left: 3px solid ${corStatus}; padding-left: 10px; margin-bottom: 10px; font-size: 12px;">
                                <div style="font-weight: bold;">${formatarMoeda(parseFloat(conta.valor))}</div>
                                <div style="color: #666;">${dataFormatada}</div>
                                <div style="color: #666; font-size: 11px;">${conta.centro_custo}</div>
                                <div style="color: #666; font-size: 10px;">${conta.parcela_info}</div>
                            </div>
                        `;
                    });
                    container.innerHTML = html;
                }
            })
            .catch(error => {
                console.error('Erro ao carregar contas a pagar:', error);
            });
        }
        
        // Função para carregar contas a receber
        function carregarContasReceber() {
            fetch('ajax/contas_receber.php')
            .then(response => response.json())
            .then(data => {
                const container = document.getElementById('contas-receber');
                if (data.length === 0) {
                    container.innerHTML = '<div style="text-align: center; color: #666; font-size: 14px;">Nenhuma conta a receber</div>';
                } else {
                    let html = '';
                    data.forEach(function(conta) {
                        const vencimento = new Date(conta.data_vencimento);
                        const hoje = new Date();
                        const diasVencimento = Math.ceil((vencimento - hoje) / (1000 * 60 * 60 * 24));
                        
                        let corStatus = '#28a745';
                        if (diasVencimento < 0) {
                            corStatus = '#dc3545'; // Vencido
                        } else if (diasVencimento <= 7) {
                            corStatus = '#ffc107'; // Vence em breve
                        }
                        
                        const dataFormatada = new Date(conta.data_vencimento + 'T00:00:00').toLocaleDateString('pt-BR');
                        
                        html += `
                            <div style="border-left: 3px solid ${corStatus}; padding-left: 10px; margin-bottom: 10px; font-size: 12px;">
                                <div style="font-weight: bold;">${formatarMoeda(parseFloat(conta.valor))}</div>
                                <div style="color: #666;">${dataFormatada}</div>
                                <div style="color: #666; font-size: 11px;">${conta.centro_custo}</div>
                                <div style="color: #666; font-size: 10px;">${conta.parcela_info}</div>
                            </div>
                        `;
                    });
                    container.innerHTML = html;
                }
            })
            .catch(error => {
                console.error('Erro ao carregar contas a receber:', error);
            });
        }
        
        // Função atualizada para carregar fluxo de caixa com filtros
        function atualizarFluxoCaixa() {
            const dataInicio = document.getElementById('data-inicio').value;
            const dataFim = document.getElementById('data-fim').value;
            
            const params = new URLSearchParams({
                data_inicio: dataInicio,
                data_fim: dataFim
            });
            
            fetch('ajax/fluxo_caixa.php?' + params)
            .then(response => response.json())
            .then(data => {
                // Usar saldo_periodo ao invés de saldo_atual para mostrar o saldo do período filtrado
                const saldoPeriodo = data.saldo_periodo || (data.entradas_periodo - data.saidas_periodo);
                
                document.getElementById('saldo-atual').textContent = formatarMoeda(saldoPeriodo);
                document.getElementById('entradas-mes').textContent = formatarMoeda(data.entradas_periodo);
                document.getElementById('saidas-mes').textContent = formatarMoeda(data.saidas_periodo);
                document.getElementById('saldo-projetado').textContent = formatarMoeda(data.saldo_projetado);
                
                // Atualizar cor do saldo
                const saldoElement = document.getElementById('saldo-atual');
                const saldoProjetadoElement = document.getElementById('saldo-projetado');
                
                if (saldoPeriodo >= 0) {
                    saldoElement.style.color = '#28a745';
                } else {
                    saldoElement.style.color = '#dc3545';
                }
                
                if (data.saldo_projetado >= 0) {
                    saldoProjetadoElement.style.color = '#28a745';
                } else {
                    saldoProjetadoElement.style.color = '#dc3545';
                }
            })
            .catch(error => {
                console.error('Erro ao atualizar fluxo de caixa:', error);
            });
        }
    </script>
</body>
</html>
