// Função para formatar valor como moeda brasileira
function formatarMoeda(valor) {
    return valor.toLocaleString('pt-BR', {
        style: 'currency',
        currency: 'BRL'
    });
}

// Função para aplicar máscara de moeda
function aplicarMascaraMoeda(input) {
    let valor = input.value.replace(/\D/g, '');
    valor = (valor / 100).toFixed(2) + '';
    valor = valor.replace(".", ",");
    valor = valor.replace(/(\d)(\d{3})(\d{3}),/g, "$1.$2.$3,");
    valor = valor.replace(/(\d)(\d{3}),/g, "$1.$2,");
    input.value = 'R$ ' + valor;
}

// Função para converter valor monetário para decimal
function converterMoedaParaDecimal(valor) {
    return parseFloat(valor.replace(/[R$\s.]/g, '').replace(',', '.')) || 0;
}

// Aplicar máscara de moeda nos campos
document.addEventListener('DOMContentLoaded', function() {
    const camposMoeda = document.querySelectorAll('.currency-input');
    camposMoeda.forEach(function(campo) {
        campo.addEventListener('input', function() {
            aplicarMascaraMoeda(this);
        });
    });
});

// Função para controlar o parcelamento
function toggleParcelamento() {
    const formaPagamento = document.querySelector('input[name="forma_pagamento"]:checked');
    const parcelasContainer = document.getElementById('parcelas-container');
    const parcelasSelect = document.getElementById('parcelas-select');
    
    if (formaPagamento && formaPagamento.value === 'parcelado') {
        parcelasContainer.style.display = 'block';
    } else {
        parcelasContainer.style.display = 'none';
        parcelasSelect.value = '';
        document.getElementById('parcelas-preview').innerHTML = '';
    }
}

// Função para gerar preview das parcelas
function gerarPreviewParcelas() {
    const valorInput = document.getElementById('valor');
    const parcelasSelect = document.getElementById('parcelas-select');
    const dataInput = document.getElementById('data');
    const previewContainer = document.getElementById('parcelas-preview');
    
    const valor = converterMoedaParaDecimal(valorInput.value);
    const numParcelas = parseInt(parcelasSelect.value);
    const dataBase = new Date(dataInput.value);
    
    if (!valor || !numParcelas || !dataBase) {
        previewContainer.innerHTML = '';
        return;
    }
    
    const valorParcela = valor / numParcelas;
    let html = '<div class="parcelas-header">Preview das Parcelas</div>';
    
    for (let i = 1; i <= numParcelas; i++) {
        const dataParcela = new Date(dataBase);
        dataParcela.setMonth(dataParcela.getMonth() + (i - 1));
        
        const dataFormatada = dataParcela.toISOString().split('T')[0];
        
        html += `
            <div class="parcela-item">
                <span class="parcela-numero">${i}ª Parcela</span>
                <div class="parcela-data">
                    <input type="date" name="data_parcela[]" value="${dataFormatada}" class="form-control">
                </div>
                <span class="parcela-valor">${formatarMoeda(valorParcela)}</span>
            </div>
        `;
    }
    
    previewContainer.innerHTML = html;
}

// Event listeners para parcelamento
document.addEventListener('DOMContentLoaded', function() {
    // Radio buttons de forma de pagamento
    const radiosPagamento = document.querySelectorAll('input[name="forma_pagamento"]');
    radiosPagamento.forEach(function(radio) {
        radio.addEventListener('change', toggleParcelamento);
    });
    
    // Select de parcelas
    const parcelasSelect = document.getElementById('parcelas-select');
    if (parcelasSelect) {
        parcelasSelect.addEventListener('change', gerarPreviewParcelas);
    }
    
    // Campo valor
    const valorInput = document.getElementById('valor');
    if (valorInput) {
        valorInput.addEventListener('input', function() {
            if (document.getElementById('parcelas-select').value) {
                gerarPreviewParcelas();
            }
        });
    }
    
    // Campo data
    const dataInput = document.getElementById('data');
    if (dataInput) {
        dataInput.addEventListener('change', function() {
            if (document.getElementById('parcelas-select').value) {
                gerarPreviewParcelas();
            }
        });
    }
});

