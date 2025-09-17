# Documentação Técnica - Sistema Financeiro Netmai

## 📋 Visão Geral

O Sistema Financeiro Netmai é uma aplicação web completa desenvolvida em PHP/MySQL para gestão financeira empresarial. O sistema oferece controle total sobre entradas, saídas, parcelamentos, fluxo de caixa e relatórios analíticos.

## 🏗️ Arquitetura do Sistema

### Estrutura de Diretórios

```
sistema_financeiro/
├── css/                    # Arquivos de estilo
│   └── style.css          # CSS principal responsivo
├── js/                     # Scripts JavaScript
│   └── script.js          # Funcionalidades client-side
├── images/                 # Imagens e ícones
│   ├── logo_netmai.png    # Logo da empresa
│   └── icon_*.png         # Ícones do sistema
├── includes/               # Arquivos de configuração
│   ├── config.php         # Configurações e conexão DB
│   ├── header.php         # Cabeçalho padrão
│   └── footer.php         # Rodapé com sidebar
├── ajax/                   # Endpoints AJAX
│   ├── fluxo_caixa.php    # Dados do fluxo de caixa
│   ├── dashboard.php      # Dados do dashboard
│   ├── relatorios.php     # Dados dos relatórios
│   ├── contas_pagar.php   # Contas a pagar
│   ├── contas_receber.php # Contas a receber
│   ├── carregar_subcategorias.php # Subcategorias por categoria
│   ├── ultimos_lancamentos.php # Últimos lançamentos
│   └── exportar_relatorio.php # Exportação PDF
├── *.php                   # Páginas principais
├── install.php            # Instalador automático
├── schema.sql             # Estrutura do banco
├── demo_data.sql          # Dados de demonstração
└── README.md              # Documentação do usuário
```

### Tecnologias Utilizadas

- **Backend**: PHP 7.4+ com PDO
- **Banco de Dados**: MySQL 5.7+ com InnoDB
- **Frontend**: HTML5, CSS3, JavaScript ES6
- **Gráficos**: Chart.js 3.x
- **Responsividade**: CSS Grid e Flexbox
- **Segurança**: Password hashing, prepared statements

## 🗄️ Modelo de Dados

### Relacionamentos

```
usuarios (1) -----> (N) lancamentos
centro_custos (1) -> (N) lancamentos
categorias (1) ----> (N) subcategorias
subcategorias (1) -> (N) lancamentos
lancamentos (1) ---> (N) parcelas
```

### Tabelas Principais

#### `usuarios`
- **Propósito**: Controle de acesso ao sistema
- **Campos principais**: id, nome, email, senha
- **Índices**: PRIMARY KEY (id), UNIQUE (email)

#### `centro_custos`
- **Propósito**: Classificação organizacional dos lançamentos
- **Campos principais**: id, nome
- **Relacionamentos**: 1:N com lancamentos

#### `categorias` e `subcategorias`
- **Propósito**: Classificação hierárquica dos lançamentos
- **Estrutura**: Categoria pai -> Subcategorias filhas
- **Relacionamentos**: categorias 1:N subcategorias 1:N lancamentos

#### `lancamentos`
- **Propósito**: Registro das transações financeiras
- **Campos principais**: 
  - tipo (entrada/saida)
  - valor (decimal 10,2)
  - data
  - forma_pagamento (a_vista/parcelado)
  - parcelas (número de parcelas se aplicável)
- **Relacionamentos**: N:1 com usuarios, centro_custos, subcategorias

#### `parcelas`
- **Propósito**: Controle detalhado de parcelamentos
- **Campos principais**:
  - numero_parcela
  - valor (decimal 10,2)
  - data_vencimento
  - pago (boolean)
- **Relacionamentos**: N:1 com lancamentos

## 🔧 Funcionalidades Técnicas

### Sistema de Autenticação

```php
// Verificação de login
function verificarLogin() {
    if (!isset($_SESSION['usuario_id'])) {
        header('Location: login.php');
        exit();
    }
}

// Hash de senhas
$senha_hash = password_hash($senha, PASSWORD_DEFAULT);
$valido = password_verify($senha, $usuario['senha']);
```

### Conexão com Banco de Dados

```php
// PDO com tratamento de erros
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", 
                   DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Erro na conexão: " . $e->getMessage());
}
```

### Sistema de Parcelamento

#### Lógica de Criação de Parcelas

