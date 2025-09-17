# Sistema Financeiro Netmai

Sistema completo de gestão financeira desenvolvido em PHP/MySQL para ambiente XAMPP, com interface responsiva e recursos avançados de controle financeiro.

## 🚀 Características Principais

- **Sistema de Login e Cadastro de Usuários**
- **Gestão de Centro de Custos**
- **Categorias e Subcategorias Hierárquicas**
- **Lançamentos Financeiros com Sistema de Parcelamento**
- **Fluxo de Caixa em Tempo Real**
- **Relatórios Gráficos Avançados**
- **Interface Totalmente Responsiva**
- **Dashboard Interativo**

## 📋 Pré-requisitos

- XAMPP (Apache + MySQL + PHP 7.4+)
- Navegador web moderno
- Resolução mínima: 1024x768

## 🔧 Instalação

### 1. Preparar o Ambiente

1. Instale o XAMPP em seu computador
2. Inicie os serviços Apache e MySQL no painel de controle do XAMPP

### 2. Configurar o Banco de Dados

1. Acesse o phpMyAdmin (http://localhost/phpmyadmin)
2. Execute o script SQL fornecido no arquivo `schema.sql` para criar o banco de dados e tabelas
3. O banco será criado com o nome `financeiro_ademar`

### 3. Instalar o Sistema

1. Copie todos os arquivos do sistema para a pasta `htdocs/sistema_financeiro/` do XAMPP
2. Certifique-se de que a estrutura de pastas está correta:
   ```
   htdocs/sistema_financeiro/
   ├── css/
   ├── js/
   ├── images/
   ├── includes/
   ├── pages/
   ├── ajax/
   └── arquivos PHP principais
   ```

### 4. Configurar Conexão

1. Abra o arquivo `includes/config.php`
2. Verifique as configurações de conexão com o banco:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'financeiro_ademar');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   ```

### 5. Acessar o Sistema

1. Abra seu navegador
2. Acesse: `http://localhost/sistema_financeiro/`
3. Faça o cadastro do primeiro usuário
4. Comece a usar o sistema!

## 📱 Funcionalidades

### Sistema de Login
- Cadastro de novos usuários
- Login seguro com senhas criptografadas
- Controle de sessão

### Centro de Custos
- Cadastro de centros de custo
- Edição e exclusão
- Busca e filtros

### Categorias e Subcategorias
- Sistema hierárquico de categorização
- Vinculação categoria > subcategoria
- Interface intuitiva de gerenciamento

### Lançamentos Financeiros
- Lançamentos de entrada e saída
- Sistema de parcelamento até 48x
- Preview das parcelas com datas editáveis
- Máscara de moeda brasileira
- Filtros por período e tipo

### Fluxo de Caixa
- Atualização em tempo real
- Filtros por período
- Saldo atual e projetado
- Contas a pagar e receber
- Indicadores visuais de vencimento

### Relatórios e Gráficos
- Dashboard interativo
- Gráficos de evolução mensal
- Análise por centro de custo
- Distribuição por categorias
- Análise de tendências
- Sazonalidade
- Projeções futuras
- Comparativos de períodos
- Exportação para PDF

### Gestão de Parcelas
- Controle individual de parcelas
- Marcação de pagamento
- Edição de valores e datas
- Status visual de vencimento
- Progresso de pagamento

## 🎨 Interface

- **Design Moderno**: Interface limpa e profissional
- **Responsiva**: Funciona perfeitamente em desktop, tablet e mobile
- **Cores Intuitivas**: Verde para entradas, vermelho para saídas
- **Navegação Fácil**: Menu superior com ícones
- **Sidebar Informativa**: Fluxo de caixa sempre visível

## 📊 Relatórios Disponíveis

1. **Dashboard Principal**
   - Resumo financeiro
   - Gráficos de entradas vs saídas
   - Distribuição por centro de custo
   - Últimos lançamentos

2. **Relatórios Avançados**
   - Evolução mensal
   - Top 10 categorias
   - Análise de tendências
   - Sazonalidade por mês e dia da semana
   - Projeções lineares
   - Comparativos de períodos

3. **Exportação**
   - Relatórios em PDF
   - Dados detalhados
   - Formatação profissional

## 🔒 Segurança

- Senhas criptografadas com password_hash()
- Controle de sessão
- Validação de dados de entrada
- Proteção contra SQL Injection
- Verificação de permissões por usuário

## 📱 Responsividade

O sistema foi desenvolvido com design responsivo, adaptando-se automaticamente a diferentes tamanhos de tela:

- **Desktop**: Layout completo com sidebar
- **Tablet**: Layout adaptado com navegação otimizada
- **Mobile**: Interface compacta com menu colapsável

## 🛠️ Tecnologias Utilizadas

- **Backend**: PHP 7.4+
- **Banco de Dados**: MySQL 5.7+
- **Frontend**: HTML5, CSS3, JavaScript ES6
- **Gráficos**: Chart.js
- **Responsividade**: CSS Grid e Flexbox
- **Ícones**: Imagens personalizadas

## 📝 Estrutura do Banco de Dados

- `usuarios`: Dados dos usuários do sistema
- `centro_custos`: Centros de custo para classificação
- `categorias`: Categorias principais
- `subcategorias`: Subcategorias vinculadas às categorias
- `lancamentos`: Transações financeiras
- `parcelas`: Detalhes das parcelas dos lançamentos

## 🎯 Como Usar

### Primeiro Acesso
1. Cadastre-se na tela de login
2. Configure os centros de custo
3. Crie categorias e subcategorias
4. Comece a fazer lançamentos

### Lançamentos
1. Acesse "Lançamentos" no menu
2. Preencha o formulário
3. Para parcelamento, selecione "Parcelado" e escolha o número de parcelas
4. Revise o preview das parcelas
5. Confirme o lançamento

### Relatórios
1. Acesse "Relatórios" no menu
2. Selecione o período desejado
3. Clique em "Atualizar Relatórios"
4. Analise os gráficos e dados
5. Exporte em PDF se necessário

## 🆘 Suporte

Para dúvidas ou problemas:

1. Verifique se o XAMPP está funcionando corretamente
2. Confirme se o banco de dados foi criado
3. Verifique as configurações em `includes/config.php`
4. Certifique-se de que todos os arquivos foram copiados corretamente

## 📄 Licença

Este sistema foi desenvolvido para uso educacional e comercial. Todos os direitos reservados à Netmai.

---

**Desenvolvido com ❤️ pela equipe Netmai**
