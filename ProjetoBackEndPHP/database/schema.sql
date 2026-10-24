-- =====================================================================
-- Sistema de Gestão de Eventos Escolares
-- Script DDL para MySQL (XAMPP)
-- Compatibilidade: MySQL 5.7+ / MariaDB 10.4+ / MySQL 8.0+
-- =====================================================================

CREATE DATABASE IF NOT EXISTS `gestao_eventos_escolares`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `gestao_eventos_escolares`;

-- Desativa checagem de chaves estrangeiras temporariamente para drops limpos
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `INSCRICAO`;
DROP TABLE IF EXISTS `EVENTO`;
DROP TABLE IF EXISTS `ALUNO`;
DROP TABLE IF EXISTS `COORDENADOR`;
DROP TABLE IF EXISTS `DIRETOR`;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
-- 1. Tabela ALUNO
-- =====================================================================
CREATE TABLE `ALUNO` (
    `ID_ALUNO` INT AUTO_INCREMENT PRIMARY KEY,
    `NOME_ALUNO` VARCHAR(100) NOT NULL,
    `EMAIL_ALUNO` VARCHAR(100) NOT NULL,
    `RA_ALUNO` VARCHAR(20) NOT NULL,
    `SENHA_ALUNO` VARCHAR(255) NOT NULL,
    `CRIADO_EM` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `UQ_ALUNO_EMAIL` UNIQUE (`EMAIL_ALUNO`),
    CONSTRAINT `UQ_ALUNO_RA` UNIQUE (`RA_ALUNO`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 2. Tabela COORDENADOR
-- =====================================================================
CREATE TABLE `COORDENADOR` (
    `ID_COORDENADOR` INT AUTO_INCREMENT PRIMARY KEY,
    `NOME_COORDENADOR` VARCHAR(100) NOT NULL,
    `EMAIL_COORDENADOR` VARCHAR(100) NOT NULL,
    `SENHA_COORDENADOR` VARCHAR(255) NOT NULL,
    `CRIADO_EM` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `UQ_COORDENADOR_EMAIL` UNIQUE (`EMAIL_COORDENADOR`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 3. Tabela DIRETOR
-- =====================================================================
CREATE TABLE `DIRETOR` (
    `ID_DIRETOR` INT AUTO_INCREMENT PRIMARY KEY,
    `NOME_DIRETOR` VARCHAR(100) NOT NULL,
    `EMAIL_DIRETOR` VARCHAR(100) NOT NULL,
    `SENHA_DIRETOR` VARCHAR(255) NOT NULL,
    `CRIADO_EM` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `UQ_DIRETOR_EMAIL` UNIQUE (`EMAIL_DIRETOR`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 4. Tabela EVENTO
-- =====================================================================
CREATE TABLE `EVENTO` (
    `ID_EVENTO` INT AUTO_INCREMENT PRIMARY KEY,
    `NOME_EVENTO` VARCHAR(100) NOT NULL,
    `DATA_EVENTO` DATETIME NOT NULL,
    `LOCAL_EVENTO` VARCHAR(100) NOT NULL,
    `VAGAS_MAXIMAS` INT NOT NULL,
    `CRIADO_EM` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `ID_COORDENADOR` INT NULL,
    `ID_DIRETOR` INT NULL,
    CONSTRAINT `FK_ID_COORDENADOR` FOREIGN KEY (`ID_COORDENADOR`)
        REFERENCES `COORDENADOR` (`ID_COORDENADOR`)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,
    CONSTRAINT `FK_ID_DIRETOR` FOREIGN KEY (`ID_DIRETOR`)
        REFERENCES `DIRETOR` (`ID_DIRETOR`)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX `IX_EVENTO_ID_COORDENADOR` ON `EVENTO` (`ID_COORDENADOR`);
CREATE INDEX `IX_EVENTO_ID_DIRETOR` ON `EVENTO` (`ID_DIRETOR`);

-- =====================================================================
-- 5. Tabela INSCRICAO
-- =====================================================================
CREATE TABLE `INSCRICAO` (
    `ID_INSCRICAO` INT AUTO_INCREMENT PRIMARY KEY,
    `ID_ALUNO` INT NOT NULL,
    `ID_EVENTO` INT NOT NULL,
    `PRESENCA_CONFIRMADA` TINYINT(1) NOT NULL DEFAULT 0,
    `DATA_INSCRICAO` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `FK_ID_ALUNO` FOREIGN KEY (`ID_ALUNO`)
        REFERENCES `ALUNO` (`ID_ALUNO`)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT `FK_ID_EVENTO` FOREIGN KEY (`ID_EVENTO`)
        REFERENCES `EVENTO` (`ID_EVENTO`)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT `ALUNO_E_EVENTO_UNIQUE` UNIQUE (`ID_ALUNO`, `ID_EVENTO`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX `IX_INSCRICAO_ID_EVENTO` ON `INSCRICAO` (`ID_EVENTO`);