```php
if ($forma_pagamento === 'parcelado' && $parcelas > 1) {
    $valor_parcela = $valor / $parcelas;
    $data_base = new DateTime($data);
    
    for ($i = 1; $i <= $parcelas; $i++) {
        $data_parcela = clone $data_base;
        $data_parcela->modify('+' . ($i-1) . ' month');
        
        $stmt = $pdo->prepare("
            INSERT INTO parcelas (lancamento_id, numero_parcela, valor, data_vencimento) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$lancamento_id, $i, $valor_parcela, $data_parcela->format('Y-m-d')]);
    }
}
```

#### Preview JavaScript das Parcelas

```javascript
function gerarPreviewParcelas() {
    const valor = converterMoedaParaDecimal(valorInput.value);
    const numParcelas = parseInt(parcelasSelect.value);
    const dataBase = new Date(dataInput.value);
    
    const valorParcela = valor / numParcelas;
    
    for (let i = 1; i <= numParcelas; i++) {
        const dataParcela = new Date(dataBase);
        dataParcela.setMonth(dataParcela.getMonth() + (i - 1));
        // Gerar HTML do preview
    }
}
```

### Fluxo de Caixa em Tempo Real

#### Cálculo do Saldo Atual

```php
$stmt = $pdo->prepare("
    SELECT 
        SUM(CASE WHEN tipo = 'entrada' THEN valor ELSE 0 END) as total_entradas,
        SUM(CASE WHEN tipo = 'saida' THEN valor ELSE 0 END) as total_saidas
    FROM lancamentos 
    WHERE usuario_id = ? AND data <= CURDATE()
");
```

#### Projeção com Parcelas Futuras

```php
$stmt = $pdo->prepare("
    SELECT 
        SUM(CASE WHEN l.tipo = 'entrada' THEN p.valor ELSE 0 END) as entradas_futuras,
        SUM(CASE WHEN l.tipo = 'saida' THEN p.valor ELSE 0 END) as saidas_futuras
    FROM parcelas p
    JOIN lancamentos l ON p.lancamento_id = l.id
    WHERE l.usuario_id = ? AND p.data_vencimento BETWEEN ? AND ? AND p.pago = 0
");
```

### Sistema de Relatórios

#### Evolução Mensal

```php
$stmt = $pdo->prepare("
    SELECT 
        DATE_FORMAT(data, '%Y-%m') as mes,
        SUM(CASE WHEN tipo = 'entrada' THEN valor ELSE 0 END) as entradas,
        SUM(CASE WHEN tipo = 'saida' THEN valor ELSE 0 END) as saidas
    FROM lancamentos 
    WHERE usuario_id = ? AND data BETWEEN ? AND ?
    GROUP BY DATE_FORMAT(data, '%Y-%m')
    ORDER BY mes
");
```

#### Análise de Sazonalidade

```php
// Por mês do ano
$stmt = $pdo->prepare("
    SELECT 
        MONTH(data) as mes,
        AVG(CASE WHEN tipo = 'entrada' THEN valor ELSE -valor END) as media
    FROM lancamentos 
    WHERE usuario_id = ? AND data BETWEEN ? AND ?
    GROUP BY MONTH(data)
");

// Por dia da semana
$stmt = $pdo->prepare("
    SELECT 
        DAYOFWEEK(data) as dia_semana,
        AVG(CASE WHEN tipo = 'entrada' THEN valor ELSE -valor END) as media
    FROM lancamentos 
    WHERE usuario_id = ? AND data BETWEEN ? AND ?
    GROUP BY DAYOFWEEK(data)
");
```

## 🎨 Interface e UX

### Design Responsivo

```css
/* Layout principal */
.content-wrapper {
    display: flex;
    flex: 1;
    gap: 20px;
    padding: 20px;
}

/* Responsividade */
@media (max-width: 768px) {
    .content-wrapper {
        flex-direction: column;
    }
    
    .sidebar {
        width: 100%;
        order: -1;
    }
}
```

### Máscaras de Entrada

```javascript
// Máscara de moeda brasileira
function aplicarMascaraMoeda(input) {
    let valor = input.value.replace(/\D/g, '');
    valor = (valor / 100).toFixed(2) + '';
    valor = valor.replace(".", ",");
    valor = valor.replace(/(\d)(\d{3})(\d{3}),/g, "$1.$2.$3,");
    valor = valor.replace(/(\d)(\d{3}),/g, "$1.$2,");
    input.value = 'R$ ' + valor;
}
```

