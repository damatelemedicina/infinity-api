USE `infinity`;

-- UPDATE exames SET enviado_por = 'MANUAL' WHERE enviado_por IS NULL AND id > 0;
-- ALTER TABLE exames MODIFY enviado_por VARCHAR(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'MANUAL';

-- UPDATE exames SET peso = 0.00 WHERE peso IS NULL AND id > 0;
-- ALTER TABLE exames MODIFY peso DECIMAL(10,2) DEFAULT 0.00;

-- UPDATE exames SET altura = 0.00 WHERE altura IS NULL AND id > 0;
-- ALTER TABLE exames MODIFY altura DECIMAL(10,2) DEFAULT 0.00;

-- ALTER TABLE usuarios MODIFY cpf VARCHAR(20) CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci' NULL DEFAULT NULL;

-- ALTER TABLE exames MODIFY status int NOT NULL DEFAULT '0';
-- ALTER TABLE exames RENAME COLUMN `placas_extensao_o_d` TO `placas_extensao_od`;
-- ALTER TABLE exames RENAME COLUMN `placas_extensao_o_e` TO `placas_extensao_oe`;
-- ALTER TABLE exames RENAME COLUMN `espes_extensao_o_d` TO `espes_extensao_od`;
-- ALTER TABLE exames RENAME COLUMN `espes_extensao_o_e` TO `espes_extensao_oe`;
-- ALTER TABLE exames MODIFY zonas varchar(30);
-- ALTER TABLE exames MODIFY placas_parede_local varchar(30);
-- ALTER TABLE exames MODIFY placas_frontal_local varchar(30);
-- ALTER TABLE exames MODIFY placas_diafrag_local varchar(30);
-- ALTER TABLE exames MODIFY placas_outros_local varchar(30);
-- ALTER TABLE exames MODIFY placas_parede_calcif varchar(30);
-- ALTER TABLE exames MODIFY placas_frontal_calcif varchar(30);
-- ALTER TABLE exames MODIFY placas_diafrag_calcif varchar(30);
-- ALTER TABLE exames MODIFY placas_outros_calcif varchar(30);
-- ALTER TABLE exames MODIFY placas_extensao_od varchar(30);
-- ALTER TABLE exames MODIFY placas_extensao_oe varchar(30);
-- ALTER TABLE exames MODIFY placas_largura_d varchar(30);
-- ALTER TABLE exames MODIFY placas_largura_e varchar(30);
-- ALTER TABLE exames MODIFY obliteracao varchar(30);
-- ALTER TABLE exames MODIFY espes_parede_local varchar(30);
-- ALTER TABLE exames MODIFY espes_frontal_local varchar(30);
-- ALTER TABLE exames MODIFY espes_parede_calcif varchar(30);
-- ALTER TABLE exames MODIFY espes_frontal_calcif varchar(30);
-- ALTER TABLE exames MODIFY espes_extensao_od varchar(30);
-- ALTER TABLE exames MODIFY espes_extensao_oe varchar(30);
-- ALTER TABLE exames MODIFY espes_largura_d varchar(30);
-- ALTER TABLE exames MODIFY espes_largura_e varchar(30);
-- ALTER TABLE exames RENAME COLUMN `digitado` TO `recepcionado`;
-- ALTER TABLE exames ADD COLUMN `atendimento` varchar(25) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
-- ALTER TABLE exames ADD COLUMN `medico_id` bigint NOT NULL DEFAULT '0' AFTER `empresa_id`;
-- ALTER TABLE exames ADD COLUMN `laudo_imagem` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `arquivo_imagem`;
-- ALTER TABLE exames ADD COLUMN `laudo_anexo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `laudo_imagem`;
-- ALTER TABLE exames MODIFY id bigint unsigned NOT NULL AUTO_INCREMENT;
-- ALTER TABLE exames ADD COLUMN `despacho_date` timestamp DEFAULT NULL AFTER `recepcionado`;
-- ALTER TABLE exames ADD COLUMN `despacho_prazo` timestamp DEFAULT NULL AFTER `recepcionado`;
-- ALTER TABLE exames ADD COLUMN `abonado` int NOT NULL DEFAULT '0' AFTER `recepcionado`;
-- ALTER TABLE exames ADD COLUMN `motivo_abono` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `recepcionado`;
-- ALTER TABLE exames ADD COLUMN `pausado` timestamp DEFAULT NULL AFTER `atendimento`;

-- ALTER TABLE exames MODIFY `arquivo_exame` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL;

-- ALTER TABLE exames ADD COLUMN `digitado` int NOT NULL DEFAULT '0' AFTER `recepcionado`;

-- CREATE INDEX byMedico ON exames(medico_id);

-- ALTER TABLE exames ADD COLUMN `copiado_de` bigint unsigned DEFAULT 0 AFTER `recepcionado`;

-- ALTER TABLE exames ADD COLUMN `abonado_medico` int NOT NULL DEFAULT '0' AFTER `abonado`;

-- ALTER TABLE exames ADD COLUMN `laudo_cancelado_date` timestamp DEFAULT NULL AFTER `laudo_download_date`;

-- ALTER TABLE clientes ADD COLUMN `cabecalho` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
-- ALTER TABLE clientes ADD COLUMN `rodape` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
-- ALTER TABLE clientes ADD COLUMN `logo_oit` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `rodape`;
-- ALTER TABLE clientes ADD COLUMN `telas` int COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0' AFTER `situacao`;
-- ALTER TABLE clientes ADD COLUMN `laudo_imagem` int COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0' AFTER `emergencia`;
-- ALTER TABLE clientes ADD COLUMN `mensagem_medicos` text COLLATE utf8mb4_unicode_ci AFTER `institution_name`;

-- ALTER TABLE clientes CHANGE COLUMN `chave_transmissao` `chave_transmissao` VARCHAR(256) CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci' NULL DEFAULT NULL;

-- ALTER TABLE medicos ADD COLUMN `solicitante` bigint NOT NULL DEFAULT '0';
-- ALTER TABLE medicos ADD COLUMN `certificado` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
-- ALTER TABLE medicos ADD COLUMN `assinatura` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
-- ALTER TABLE medicos ADD COLUMN `expira` datetime COLLATE utf8mb4_unicode_ci DEFAULT NULL;
-- ALTER TABLE medicos ADD COLUMN `senha` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
-- ALTER TABLE medicos ADD COLUMN `arquivo` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
-- ALTER TABLE medicos ADD COLUMN `assinatura_oit` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `assinatura`;

-- ALTER TABLE medicos ADD COLUMN `recusas` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `arquivo`;

-- ALTER TABLE tipo_exames ADD COLUMN `desativar_upload` int COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0' AFTER `laudo_rapido`;
-- ALTER TABLE tipo_exames ADD COLUMN `desativar_modelo` int COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0' AFTER `laudo_rapido`;

-- ALTER TABLE medicos_modelos ADD COLUMN `padrao` int COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0' AFTER `modelo`;

-- ALTER TABLE despacho_regras MODIFY `hora_inicial` varchar(8) DEFAULT '00:00:00';
-- ALTER TABLE despacho_regras MODIFY `hora_final` varchar(8) DEFAULT '23:59:59';
-- ALTER TABLE despacho_regras MODIFY `hora_inicial` varchar(8) DEFAULT '00:00:00';
-- ALTER TABLE despacho_regras MODIFY `hora_final` varchar(8) DEFAULT '23:59:59';
-- ALTER TABLE despacho_regras MODIFY `hora_final` varchar(8) DEFAULT '23:59:59';
-- ALTER TABLE despacho_regras MODIFY `nome` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL;

-- ALTER TABLE despacho_regras ADD COLUMN `incompleto` int COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0' AFTER `tipo`;

-- ALTER TABLE preco_clientes MODIFY `cobranca2` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL;
-- ALTER TABLE preco_clientes MODIFY `cobranca2` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL;

-- ALTER TABLE medicos_exames ADD COLUMN `recusa` int COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0' AFTER `tipo_exame_id`;

-- ALTER TABLE despacho_filas ADD COLUMN `recusas` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `exames`;

-- ALTER TABLE preco_medicos MODIFY `nome` VARCHAR(100) CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci' NOT NULL;

-- DROP TABLE IF EXISTS `impossibilidades`;

-- CREATE TABLE IF NOT EXISTS `impossibilidades` (
--   `id` bigint unsigned NOT NULL AUTO_INCREMENT,
--   `nome` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
--   `empresa_id` bigint unsigned NOT NULL,
--   `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
--   `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
--   PRIMARY KEY (`id`),
--   KEY `byEmpresa` (`empresa_id`),
--   CONSTRAINT `byEmpresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`)
-- ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- INSERT INTO `impossibilidades`(`nome`,`empresa_id`)
-- VALUES('INTERFERENCIAS', 1);

-- INSERT INTO `impossibilidades`(`nome`,`empresa_id`)
-- VALUES('ARTEFATOS EM GRANDE QUANTIDADE, NAO SENDO POSSIVEL AVALIAR A ATIVIDADE DE BASE NEM CONSTATAR PRESENCA DE ANORMALIDADES', 1);

-- INSERT INTO `impossibilidades`(`nome`,`empresa_id`)
-- VALUES('ARQUIVO CORROMPIDO OU INEXISTENTE', 1);

-- INSERT INTO `impossibilidades`(`nome`,`empresa_id`)
-- VALUES('DIVERGENCIA ENTRE DADOS DO PACIENTE E EXAME ANEXADO', 1);

-- INSERT INTO `impossibilidades`(`nome`,`empresa_id`)
-- VALUES('EXAME NAO LAUDADO DEVIDO ERRO DE ENVIO. FAVOR ENVIAR NOVAMENTE', 1);

-- INSERT INTO `impossibilidades`(`nome`,`empresa_id`)
-- VALUES('TRAÇADO COM DIFICIL INTERPRETACAO POR QUALIDADE RUIM DO EXAME', 1);

-- INSERT INTO `impossibilidades`(`nome`,`empresa_id`)
-- VALUES('INVERSAO DE ELETRODOS. POR FAVOR FACA UM NOVO EXAME', 1);

-- INSERT INTO `impossibilidades`(`nome`,`empresa_id`)
-- VALUES('INTERFERENCIA EM LINHA DE BASE. POR FAVOR FACA UM NOVO EXAME', 1);

-- INSERT INTO `impossibilidades`(`nome`,`empresa_id`)
-- VALUES('NAO HOUVE REGISTRO EM UMA OU MAIS DERIVACOES', 1);

-- INSERT INTO `impossibilidades`(`nome`,`empresa_id`)
-- VALUES('OUTRO TIPO DE EXAME ANEXADO', 1);

-- INSERT INTO `impossibilidades`(`nome`,`empresa_id`)
-- VALUES('AUSENCIA DE DADOS IMPORTANTES PARA O LAUDO', 1);

-- INSERT INTO `impossibilidades`(`nome`,`empresa_id`)
-- VALUES('TEMPO DE GRAVACAO INADEQUADO, FAVOR REFAZER O EXAME', 1);

-- INSERT INTO `impossibilidades`(`nome`,`empresa_id`)
-- VALUES('EXAME CLINICO, FAVOR REENVIAR COMO CLINICO', 1);

-- INSERT INTO `impossibilidades`(`nome`,`empresa_id`)
-- VALUES('ENCHER MAIS OS PULMOES E SOPRAR POR MAIS TEMPO', 1);

-- INSERT INTO `impossibilidades`(`nome`,`empresa_id`)
-- VALUES('FAVOR REPETIR O EXAME SOPRANDO COM MAIS FORCA DESDE O INICIO', 1);

-- INSERT INTO `impossibilidades`(`nome`,`empresa_id`)
-- VALUES('ENCHER MAIS OS PULMOES E SOPRAR POR MAIS TEMPO', 1);

-- INSERT INTO `impossibilidades`(`nome`,`empresa_id`)
-- VALUES('SOPRAR POR MAIS TEMPO', 1);

-- DROP TABLE IF EXISTS `equipamentos`;

-- DELETE FROM autorizacaos where id > 0;
-- DELETE FROM permissaos where id > 0;
-- INSERT INTO permissaos (`id`, `recurso`, `descricao`) VALUES (10, 'MenuPainel', 'Menu: Painel');
-- INSERT INTO permissaos (`id`, `recurso`, `descricao`) VALUES (20, 'MenuExames', 'Menu: Exames');
-- INSERT INTO permissaos (`id`, `recurso`, `descricao`) VALUES (30, 'MenuEmpresas', 'Menu: Empresas');
-- INSERT INTO permissaos (`id`, `recurso`, `descricao`) VALUES (40, 'MenuClientes', 'Menu: Clientes');
-- INSERT INTO permissaos (`id`, `recurso`, `descricao`) VALUES (50, 'MenuUsuarios', 'Menu: Usuários');
-- INSERT INTO permissaos (`id`, `recurso`, `descricao`) VALUES (60, 'MenuMedicos', 'Menu: Médicos');
-- INSERT INTO permissaos (`id`, `recurso`, `descricao`) VALUES (70, 'MenuEquipamentos', 'Menu: Equipamentos');
-- INSERT INTO permissaos (`id`, `recurso`, `descricao`) VALUES (80, 'MenuFinanceiro', 'Menu: Financeiro');
-- INSERT INTO permissaos (`id`, `recurso`, `descricao`) VALUES (90, 'MenuRelatorios', 'Menu: Relatorios');
-- INSERT INTO permissaos (`id`, `recurso`, `descricao`) VALUES (91, 'MenuRelatorioFinanceiroClientes', 'Menu: Relatorio Financeiro (Clientes)');
-- INSERT INTO permissaos (`id`, `recurso`, `descricao`) VALUES (92, 'MenuRelatorioFinanceiroMedicos', 'Menu: Relatorio Financeiro (Médicos)');
-- INSERT INTO permissaos (`id`, `recurso`, `descricao`) VALUES (93, 'MenuRelatorioFaturamentoExportar', 'Menu: Faturamento (Exportar)');
-- INSERT INTO permissaos (`id`, `recurso`, `descricao`) VALUES (94, 'MenuRelatorioFaturamentoPacotes', 'Menu: Faturamento (Pacotes)');
-- INSERT INTO permissaos (`id`, `recurso`, `descricao`) VALUES (100,'MenuConfiguracoes', 'Menu: Configurações');
-- INSERT INTO autorizacaos(perfil_id, permissao_id, acesso)
-- SELECT perfils.id as perfil_id, permissaos.id as permissao_id,'VISUALIZAR' as acesso
-- FROM perfils, permissaos
-- ORDER BY perfils.id, permissaos.id;

-- ALTER TABLE empresas ADD COLUMN  `recado_medicos` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci AFTER `medico_compartilhado`;
-- ALTER TABLE empresas ADD COLUMN  `recado_clientes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci AFTER `medico_compartilhado`;
-- ALTER TABLE empresas ADD COLUMN  `recado_colaboradores` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci AFTER `medico_compartilhado`;

-- INSERT INTO permissaos (`id`, `recurso`, `descricao`) VALUES (15,'MenuRecados', 'Menu: Recados');
-- INSERT INTO autorizacaos(perfil_id, permissao_id, acesso)
-- SELECT perfils.id as perfil_id, permissaos.id as permissao_id,'VISUALIZAR' as acesso
-- FROM perfils, permissaos
-- WHERE permissaos.id = 15
-- ORDER BY perfils.id, permissaos.id;
UPDATE
	pacientes
SET
	rg = regexp_replace(rg, '[^0-9]', ''),
	cpf = regexp_replace(cpf, '[^0-9]', '')
WHERE id > 0;

CREATE TABLE IF NOT EXISTS `contas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `cliente_id` bigint unsigned DEFAULT '0',
  `empresa_id` bigint unsigned NOT NULL,
  `descricao` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `valor` decimal(10,2) DEFAULT '0',
  `data` timestamp DEFAULT CURRENT_TIMESTAMP NOT NULL,
  PRIMARY KEY (`id`),
  KEY `byCliente` (`cliente_id`),
  KEY `byEmpresa` (`empresa_id`),
  CONSTRAINT `ContaByEmpresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas`(`id`),
  CONSTRAINT `ContaByCliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `equipamentos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `cliente_id` bigint unsigned DEFAULT '0',
  `empresa_id` bigint unsigned NOT NULL,
  `exame` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `marca` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `serie` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `cidade` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `estado` varchar(2) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `observacoes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `contrato` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `bySerie` (`serie`),
  KEY `byCliente` (`cliente_id`),
  KEY `byEmpresa` (`empresa_id`),
  CONSTRAINT `EquipamentoByEmpresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `preco_clientes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `empresa_id` bigint unsigned NOT NULL,
  `cliente_id` bigint unsigned DEFAULT '0',
  `tipo_exame_id` bigint unsigned DEFAULT '0',
  `vigencia_de` date DEFAULT null,
  `vigencia_ate` date DEFAULT null,
  `de1` int DEFAULT '0',
  `ate1` int DEFAULT '0',
  `preco1` decimal(10,2) DEFAULT NULL,
  `cobranca1` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `de2` int DEFAULT '0',
  `ate2` int DEFAULT '0',
  `preco2` decimal(10,2) DEFAULT NULL,
  `cobranca2` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `byEmpresa` (`empresa_id`),
  KEY `byCliente` (`cliente_id`),
  KEY `byTipoExame` (`tipo_exame_id`),
  CONSTRAINT `PrecoClienteByEmpresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`),
  CONSTRAINT `PrecoClienteByCliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`),
  CONSTRAINT `PrecoClienteByTipoExame` FOREIGN KEY (`tipo_exame_id`) REFERENCES `tipo_exames` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `preco_medicos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `empresa_id` bigint unsigned NOT NULL,
  `medico_id` bigint unsigned DEFAULT '0',
  `tipo_exame_id` bigint unsigned DEFAULT '0',
  `vigencia_de` date DEFAULT null,
  `vigencia_ate` date DEFAULT null,
  `preco` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `byEmpresa` (`empresa_id`),
  KEY `byMedico` (`medico_id`),
  KEY `byTipoExame` (`tipo_exame_id`),
  CONSTRAINT `PrecoMedicoByEmpresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`),
  CONSTRAINT `PrecoMedicoByMedico` FOREIGN KEY (`medico_id`) REFERENCES `medicos` (`id`),
  CONSTRAINT `PrecoMedicoByTipoExame` FOREIGN KEY (`tipo_exame_id`) REFERENCES `tipo_exames` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `despacho_regras` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `empresa_id` bigint unsigned NOT NULL,
  `medico_id` bigint unsigned NOT NULL, -- para cima obrigatorios
  `cliente_id` bigint unsigned DEFAULT '0',
  `tipo_exame_id` bigint unsigned DEFAULT '0',
  `quantidade` int DEFAULT '0',
  `dias` varchar(30) DEFAULT 'DOM,SEG,TER,QUA,QUI,SEX,SAB',
  `hora_inicial` time DEFAULT '00:00:00',
  `hora_final` time DEFAULT '23:59:59',
  `ativa` int DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `byEmpresa` (`empresa_id`),
  KEY `byMedico` (`medico_id`),
  KEY `byCliente` (`cliente_id`),
  KEY `byTipoExame` (`tipo_exame_id`),
  CONSTRAINT `DespachoRegraByEmpresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`),
  CONSTRAINT `DespachoRegraByMedico` FOREIGN KEY (`medico_id`) REFERENCES `medicos` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `despacho_filas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `exames` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `empresa_id` bigint unsigned NOT NULL,
  `medico_id` bigint unsigned NOT NULL,
  `status` int DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `byMedico` (`medico_id`),
  KEY `byEmpresa` (`empresa_id`),
  CONSTRAINT `DespachoFilaByEmpresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas`(`id`),
  CONSTRAINT `DespachoFilaByMedico` FOREIGN KEY (`medico_id`) REFERENCES `medicos`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `despacho_recusas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `medico_id` bigint unsigned NOT NULL,
  `exame_id` bigint unsigned NOT NULL,
  `motivo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `byMedico` (`medico_id`),
  KEY `byExame` (`exame_id`),
  CONSTRAINT `DespachoRecusaByMedico` FOREIGN KEY (`medico_id`) REFERENCES `medicos` (`id`),
  CONSTRAINT `DespachoRecusaByExame` FOREIGN KEY (`exame_id`) REFERENCES `exames` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `medicos_modelos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `medico_id` bigint unsigned NOT NULL,
  `modelo` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `byMedico` (`medico_id`),
  CONSTRAINT `byMedico` FOREIGN KEY (`medico_id`) REFERENCES `medicos` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `exames` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `numero` bigint NOT NULL DEFAULT '0',
  `exame_id` bigint NOT NULL,
  `motivo_id` bigint DEFAULT NULL,
  `cliente_id` bigint NOT NULL,
  `empresa_id` bigint NOT NULL,
  `medico_id` bigint NOT NULL DEFAULT 0,
  `status` int NOT NULL DEFAULT '0',
  `arquivo_exame` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `arquivo_laudo` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `create_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `observacoes` text COLLATE utf8mb4_unicode_ci,
  `observacoes_medico` text COLLATE utf8mb4_unicode_ci,
  `modelo_content` text COLLATE utf8mb4_unicode_ci,
  `arquivos_selecionados` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `opcoes_impossibilitado` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `paciente` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rg` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cpf` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nascimento` datetime DEFAULT NULL,
  `sexo` varchar(1) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `funcao` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contratante` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ativo` int NOT NULL DEFAULT '1',
  `emergencia` int NOT NULL DEFAULT '0',
  `laudo_impossibilitado` int NOT NULL DEFAULT '0',
  `laudo_date` timestamp NULL DEFAULT NULL,
  `exame_date` timestamp NULL DEFAULT NULL,
  `medico_solicitante` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `crm_solicitante` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sub_tipo_exame` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `peso` decimal(10,2) DEFAULT NULL,
  `altura` decimal(10,2) DEFAULT NULL,
  `imc` decimal(10,2) DEFAULT NULL,
  `fumante` char(1) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fumante_tempo` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `extra_data` text COLLATE utf8mb4_unicode_ci,
  `preco_exame` decimal(10,2) DEFAULT NULL,
  `preco_exame_medico` decimal(10,2) DEFAULT NULL,
  `baixado` int DEFAULT NULL,
  `acuidade_perto_od` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `acuidade_perto_oe` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `acuidade_longe_od` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `acuidade_longe_oe` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lente_corretiva` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `senso_cromatico` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `visao_noturna` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `visao_ofuscada` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `profundidade` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `crc` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rnd` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `protocolo` varchar(25) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `arquivo_laudo_assinado` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `arquivo_imagem` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `arquivo_id` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `laudo_download_date` timestamp NULL DEFAULT NULL,
  `rx_digital` varchar(1) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `negatoscopio` varchar(1) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `qualidade` varchar(1) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `comentarios_qualidade` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `normal` varchar(1) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `anormalidade_parenquima` varchar(1) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `primarias` varchar(1) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `secundarias` varchar(1) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `zonas` varchar(6) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `profusao` varchar(3) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `grd_opacidade` varchar(1) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `anormalidade_pleural` varchar(1) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `placas_pleurais` varchar(1) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `placas_parede_local` varchar(3) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `placas_frontal_local` varchar(3) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `placas_diafrag_local` varchar(3) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `placas_outros_local` varchar(3) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `placas_parede_calcif` varchar(3) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `placas_frontal_calcif` varchar(3) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `placas_diafrag_calcif` varchar(3) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `placas_outros_calcif` varchar(3) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `placas_extensao_o_d` varchar(5) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `placas_extensao_o_e` varchar(5) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `placas_largura_d` varchar(4) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `placas_largura_e` varchar(4) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `obliteracao` varchar(3) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `espessamento_pleural` varchar(1) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `espes_parede_local` varchar(3) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `espes_frontal_local` varchar(3) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `espes_parede_calcif` varchar(3) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `espes_frontal_calcif` varchar(3) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `espes_extensao_o_d` varchar(5) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `espes_extensao_o_e` varchar(5) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `espes_largura_d` varchar(4) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `espes_largura_e` varchar(4) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `outras_anormalidades` varchar(1) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `simbolos` varchar(58) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `comentarios_laudo` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reenviar_exame` int DEFAULT NULL,
  `enviado_por` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pagina_pdf_laudo` int DEFAULT NULL,
  `imagem_date` timestamp NULL DEFAULT NULL,
  `laudo_ecg` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `laudo_ecg_outros` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `frequencia_ecg` int DEFAULT NULL,
  `empresa` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `recepcionado` bigint NOT NULL DEFAULT '0',
  `abonado` int NOT NULL DEFAULT '0',
  `motivo_abono` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `byExame` (`exame_id`),
  KEY `byEmpresa` (`empresa_id`),
  KEY `byCliente` (`cliente_id`),
  KEY `byMedico` (`medico_id`),
  KEY `byArquivoExame` (`arquivo_exame`),
  KEY `byArquivo` (`arquivo_id`),
  KEY `byCreateDate` (`create_date`),
  KEY `byCRC` (`crc`,`ativo`),
  KEY `byProtocolo` (`protocolo`),
  KEY `byRG` (`rg`),
  KEY `byCPF` (`cpf`)
) ENGINE=InnoDB AUTO_INCREMENT=211 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP procedure IF EXISTS `GetLoginDoUsuario`;
DROP procedure IF EXISTS `GetEmpresas`;
DROP procedure IF EXISTS `GetDominiosDoUsuario`;
DROP procedure IF EXISTS `GetPermissoesDoPerfil`;
DROP procedure IF EXISTS `GetFichas`;
DROP procedure IF EXISTS `GetExamesDaEmpresa`;
DROP procedure IF EXISTS `GetExamesDoCliente`;
DROP procedure IF EXISTS `GetExamesDoMedico`;
DROP procedure IF EXISTS `GetExameParaLaudar`;
DROP procedure IF EXISTS `GetLaudosDoMedico`;
DROP procedure IF EXISTS `GetLaudosDoCliente`;
DROP procedure IF EXISTS `GetFaturamentoExportar`;
DROP procedure IF EXISTS `GetFaturamentoPacotes`;
DROP procedure IF EXISTS `GetSaldoDoCliente`;
DROP procedure IF EXISTS `GetPesquisaAvancada`;
DROP procedure IF EXISTS `GetMedicosDoExame`;
DROP procedure IF EXISTS `GetWXMLIncompleto`;
DROP procedure IF EXISTS `GetLaudosParaDownload`;
DROP procedure IF EXISTS `CalculaIMC`;
DROP procedure IF EXISTS `GetClientes`;
DROP procedure IF EXISTS `GetPainel`;

DROP trigger IF EXISTS `CalculaIMC_OnInsert`;
DROP trigger IF EXISTS `CalculaIMC_OnUpdate`;

DELIMITER $$

CREATE PROCEDURE `GetPainel`(CLIENTE_ID integer, MEDICO_ID integer, MATRIZ_ID integer)
BEGIN
	SELECT * FROM (
		SELECT
			tipo_exames.nome as exame,
			medicos.nome as medico,
			COUNT(exames.id) as total
		FROM exames
		INNER JOIN tipo_exames on tipo_exames.id = exames.exame_id
		INNER JOIN medicos on medicos.id = exames.medico_id
        INNER JOIN empresas on exames.empresa_id = empresas.id
		WHERE exames.status = 1 AND exames.ativo = 1
            AND Day(laudo_date) = Day(Now())
            AND Month(laudo_date) = Month(Now())
            AND Year(laudo_date) = Year(Now())
			AND IF (CLIENTE_ID > 0, FALSE, TRUE)
            AND IF (MEDICO_ID > 0, exames.medico_id = MEDICO_ID, TRUE)
            AND IF (MATRIZ_ID > 0, empresas.matriz = MATRIZ_ID, TRUE)
        GROUP BY exames.medico_id, exames.exame_id
        ORDER BY medico ASC, total DESC
    ) AS laudados;

    SELECT * FROM (
		SELECT IFNULL(SUM(exames.arquivo_imagem IS NULL), 0) AS sem_imagem,
			   IFNULL(SUM(exames.arquivo_imagem IS NOT NULL), 0) AS com_imagem
		FROM exames
		INNER JOIN tipo_exames on tipo_exames.id = exames.exame_id
        INNER JOIN empresas on exames.empresa_id = empresas.id
		WHERE exames.status = 0 AND exames.ativo = 1 AND exames.exame_id = 1
		AND IF (CLIENTE_ID > 0, FALSE, TRUE)
        AND IF (MATRIZ_ID > 0, empresas.matriz = MATRIZ_ID, TRUE)
        AND IF (MEDICO_ID > 0, exames.exame_id IN (
			SELECT medicos_exames.tipo_exame_id FROM medicos_exames WHERE medicos_exames.medico_id = MEDICO_ID
		), TRUE)
	) AS ECG;

    SELECT * FROM (
		SELECT
			tipo_exames.id as id,
			tipo_exames.nome as exame,
			COUNT(exames.id) as total
		FROM exames
		INNER JOIN tipo_exames on tipo_exames.id = exames.exame_id
        INNER JOIN empresas on exames.empresa_id = empresas.id
		WHERE exames.status = 0 AND exames.ativo = 1
		AND IF (CLIENTE_ID > 0, exames.cliente_id = CLIENTE_ID, TRUE)
        AND IF (MEDICO_ID > 0, exames.exame_id IN (
			SELECT medicos_exames.tipo_exame_id FROM medicos_exames WHERE medicos_exames.medico_id = MEDICO_ID
		), TRUE)
        AND IF (MEDICO_ID > 0, exames.medico_id = MEDICO_ID OR exames.medico_id = 0, TRUE)
        AND IF (MATRIZ_ID > 0, empresas.matriz = MATRIZ_ID, TRUE)
		GROUP BY exames.exame_id
		ORDER BY total DESC
	) AS aguardandoLaudo
    WHERE total > 0;

    SELECT * FROM (
        SELECT
            count(exames.id) as emergencias
        FROM exames
        INNER JOIN tipo_exames on tipo_exames.id = exames.exame_id
        INNER JOIN empresas on exames.empresa_id = empresas.id
        WHERE exames.status = 0 AND exames.ativo = 1 AND exames.emergencia = 2
        AND IF (CLIENTE_ID > 0, exames.cliente_id = CLIENTE_ID, TRUE)
        AND IF (MEDICO_ID > 0, exames.exame_id IN (
            SELECT medicos_exames.tipo_exame_id FROM medicos_exames WHERE medicos_exames.medico_id = MEDICO_ID
        ), TRUE)
        AND IF (MEDICO_ID > 0, exames.medico_id = MEDICO_ID OR exames.medico_id = 0, TRUE)
        AND IF (MATRIZ_ID > 0, empresas.matriz = MATRIZ_ID, TRUE)
    ) AS emergencias;

END$$

CREATE PROCEDURE `GetClientes`(EMPRESA_ID integer)
BEGIN
	SELECT
		clientes.id AS ClienteId,
        clientes.nome AS ClienteNome
	FROM clientes
    INNER JOIN empresas ON empresas.id = clientes.empresa_id
    WHERE
		clientes.empresa_id = EMPRESA_ID OR empresas.matriz = EMPRESA_ID;
END$$

CREATE PROCEDURE `CalculaIMC`(EXAME_ID integer)
BEGIN
	UPDATE exames
    SET imc = round(peso / (altura * altura), 2)
    WHERE id = EXAME_ID;
END$$

-- CREATE TRIGGER `CalculaIMC_OnInsert` AFTER INSERT ON `exames` FOR EACH ROW
-- BEGIN
-- 	CALL `CalculaIMC`(NEW.ID);
-- END$$

-- CREATE TRIGGER `CalculaIMC_OnUpdate` AFTER UPDATE ON `exames` FOR EACH ROW
-- BEGIN
-- 	CALL `CalculaIMC`(NEW.ID);
-- END$$

CREATE PROCEDURE `GetLaudosParaDownload`(CLIENTE_ID integer)
BEGIN
	SELECT
		exames.id AS id,
        exames.arquivo_laudo AS arquivo_laudo,
		exames.paciente AS paciente,
		exames.exame_id  AS exame_id
	FROM
		exames
	WHERE
		exames.cliente_id = CLIENTE_ID AND
        exames.ativo = 1 AND
        exames.arquivo_laudo IS NOT NULL AND
        exames.laudo_download_date IS NULL AND
        exames.laudo_date IS NOT NULL;
END$$

CREATE PROCEDURE `GetMedicosDoExame`(TIPO_EXAME_ID integer, MATRIZ_ID integer)
BEGIN
    SELECT mex.medico_id
    FROM medicos_exames mex
    WHERE mex.tipo_exame_id = TIPO_EXAME_ID AND
    mex.medico_id IN (
        SELECT med.id
        FROM medicos med
        WHERE med.situacao = 0 AND
		med.empresa_id IN (
            SELECT id from empresas emp where emp.matriz = MATRIZ_ID
        )
    );
END$$

CREATE PROCEDURE `GetFichas`(EMPRESA_ID integer, DOC varchar(20), PACIENTE varchar(100), DATA_INICIAL datetime, DATA_FINAL datetime)
BEGIN
	SELECT
		fichas.id AS FichaId,
		fichas.numero AS FichaNumero,
		fichas.created_at as FichaData,
		pacientes.nome AS PacienteNome,
		pacientes.doc AS PacienteDoc,
		empresas.id AS EmpresaID,
        empresas.nome AS EmpresaNome,
        clientes.nome AS ClienteNome
	FROM fichas
	INNER JOIN pacientes ON pacientes.id = fichas.paciente_id
	INNER JOIN empresas ON empresas.id = fichas.empresa_id
	INNER JOIN clientes ON clientes.id = fichas.cliente_id
	WHERE
		(empresas.id = EMPRESA_ID OR empresas.matriz = EMPRESA_ID) AND
		pacientes.doc LIKE DOC OR pacientes.nome LIKE PACIENTE OR
        (fichas.data >= DATA_INICIAL AND fichas.data <= DATA_FINAL);
END$$

CREATE PROCEDURE `GetLoginDoUsuario`(EMPRESA_LOGIN varchar(20), USUARIO_LOGIN varchar(20))
BEGIN
	SELECT
		empresas.id AS EmpresaId,
		empresas.login AS EmpresaLogin,
		empresas.nome AS EmpresaNome,
		empresas.matriz AS EmpresaMatriz,
		usuarios.id AS UsuarioId,
		usuarios.login AS UsuarioLogin,
		usuarios.nome AS UsuarioNome,
        usuarios.senha AS UsuarioSenha,
        usuarios.email AS UsuarioEmail,
        usuarios.cpf AS UsuarioCPF,
        usuarios.device_id AS UsuarioDeviceId,
		usuarios.situacao AS UsuarioSituacao,
		usuarios.v2 AS UsuarioV2,
        usuarios.conta_cliente AS ContaClienteId,
        usuarios.conta_medico AS ContaMedicoId,
        usuarios.conta_cliente > 0 AS isContaCliente,
        usuarios.conta_medico > 0 AS isContaMedico,
        (usuarios.conta_cliente + usuarios.conta_medico) = 0 AS isContaAdmin
	FROM
		acessos
	INNER JOIN empresas ON empresas.id = acessos.empresa_id
	INNER JOIN usuarios ON usuarios.id = acessos.usuario_id
	WHERE
		empresas.login = EMPRESA_LOGIN AND usuarios.login = USUARIO_LOGIN;
END$$

CREATE PROCEDURE `GetDominiosDoUsuario`(USUARIO_LOGIN varchar(20))
BEGIN
	SELECT
		empresas.id AS EmpresaId,
		empresas.login AS EmpresaLogin,
		empresas.nome AS EmpresaNome
	FROM
		acessos
	INNER JOIN empresas ON acessos.empresa_id = empresas.id
	INNER JOIN usuarios ON acessos.usuario_id = usuarios.id
	WHERE
		usuarios.login = USUARIO_LOGIN;
END$$

CREATE PROCEDURE `GetEmpresas`(MATRIZ_ID integer)
BEGIN

	SELECT empresas.id AS EmpresaId,
		empresas.login AS EmpresaLogin,
		empresas.nome AS EmpresaNome
	FROM
		empresas
	WHERE
		empresas.matriz = MATRIZ_ID;
END$$

CREATE PROCEDURE `GetPermissoesDoPerfil`(PERFIL_ID integer)
BEGIN
	SELECT
		autorizacaos.id AS AutorizacaoId,
		permissaos.id AS PermissaoId,
		permissaos.recurso AS PermissaoRecurso,
		permissaos.descricao AS PermissaoDescricao,
		autorizacaos.acesso AS PermissaoAcesso
	FROM
		autorizacaos
	INNER JOIN perfils ON autorizacaos.perfil_id = perfils.id
	INNER JOIN permissaos ON autorizacaos.permissao_id = permissaos.id
	WHERE
		perfils.id = PERFIL_ID
	ORDER BY permissaos.id;
END$$

CREATE PROCEDURE `GetExamesDaEmpresa`(EMPRESA_ID integer, ATIVOS integer)
BEGIN
	SELECT
		exames.id AS id,
        exames.cliente_id as cliente_id,
		exames.exame_id  AS exame_id,
		exames.paciente AS paciente,
        tipo_exames.nome AS exame,
        tipo_exames.id AS exame_id,
        clientes.nome AS cliente,
        clientes.mensagem_medicos AS mensagem_medicos,
        empresas.login AS empresa,
        exames.status AS status,
        exames.emergencia AS emergencia,
        exames.arquivo_exame AS arquivo_exame,
        exames.arquivo_laudo AS arquivo_laudo,
        exames.ativo AS ativo,
        exames.abonado AS abonado,
        exames.abonado_medico AS abonado_medico,
        exames.medico_id AS medico,
        exames.pausado AS pausado,
        (select nome from medicos where medicos.id = exames.medico_id) as medico_nome,
        TRUE AS isEmpresa,
       date_format(exames.created_at, '%d/%m/%Y %H:%i:%s') AS exame_data,
       date_format(exames.laudo_date, '%d/%m/%Y %H:%i:%s') AS laudo_data
	FROM
		exames
	INNER JOIN tipo_exames ON tipo_exames.id = exames.exame_id
	INNER JOIN empresas ON empresas.id = exames.empresa_id
	INNER JOIN clientes ON clientes.id = exames.cliente_id
	WHERE
		(exames.empresa_id = EMPRESA_ID  OR empresas.matriz = EMPRESA_ID)
        AND IF (ATIVOS = 0, TRUE, exames.ativo = 1)
	ORDER BY
		id DESC
    LIMIT 20000;
END$$

CREATE PROCEDURE `GetExamesDoCliente`(CLIENTE_ID integer, ATIVOS integer)
BEGIN
	SELECT
		exames.id AS id,
        exames.cliente_id as cliente_id,
		exames.paciente AS paciente,
        tipo_exames.nome AS exame,
        tipo_exames.id AS exame_id,
        clientes.nome AS cliente,
        clientes.mensagem_medicos AS mensagem_medicos,
        empresas.login AS empresa,
        exames.status AS status,
        exames.emergencia AS emergencia,
        exames.arquivo_exame AS arquivo_exame,
        exames.arquivo_laudo AS arquivo_laudo,
        exames.ativo AS ativo,
        exames.abonado AS abonado,
        exames.abonado_medico AS abonado_medico,
        exames.medico_id AS medico,
        exames.pausado AS pausado,
        (select nome from medicos where medicos.id = exames.medico_id) as medico_nome,
        TRUE AS isCliente,
        date_format(exames.created_at, '%d/%m/%Y %H:%i:%s') AS exame_data,
        date_format(exames.laudo_date, '%d/%m/%Y %H:%i:%s') AS laudo_data
	FROM
		exames
	INNER JOIN tipo_exames ON tipo_exames.id = exames.exame_id
	INNER JOIN empresas ON empresas.id = exames.empresa_id
	INNER JOIN clientes ON clientes.id = exames.cliente_id
	WHERE
		(exames.cliente_id = CLIENTE_ID OR exames.recepcionado = CLIENTE_ID)
        AND IF (ATIVOS = 0, TRUE, exames.ativo = 1)
	ORDER BY
		id DESC
    LIMIT 20000;
END$$

CREATE PROCEDURE `GetExamesDoMedico`(MEDICO_ID integer, ATIVOS integer)
BEGIN
	SELECT
		exames.id AS id,
        exames.cliente_id as cliente_id,
		exames.paciente AS paciente,
        tipo_exames.nome AS exame,
        tipo_exames.id AS exame_id,
        clientes.nome AS cliente,
        clientes.mensagem_medicos AS mensagem_medicos,
        empresas.login AS empresa,
        exames.status AS status,
        exames.emergencia AS emergencia,
        exames.arquivo_exame AS arquivo_exame,
        exames.arquivo_laudo AS arquivo_laudo,
        exames.ativo AS ativo,
        exames.abonado AS abonado,
        exames.abonado_medico AS abonado_medico,
        exames.medico_id AS medico,
        exames.pausado AS pausado,
        (select nome from medicos where medicos.id = exames.medico_id) as medico_nome,
        TRUE AS isMedico,
       date_format(exames.created_at, '%d/%m/%Y %H:%i:%s') AS exame_data,
       date_format(exames.laudo_date, '%d/%m/%Y %H:%i:%s') AS laudo_data
	FROM
		exames
	INNER JOIN tipo_exames ON tipo_exames.id = exames.exame_id
	INNER JOIN empresas ON empresas.id = exames.empresa_id
	INNER JOIN clientes ON clientes.id = exames.cliente_id
	WHERE
		exames.medico_id = MEDICO_ID AND IF (ATIVOS = 0, TRUE, exames.ativo = 1)
	ORDER BY
		id DESC
    LIMIT 20000;
END$$

CREATE PROCEDURE `GetPesquisaAvancada`(
    EMPRESA_ID integer,
    DATA_INICIAL datetime,
    DATA_FINAL datetime,
    CLIENTE_ID integer,
    MEDICO_ID integer,
    STATUS_EXAME integer
)
BEGIN
	SELECT
		exames.id AS id,
        exames.cliente_id as cliente_id,
		exames.paciente AS paciente,
        tipo_exames.nome AS exame,
        tipo_exames.id AS exame_id,
        clientes.nome AS cliente,
        empresas.login AS empresa,
        exames.status AS status,
        exames.emergencia AS emergencia,
        exames.arquivo_exame AS arquivo_exame,
        exames.arquivo_laudo AS arquivo_laudo,
        exames.ativo AS ativo,
        exames.abonado AS abonado,
        exames.abonado_medico AS abonado_medico,
        exames.medico_id AS medico,
        exames.pausado AS pausado,
        (select nome from medicos where medicos.id = exames.medico_id) as medico_nome,
        TRUE AS isCliente,
        date_format(exames.created_at, '%d/%m/%Y %H:%i:%s') AS exame_data,
        date_format(exames.laudo_date, '%d/%m/%Y %H:%i:%s') AS laudo_data
	FROM
		exames
	INNER JOIN tipo_exames ON tipo_exames.id = exames.exame_id
	INNER JOIN empresas ON empresas.id = exames.empresa_id
	INNER JOIN clientes ON clientes.id = exames.cliente_id
	WHERE
        (exames.empresa_id = EMPRESA_ID OR empresas.matriz = EMPRESA_ID) AND
        exames.created_at >= DATA_INICIAL AND
        exames.created_at <= DATA_FINAL AND
        IF (STATUS_EXAME >= 0, exames.status = STATUS_EXAME, TRUE) AND
        IF (MEDICO_ID > 0, exames.medico_id = MEDICO_ID, TRUE) AND
        IF (CLIENTE_ID > 0, exames.cliente_id = CLIENTE_ID, TRUE)
	ORDER BY
		emergencia DESC;
END$$

CREATE PROCEDURE `GetExameParaLaudar`(EXAME_ID integer)
BEGIN
	SELECT
		exames.id AS laudar_id,
        exames.status AS status,
        exames.cliente_id as cliente_id,
        exames.id AS laudar_numero,
		exames.paciente AS laudar_paciente,
		exames.fumante AS laudar_fumante,
        exames.sexo AS laudar_sexo,
        exames.peso AS laudar_peso,
        exames.altura AS laudar_altura,
        exames.imc AS laudar_imc,
        exames.observacoes AS laudar_observacoes,
        exames.medico_id AS laudar_medico_id,
        exames.contratante as laudar_contratante,
        tipo_exames.nome AS laudar_tipo_exame,
        tipo_exames.id AS laudar_tipo_exame_id,
        tipo_exames.desativar_modelo AS desativar_modelo,
        tipo_exames.desativar_upload AS desativar_upload,
        clientes.telas AS cliente_telas,
        clientes.mensagem_medicos AS mensagem_medicos,
        exames.atendimento AS laudar_atendimento,
        motivo_exames.nome AS laudar_motivo,
        exames.arquivo_imagem AS arquivo_imagem,
        exames.arquivo_exame AS arquivo_exame,
        exames.arquivo_laudo AS arquivo_laudo,
        exames.modelo_content AS modelo,
        exames.pausado AS pausado,
        date_format(exames.nascimento, '%d/%m/%Y') AS laudar_nascimento,
        date_format(exames.exame_date, '%d/%m/%Y') AS laudar_data
	FROM
		exames
	INNER JOIN tipo_exames ON tipo_exames.id = exames.exame_id
	INNER JOIN motivo_exames ON motivo_exames.id = exames.motivo_id
    INNER JOIN clientes ON clientes.id = exames.cliente_id
	WHERE
		exames.id = EXAME_ID;
END$$

CREATE PROCEDURE `GetLaudosDoMedico`(MEDICO_ID integer, DATA_INICIAL datetime, DATA_FINAL datetime)
BEGIN
    SELECT
        ex.id AS exame_id,
        ex.paciente AS paciente,
        ex.created_at AS exame_data,
        ex.laudo_date AS laudo_data,
        em.nome as empresa,
        cl.nome AS cliente_nome,
        te.nome AS tipo_exame,
        me.nome AS medico_nome,
        concat("R$ ", format(if (
            ex.abonado_medico = 1 OR ex.ativo = 0,
            0,
            IFNULL((SELECT pm.preco FROM preco_medicos pm WHERE
                pm.empresa_id = me.empresa_id AND
                pm.medico_id = ex.medico_id AND
                pm.tipo_exame_id = ex.exame_id AND
                ex.created_at >= pm.vigencia_de AND ex.created_at <= pm.vigencia_ate
                limit 1
            ), 0)
        ), 2, 'pt_BR')) AS preco_laudo
    FROM exames ex
    INNER JOIN empresas em
    	ON em.id = ex.empresa_id
    INNER JOIN clientes cl
        ON cl.id = ex.cliente_id
    INNER JOIN medicos me
        ON me.id = ex.medico_id
    INNER JOIN tipo_exames te
        ON te.id = ex.exame_id
    WHERE
        ex.status = 1 AND
        ex.medico_id = MEDICO_ID AND
        ex.created_at >= DATA_INICIAL AND
        ex.created_at <= DATA_FINAL;
END$$

CREATE PROCEDURE `GetLaudosDoCliente`(EMPRESA_ID integer, CLIENTE_ID integer, DATA_INICIAL datetime, DATA_FINAL datetime)
BEGIN
    SELECT
        exame_id,
        exame_data,
        laudo_data,
        upper(paciente) as paciente,
        upper(tipo_exame) as tipo_exame,
        upper(empresa) as empresa,
        upper(cliente) as cliente,
        upper(medico) as medico,
        upper(contratante) as contratante,
        concat("R$ ", format(if(
            abonado = 1 OR ativo = 0,
            0,
            if(
                num <= limite1,
                if (
                    cobranca1 = 'PACOTE',
                    preco1 / limite1,
                    preco1
                ),
                preco2
            )
        ), 2, 'pt_BR')) as preco
    FROM (
        SELECT
            (@cnt := @cnt + 1) AS num,
            ex.id as exame_id,
            te.nome as tipo_exame,
            em.nome as empresa,
            cl.nome as cliente,
            ex.paciente as paciente,
            ex.created_at as exame_data,
            ex.laudo_date as laudo_data,
            ex.abonado as abonado,
            ex.ativo as ativo,
            ex.contratante as contratante,
            (select me.nome from medicos me where
                me.id = ex.medico_id
            ) as medico,
            (select pc.cobranca1 from preco_clientes pc where
                pc.empresa_id = ex.empresa_id and
                pc.cliente_id = ex.cliente_id and
                pc.tipo_exame_id = ex.exame_id and
                ex.created_at >= pc.vigencia_de and ex.created_at <= pc.vigencia_ate
                limit 1
            ) as cobranca1,
            ifnull((select pc.ate1 from preco_clientes pc where
                pc.empresa_id = ex.empresa_id and
                pc.cliente_id = ex.cliente_id and
                pc.tipo_exame_id = ex.exame_id and
                ex.created_at >= pc.vigencia_de and ex.created_at <= pc.vigencia_ate
                limit 1
            ), 0) as limite1,
            ifnull((select pc.preco1 from preco_clientes pc where
                pc.empresa_id = ex.empresa_id and
                pc.cliente_id = ex.cliente_id and
                pc.tipo_exame_id = ex.exame_id and
                ex.created_at >= pc.vigencia_de and ex.created_at <= pc.vigencia_ate
                limit 1
            ), 0) as preco1,
            (select pc.cobranca2 from preco_clientes pc where
                pc.empresa_id = ex.empresa_id and
                pc.cliente_id = ex.cliente_id and
                pc.tipo_exame_id = ex.exame_id and
                ex.created_at >= pc.vigencia_de and ex.created_at <= pc.vigencia_ate
                limit 1
            ) as cobranca2,
            ifnull((select pc.ate2 from preco_clientes pc where
                pc.empresa_id = ex.empresa_id and
                pc.cliente_id = ex.cliente_id and
                pc.tipo_exame_id = ex.exame_id and
                ex.created_at >= pc.vigencia_de and ex.created_at <= pc.vigencia_ate
                limit 1
            ), 0) as limite2,
            ifnull((select pc.preco2 from preco_clientes pc where
                pc.empresa_id = ex.empresa_id and
                pc.cliente_id = ex.cliente_id and
                pc.tipo_exame_id = ex.exame_id and
                ex.created_at >= pc.vigencia_de and ex.created_at <= pc.vigencia_ate
                limit 1
            ), 0) as preco2
        FROM exames AS ex
        CROSS JOIN (SELECT @cnt := 0) AS dummy
        INNER JOIN empresas em ON em.id = ex.empresa_id
        INNER JOIN clientes cl ON cl.id = ex.cliente_id
        INNER JOIN tipo_exames te ON te.id = ex.exame_id
        WHERE
            ex.status = 1 AND
            IF(CLIENTE_ID > 0, ex.cliente_id = CLIENTE_ID, true) AND
            IF(EMPRESA_ID > 0, ex.empresa_id = EMPRESA_ID, true) AND
            ex.created_at >= DATA_INICIAL AND
            ex.created_at <= DATA_FINAL
    ) as result;

END$$

CREATE PROCEDURE `GetFaturamentoExportar`(EMPRESA_ID integer, DATA_INICIAL datetime, DATA_FINAL datetime, EXPORTAR integer)
BEGIN
    SELECT
        cliente,
        tipo_exame,
        quantidade,
        if (preco_pacote > 0, preco_pacote + saldo_unitario * preco_unitario, quantidade * preco_unitario) as total
    FROM
        (SELECT
            cliente,
            tipo_exame,
            quantidade,
            if (preco_pacote1 > 0, preco_pacote1, preco_pacote2) as preco_pacote,
            if (preco_unitario1 > 0, preco_unitario1, preco_unitario2) as preco_unitario,
            if (saldo_unitario1 > 0, saldo_unitario1, saldo_unitario2) as saldo_unitario
        FROM
            (SELECT
                cliente,
                tipo_exame,
                quantidade,
                abonados,
                if (cobranca1 = 'PACOTE', preco1, 0) as preco_pacote1,
                if (cobranca1 = 'UNITARIO', preco1, 0) as preco_unitario1,
                if (cobranca2 = 'PACOTE', preco2, 0) as preco_pacote2,
                if (cobranca2 = 'UNITARIO', preco2, 0) as preco_unitario2,
                if (cobranca1 = 'PACOTE', if((quantidade - abonados) > limite1, quantidade - abonados - limite1, 0), 0) as saldo_unitario1,
                if (cobranca2 = 'PACOTE', if((quantidade - abonados) > limite2, quantidade - abonados - limite2, 0), 0) as saldo_unitario2
            FROM
            (SELECT
                cliente,
                tipo_exame,
                count(id) - sum(abonado) as quantidade,
                sum(abonado) as abonados,
                cobranca1, limite1, preco1, cobranca2, limite2, preco2
            FROM
                (SELECT
                    ex.id as id,
                    ex.created_at,
                    if(EXPORTAR = 1, cl.id, concat(cl.nome, " (", cl.id, ")")) as cliente,
                    if(EXPORTAR = 1, te.id, te.nome) as tipo_exame,
                    ex.abonado,
                    ex.exame_id,
                    ex.cliente_id,
                    (select pc.cobranca1 from preco_clientes pc where
                        pc.empresa_id = ex.empresa_id and
                        pc.cliente_id = ex.cliente_id and
                        pc.tipo_exame_id = ex.exame_id and
                        date(ex.created_at) >= pc.vigencia_de and date(ex.created_at) <= pc.vigencia_ate
                        limit 1
                    ) as cobranca1,
                    ifnull((select pc.ate1 from preco_clientes pc where
                        pc.empresa_id = ex.empresa_id and
                        pc.cliente_id = ex.cliente_id and
                        pc.tipo_exame_id = ex.exame_id and
                        date(ex.created_at) >= pc.vigencia_de and date(ex.created_at) <= pc.vigencia_ate
                        limit 1
                    ), 0) as limite1,
                    ifnull((select pc.preco1 from preco_clientes pc where
                        pc.empresa_id = ex.empresa_id and
                        pc.cliente_id = ex.cliente_id and
                        pc.tipo_exame_id = ex.exame_id and
                        date(ex.created_at) >= pc.vigencia_de and date(ex.created_at) <= pc.vigencia_ate
                        limit 1
                    ), 0) as preco1,
                    (select pc.cobranca2 from preco_clientes pc where
                        pc.empresa_id = ex.empresa_id and
                        pc.cliente_id = ex.cliente_id and
                        pc.tipo_exame_id = ex.exame_id and
                        date(ex.created_at) >= pc.vigencia_de and date(ex.created_at) <= pc.vigencia_ate
                        limit 1
                    ) as cobranca2,
                    ifnull((select pc.ate2 from preco_clientes pc where
                        pc.empresa_id = ex.empresa_id and
                        pc.cliente_id = ex.cliente_id and
                        pc.tipo_exame_id = ex.exame_id and
                        date(ex.created_at) >= pc.vigencia_de and date(ex.created_at) <= pc.vigencia_ate
                        limit 1
                    ), 0) as limite2,
                    ifnull((select pc.preco2 from preco_clientes pc where
                        pc.empresa_id = ex.empresa_id and
                        pc.cliente_id = ex.cliente_id and
                        pc.tipo_exame_id = ex.exame_id and
                        date(ex.created_at) >= pc.vigencia_de and date(ex.created_at) <= pc.vigencia_ate
                        limit 1
                    ), 0) as preco2
                FROM exames AS ex
                INNER JOIN clientes cl ON cl.id = ex.cliente_id
                INNER JOIN tipo_exames te ON te.id = ex.exame_id
                WHERE
                    ex.status = 1 AND
                    ex.ativo = 1 AND
                    ex.empresa_id = EMPRESA_ID AND
                    ex.created_at >= DATA_INICIAL AND
                    ex.created_at <= DATA_FINAL
                ) as temp
            GROUP BY cliente, tipo_exame, cobranca1, limite1, preco1, cobranca2, limite2, preco2
            ORDER BY cliente
            ) as temp1
        ) as temp2)
    as result;
END$$

CREATE PROCEDURE `GetFaturamentoPacotes`(EMPRESA_ID integer, CLIENTE_ID integer, DATA_INICIAL datetime, DATA_FINAL datetime)
BEGIN
	select
        upper(cl.nome) as cliente,
        upper(te.nome) as exame,
        total as exames,
        abonado,
        cobrado,
        pacote_ate,
        pacote_preco,
        preco_excedente,
        if(cobrado > pacote_ate, cobrado - pacote_ate, 0) as exames_excedente,
        if(cobrado > pacote_ate, (cobrado - pacote_ate) * preco_excedente, 0) as total_excedente,
        pacote_preco + if(cobrado > pacote_ate, (cobrado - pacote_ate) * preco_excedente, 0) as total_exames
	from
	(select
		t1.empresa_id,
		t1.cliente_id,
        t1.tipo_exame_id,
        t1.total,
        abonado,
        t1.cobrado,
        t1.ate1,
        t1.preco1,
        t1.preco2,
		(ate1 / total) as pacote_ate,
		(preco1 / total) as pacote_preco,
		(preco2 / total) as preco_excedente
	from
		(select
			ex.empresa_id as empresa_id,
			ex.cliente_id as cliente_id,
			ex.exame_id as tipo_exame_id,
			count(ex.id) as total,
			sum(if(ex.abonado = 1 OR ex.ativo = 0, 1, 0)) as abonado,
			sum(if(ex.abonado = 0 AND ex.ativo = 1, 1, 0)) as cobrado,
			sum(ifnull((select pc.ate1 from preco_clientes pc where
				pc.empresa_id = ex.empresa_id and
				pc.cliente_id = ex.cliente_id and
				pc.tipo_exame_id = ex.exame_id and
				ex.created_at >= pc.vigencia_de and ex.created_at <= pc.vigencia_ate
                limit 1
			), 0)) as ate1,
			sum(ifnull((select pc.preco1 from preco_clientes pc where
				pc.empresa_id = ex.empresa_id and
				pc.cliente_id = ex.cliente_id and
				pc.tipo_exame_id = ex.exame_id and
				ex.created_at >= pc.vigencia_de and ex.created_at <= pc.vigencia_ate
                limit 1
			), 0)) as preco1,
			sum(ifnull((select pc.preco2 from preco_clientes pc where
				pc.empresa_id = ex.empresa_id and
				pc.cliente_id = ex.cliente_id and
				pc.tipo_exame_id = ex.exame_id and
				ex.created_at >= pc.vigencia_de and ex.created_at <= pc.vigencia_ate
                limit 1
			), 0)) as preco2
		from exames ex
		where
            ex.empresa_id = EMPRESA_ID AND ex.status = 1 AND
            IF(CLIENTE_ID > 0, ex.cliente_id = CLIENTE_ID, true) AND
            ex.created_at >= DATA_INICIAL AND
            ex.created_at <= DATA_FINAL
		group by
			ex.empresa_id, ex.cliente_id, ex.exame_id) as t1
		) as t2
	inner join clientes cl on cl.id = t2.cliente_id
	inner join tipo_exames te on te.id = t2.tipo_exame_id;
END$$

CREATE PROCEDURE `GetSaldoDoCliente`(CLIENTE_ID integer, DATA_DE datetime, DATA_ATE datetime)
BEGIN
set @total = 0;
select
	t.id as id,
    date_format(t.data, '%d/%m/%Y') as data,
    t.cliente as cliente,
    t.descricao as descricao,
    t.valor as valor,
    t.saldo as saldo
FROM
(select
	cc.id as id,
	cc.data as data,
	cl.nome as cliente,
	cc.descricao as descricao,
	cc.valor as valor,
	(@total := @total + cc.valor) as saldo
from contas cc
inner
	join clientes cl on cc.cliente_id = cl.id
where cc.cliente_id = CLIENTE_ID AND cc.data <= DATA_ATE
order by date(cc.data) asc
) as t
where
      t.data >= DATA_DE and t.data <= DATA_ATE;
END$$

CREATE PROCEDURE `GetWXMLIncompleto`(EMPRESA_ID integer)
BEGIN
	SELECT
		exames.id AS id,
        exames.cliente_id as cliente_id,
		exames.paciente AS paciente,
        tipo_exames.nome AS exame,
        tipo_exames.id AS exame_id,
        clientes.nome AS cliente,
        empresas.login AS empresa,
        exames.status AS status,
        exames.emergencia AS emergencia,
        exames.arquivo_exame AS arquivo_exame,
        exames.arquivo_laudo AS arquivo_laudo,
        exames.ativo AS ativo,
        exames.abonado AS abonado,
        exames.abonado_medico AS abonado_medico,
        exames.medico_id AS medico,
        exames.pausado AS pausado,
        (select nome from medicos where medicos.id = exames.medico_id) as medico_nome,
        TRUE AS isCliente,
        date_format(exames.created_at, '%d/%m/%Y %H:%i:%s') AS exame_data,
        date_format(exames.laudo_date, '%d/%m/%Y %H:%i:%s') AS laudo_data
	FROM
		exames
	INNER JOIN tipo_exames ON tipo_exames.id = exames.exame_id
	INNER JOIN empresas ON empresas.id = exames.empresa_id
	INNER JOIN clientes ON clientes.id = exames.cliente_id
	WHERE
        (exames.empresa_id = EMPRESA_ID OR empresas.matriz = EMPRESA_ID) AND
        exames.status = 0 AND exames.ativo = 1 AND
		ifnull(lower(exames.arquivo_exame), '') like '%.wxml%' AND
        ifnull(lower(exames.arquivo_imagem), '') not like '%.jpg%'
	ORDER BY
		exames.id DESC;
END$$