// Função para carregar subcategorias via AJAX
function carregarSubcategorias(categoriaId) {
    const subcategoriaSelect = document.getElementById('subcategoria_id');
    
    if (!categoriaId) {
        subcategoriaSelect.innerHTML = '<option value="">Selecione uma categoria primeiro</option>';
        return;
    }
    
    fetch('ajax/carregar_subcategorias.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'categoria_id=' + categoriaId
    })
    .then(response => response.json())
    .then(data => {
        subcategoriaSelect.innerHTML = '<option value="">Selecione uma subcategoria</option>';
        data.forEach(function(subcategoria) {
            subcategoriaSelect.innerHTML += `<option value="${subcategoria.id}">${subcategoria.categoria_nome} > ${subcategoria.nome}</option>`;
        });
    })
    .catch(error => {
        console.error('Erro ao carregar subcategorias:', error);
    });
}

// Event listener para mudança de categoria
document.addEventListener('DOMContentLoaded', function() {
    const categoriaSelect = document.getElementById('categoria_id');
    if (categoriaSelect) {
        categoriaSelect.addEventListener('change', function() {
            carregarSubcategorias(this.value);
        });
    }
});

// Função para atualizar o fluxo de caixa
function atualizarFluxoCaixa() {
    fetch('ajax/fluxo_caixa.php')
    .then(response => response.json())
    .then(data => {
        document.getElementById('saldo-atual').textContent = formatarMoeda(data.saldo_atual);
        document.getElementById('entradas-mes').textContent = formatarMoeda(data.entradas_mes);
        document.getElementById('saidas-mes').textContent = formatarMoeda(data.saidas_mes);
        
        // Atualizar cor do saldo
        const saldoElement = document.getElementById('saldo-atual');
        if (data.saldo_atual >= 0) {
            saldoElement.style.color = '#28a745';
        } else {
            saldoElement.style.color = '#dc3545';
        }
    })
    .catch(error => {
        console.error('Erro ao atualizar fluxo de caixa:', error);
    });
}

// Atualizar fluxo de caixa a cada 30 segundos
setInterval(atualizarFluxoCaixa, 30000);

// Função para confirmar exclusão
function confirmarExclusao(mensagem) {
    return confirm(mensagem || 'Tem certeza que deseja excluir este item?');
}

// Função para mostrar/ocultar senha
function toggleSenha(inputId) {
    const input = document.getElementById(inputId);
    const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
    input.setAttribute('type', type);
}

// Validação de formulários
function validarFormulario(formId) {
    const form = document.getElementById(formId);
    const campos = form.querySelectorAll('[required]');
    let valido = true;
    
    campos.forEach(function(campo) {
        if (!campo.value.trim()) {
            campo.style.borderColor = '#dc3545';
            valido = false;
        } else {
            campo.style.borderColor = '#ced4da';
        }
    });
    
    return valido;
}

// Função para mostrar alertas
function mostrarAlerta(mensagem, tipo = 'success') {
    const alerta = document.createElement('div');
    alerta.className = `alert alert-${tipo}`;
    alerta.textContent = mensagem;
    
    const container = document.querySelector('.main-content');
    container.insertBefore(alerta, container.firstChild);
    
    setTimeout(function() {
        alerta.remove();
    }, 5000);
}

// Função para filtrar tabelas
function filtrarTabela(inputId, tabelaId) {
    const input = document.getElementById(inputId);
    const tabela = document.getElementById(tabelaId);
    const linhas = tabela.getElementsByTagName('tr');
    
    input.addEventListener('keyup', function() {
        const filtro = this.value.toLowerCase();
        
        for (let i = 1; i < linhas.length; i++) {
            const linha = linhas[i];
            const texto = linha.textContent.toLowerCase();
            
            if (texto.includes(filtro)) {
                linha.style.display = '';
            } else {
                linha.style.display = 'none';
            }
        }
    });
}