### Gráficos Interativos

```javascript
// Configuração Chart.js
new Chart(ctx, {
    type: 'line',
    data: {
        labels: data.meses,
        datasets: [{
            label: 'Entradas',
            data: data.entradas,
            borderColor: '#28a745',
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        plugins: {
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return formatarMoeda(context.parsed.y);
                    }
                }
            }
        }
    }
});
```

## 🔒 Segurança

### Proteções Implementadas

1. **SQL Injection**: Uso exclusivo de prepared statements
2. **XSS**: Sanitização com `htmlspecialchars()`
3. **CSRF**: Validação de sessão em todas as operações
4. **Senhas**: Hash com `password_hash()` e `PASSWORD_DEFAULT`
5. **Sessões**: Controle rigoroso de autenticação

### Validações

```php
// Validação de entrada
$valor = converterMoeda($_POST['valor'] ?? '');
if (empty($tipo) || empty($centro_custo_id) || empty($valor)) {
    $erro = 'Campos obrigatórios não preenchidos.';
}

// Sanitização de saída
echo htmlspecialchars($usuario['nome']);
```

## 📊 Performance

### Otimizações Implementadas

1. **Índices de Banco**: Chaves estrangeiras indexadas
2. **Consultas Otimizadas**: JOINs eficientes, LIMIT em listagens
3. **Cache Client-side**: Dados do fluxo de caixa atualizados a cada 30s
4. **Paginação**: Limitação de resultados (50 lançamentos por página)

### Consultas Críticas

```php
// Otimizada com LIMIT e índices
$stmt = $pdo->prepare("
    SELECT l.*, cc.nome as centro_custo_nome, c.nome as categoria_nome, s.nome as subcategoria_nome
    FROM lancamentos l
    JOIN centro_custos cc ON l.centro_custo_id = cc.id
    JOIN subcategorias s ON l.subcategoria_id = s.id
    JOIN categorias c ON s.categoria_id = c.id
    WHERE l.usuario_id = ?
    ORDER BY l.data DESC, l.id DESC
    LIMIT 50
");
```

## 🧪 Testes e Debugging

### Dados de Teste

O arquivo `demo_data.sql` contém:
- 21 lançamentos de exemplo
- Parcelas em diferentes estados (pagas/pendentes)
- Distribuição temporal (4 meses)
- Diferentes categorias e centros de custo

### Logs de Erro

```php
// Tratamento de erros PDO
try {
    $stmt->execute($params);
} catch (PDOException $e) {
    error_log("Erro SQL: " . $e->getMessage());
    $erro = 'Erro no sistema. Tente novamente.';
}
```

## 🚀 Deploy e Manutenção

### Requisitos de Servidor

- **PHP**: 7.4 ou superior
- **MySQL**: 5.7 ou superior
- **Apache**: mod_rewrite habilitado
- **Extensões PHP**: PDO, PDO_MySQL, mbstring

### Configuração de Produção

```php
// config.php para produção
define('DB_HOST', 'localhost');
define('DB_NAME', 'financeiro_ademar');
define('DB_USER', 'usuario_producao');
define('DB_PASS', 'senha_segura');

// Desabilitar exibição de erros
ini_set('display_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);
```

### Backup e Manutenção

```sql
-- Backup completo
mysqldump -u root -p financeiro_ademar > backup_financeiro.sql

-- Limpeza de dados antigos (opcional)
DELETE FROM parcelas WHERE lancamento_id IN (
    SELECT id FROM lancamentos WHERE data < DATE_SUB(NOW(), INTERVAL 2 YEAR)
);
```

## 📈 Possíveis Melhorias

### Funcionalidades Futuras

1. **API REST**: Endpoints para integração externa
2. **Relatórios Avançados**: Análise preditiva, ML
3. **Notificações**: Email/SMS para vencimentos
4. **Multi-empresa**: Suporte a múltiplas empresas
5. **Importação**: CSV, OFX, outros formatos
6. **Mobile App**: Aplicativo nativo
7. **Integração Bancária**: Open Banking

### Otimizações Técnicas

1. **Cache Redis**: Cache de consultas frequentes
2. **CDN**: Distribuição de assets estáticos
3. **Compressão**: Gzip, minificação
4. **Lazy Loading**: Carregamento sob demanda
5. **WebSockets**: Atualizações em tempo real

---

**Desenvolvido pela equipe Netmai - Documentação Técnica v1.0**
