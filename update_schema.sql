-- Atualização do schema para controle de baixa de lançamentos
-- Execute este script para adicionar o controle de baixa aos lançamentos

USE financeiro_ademar;

-- Adicionar campo 'pago' na tabela lancamentos para controle de baixa
ALTER TABLE `lancamentos` ADD COLUMN `pago` tinyint(1) NOT NULL DEFAULT 1 AFTER `usuario_id`;

-- Adicionar campo 'data_pagamento' para registrar quando foi dada a baixa
ALTER TABLE `lancamentos` ADD COLUMN `data_pagamento` date NULL AFTER `pago`;

-- Atualizar lançamentos existentes com data futura para não pago
UPDATE `lancamentos` SET `pago` = 0 WHERE `data` > CURDATE();

-- Comentários sobre os novos campos:
-- pago: 0 = Não pago (conta a pagar/receber), 1 = Pago (impacta o fluxo de caixa)
-- data_pagamento: Data em que foi dada a baixa (NULL se não foi pago ainda)
