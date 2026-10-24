-- =====================================================================
-- Dados Iniciais de Teste (Seed Data)
-- Senha padrão para todos os usuários cadastrados: 123456
-- =====================================================================

USE `gestao_eventos_escolares`;

-- 1. Diretor inicial
INSERT INTO `DIRETOR` (`NOME_DIRETOR`, `EMAIL_DIRETOR`, `SENHA_DIRETOR`, `CRIADO_EM`)
VALUES ('Prof. Carlos Diretor', 'diretor@escola.com', '$2y$10$DnDCyuWynjk2hQSWbSzOUO6wT3ndNSj6D9RMV14io69n/glWTAhcO', NOW());

-- 2. Coordenador inicial
INSERT INTO `COORDENADOR` (`NOME_COORDENADOR`, `EMAIL_COORDENADOR`, `SENHA_COORDENADOR`, `CRIADO_EM`)
VALUES ('Profa. Ana Coordenadora', 'coordenador@escola.com', '$2y$10$DnDCyuWynjk2hQSWbSzOUO6wT3ndNSj6D9RMV14io69n/glWTAhcO', NOW());

-- 3. Alunos iniciais
INSERT INTO `ALUNO` (`NOME_ALUNO`, `EMAIL_ALUNO`, `RA_ALUNO`, `SENHA_ALUNO`, `CRIADO_EM`)
VALUES 
('João Pedro Aluno', 'joao.aluno@escola.com', 'RA123456', '$2y$10$DnDCyuWynjk2hQSWbSzOUO6wT3ndNSj6D9RMV14io69n/glWTAhcO', NOW()),
('Maria Silva Aluna', 'maria.aluna@escola.com', 'RA654321', '$2y$10$DnDCyuWynjk2hQSWbSzOUO6wT3ndNSj6D9RMV14io69n/glWTAhcO', NOW());

-- 4. Eventos de teste
INSERT INTO `EVENTO` (`NOME_EVENTO`, `DATA_EVENTO`, `LOCAL_EVENTO`, `VAGAS_MAXIMAS`, `CRIADO_EM`, `ID_COORDENADOR`, `ID_DIRETOR`)
VALUES 
('Feira de Ciências 2026', DATE_ADD(NOW(), INTERVAL 15 DAY), 'Auditório Principal', 50, NOW(), 1, NULL),
('Seminário de Inovação e Tecnologia', DATE_ADD(NOW(), INTERVAL 30 DAY), 'Laboratório de Informática', 30, NOW(), NULL, 1);

-- 5. Inscrição de teste
INSERT INTO `INSCRICAO` (`ID_ALUNO`, `ID_EVENTO`, `PRESENCA_CONFIRMADA`, `DATA_INSCRICAO`)
VALUES (1, 1, 0, NOW());
