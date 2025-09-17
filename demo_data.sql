-- Dados de demonstração para o Sistema Financeiro Netmai
-- Execute este script após a instalação para popular o sistema com dados de exemplo

USE financeiro_ademar;

-- Inserir lançamentos de exemplo (assumindo que existe um usuário com ID 1)
INSERT INTO lancamentos (tipo, centro_custo_id, subcategoria_id, valor, data, forma_pagamento, parcelas, usuario_id) VALUES
-- Entradas
('entrada', 2, 1, 15000.00, '2024-01-15', 'a_vista', NULL, 1),
('entrada', 2, 1, 8500.00, '2024-01-20', 'parcelado', 3, 1),
('entrada', 2, 2, 12000.00, '2024-02-05', 'a_vista', NULL, 1),
('entrada', 2, 1, 22000.00, '2024-02-18', 'parcelado', 6, 1),
('entrada', 2, 3, 1500.00, '2024-03-10', 'a_vista', NULL, 1),

-- Saídas
('saida', 1, 4, 8000.00, '2024-01-05', 'a_vista', NULL, 1),
('saida', 1, 5, 2500.00, '2024-01-01', 'a_vista', NULL, 1),
('saida', 2, 6, 450.00, '2024-01-10', 'a_vista', NULL, 1),
('saida', 2, 7, 380.00, '2024-01-15', 'a_vista', NULL, 1),
('saida', 3, 8, 650.00, '2024-01-20', 'a_vista', NULL, 1),
('saida', 1, 4, 8000.00, '2024-02-05', 'a_vista', NULL, 1),
('saida', 3, 9, 1200.00, '2024-02-12', 'a_vista', NULL, 1),
('saida', 4, 11, 15000.00, '2024-02-20', 'parcelado', 12, 1),
('saida', 1, 5, 2500.00, '2024-03-01', 'a_vista', NULL, 1),
('saida', 2, 6, 520.00, '2024-03-08', 'a_vista', NULL, 1);

-- Inserir parcelas para os lançamentos parcelados
-- Lançamento ID 2 (Entrada parcelada em 3x)
INSERT INTO parcelas (lancamento_id, numero_parcela, valor, data_vencimento, pago) VALUES
(2, 1, 2833.33, '2024-01-20', 1),
(2, 2, 2833.33, '2024-02-20', 1),
(2, 3, 2833.34, '2024-03-20', 0);

-- Lançamento ID 4 (Entrada parcelada em 6x)
INSERT INTO parcelas (lancamento_id, numero_parcela, valor, data_vencimento, pago) VALUES
(4, 1, 3666.67, '2024-02-18', 1),
(4, 2, 3666.67, '2024-03-18', 1),
(4, 3, 3666.67, '2024-04-18', 0),
(4, 4, 3666.67, '2024-05-18', 0),
(4, 5, 3666.67, '2024-06-18', 0),
(4, 6, 3666.65, '2024-07-18', 0);

-- Lançamento ID 13 (Saída parcelada em 12x - Equipamentos)
INSERT INTO parcelas (lancamento_id, numero_parcela, valor, data_vencimento, pago) VALUES
(13, 1, 1250.00, '2024-02-20', 1),
(13, 2, 1250.00, '2024-03-20', 1),
(13, 3, 1250.00, '2024-04-20', 0),
(13, 4, 1250.00, '2024-05-20', 0),
(13, 5, 1250.00, '2024-06-20', 0),
(13, 6, 1250.00, '2024-07-20', 0),
(13, 7, 1250.00, '2024-08-20', 0),
(13, 8, 1250.00, '2024-09-20', 0),
(13, 9, 1250.00, '2024-10-20', 0),
(13, 10, 1250.00, '2024-11-20', 0),
(13, 11, 1250.00, '2024-12-20', 0),
(13, 12, 1250.00, '2025-01-20', 0);

-- Inserir mais alguns lançamentos para enriquecer os dados
INSERT INTO lancamentos (tipo, centro_custo_id, subcategoria_id, valor, data, forma_pagamento, parcelas, usuario_id) VALUES
('entrada', 2, 1, 18500.00, '2024-03-25', 'a_vista', NULL, 1),
('entrada', 2, 2, 9200.00, '2024-04-02', 'parcelado', 2, 1),
('saida', 3, 10, 2800.00, '2024-03-15', 'a_vista', NULL, 1),
('saida', 4, 12, 4500.00, '2024-03-22', 'parcelado', 4, 1),
('entrada', 2, 3, 850.00, '2024-04-10', 'a_vista', NULL, 1),
('saida', 2, 7, 420.00, '2024-04-05', 'a_vista', NULL, 1);

-- Parcelas para os novos lançamentos parcelados
-- Lançamento ID 17 (Entrada parcelada em 2x)
INSERT INTO parcelas (lancamento_id, numero_parcela, valor, data_vencimento, pago) VALUES
(17, 1, 4600.00, '2024-04-02', 1),
(17, 2, 4600.00, '2024-05-02', 0);

-- Lançamento ID 19 (Saída parcelada em 4x - Software)
INSERT INTO parcelas (lancamento_id, numero_parcela, valor, data_vencimento, pago) VALUES
(19, 1, 1125.00, '2024-03-22', 1),
(19, 2, 1125.00, '2024-04-22', 0),
(19, 3, 1125.00, '2024-05-22', 0),
(19, 4, 1125.00, '2024-06-22', 0);

-- Comentários sobre os dados inseridos:
-- 
-- RESUMO DOS DADOS DE DEMONSTRAÇÃO:
-- 
-- ENTRADAS:
-- - Janeiro: R$ 23.500,00 (R$ 15.000 à vista + R$ 8.500 parcelado)
-- - Fevereiro: R$ 34.000,00 (R$ 12.000 à vista + R$ 22.000 parcelado)
-- - Março: R$ 20.850,00 (R$ 1.500 + R$ 18.500 à vista + R$ 850)
-- - Abril: R$ 9.200,00 (parcelado)
-- 
-- SAÍDAS:
-- - Janeiro: R$ 11.330,00
-- - Fevereiro: R$ 24.200,00 (incluindo R$ 15.000 parcelado)
-- - Março: R$ 7.320,00 (incluindo R$ 4.500 parcelado)
-- - Abril: R$ 420,00
-- 
-- PARCELAS PENDENTES:
-- - Várias parcelas de entradas e saídas com vencimentos futuros
-- - Permite testar o sistema de controle de parcelas
-- - Demonstra diferentes cenários de pagamento
-- 
-- Este conjunto de dados permite testar:
-- ✓ Dashboard com dados reais
-- ✓ Gráficos de evolução mensal
-- ✓ Fluxo de caixa com saldo positivo/negativo
-- ✓ Sistema de parcelas
-- ✓ Relatórios por categoria e centro de custo
-- ✓ Contas a pagar e receber
-- ✓ Análises de tendência e sazonalidade
