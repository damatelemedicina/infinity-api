USE `infinity`;
DROP procedure IF EXISTS `GetLoginDoUsuario`;
DROP procedure IF EXISTS `GetEmpresas`;
DROP procedure IF EXISTS `GetDominiosDoUsuario`;
DROP procedure IF EXISTS `GetPermissoesDoPerfil`;
DROP procedure IF EXISTS `GetFichas`;

DELIMITER $$

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
		( empresas.id = EMPRESA_ID OR empresas.matriz = EMPRESA_ID ) AND
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
		usuarios.v2 AS UsuarioV2
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
	
	/**
    -- FILIAIS ATUAIS DESTA EMPRESA i.e TODAS QUE TEM O ID DESTA EMPRESA EXCETO ELA PRÓPRIA
	SELECT 
		empresas.id AS EmpresaId, 
		empresas.login AS EmpresaLogin, 
		empresas.nome AS EmpresaNome
	FROM 
		empresas
	WHERE 
		empresas.matriz = EMPRESA_ID AND
        empresas.id <> empresas.matriz;
    
    -- POTENCIAIS FILIAIS DESTA EMPRESA i.e. TODAS MENOS A MATRIZ DESTA EMPRESA E ELA PRÓPRIA
	SELECT 
		empresas.id AS EmpresaId, 
		empresas.login AS EmpresaLogin, 
		empresas.nome AS EmpresaNome
	FROM
		empresas
    WHERE
		empresas.matriz = MATRIZ_ID AND
        empresas.id <> empresas.matriz AND
        empresas.id <> EMPRESA_ID;
    */

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
	ORDER BY permissaos.descricao;
END$$

/**
CREATE TABLE `ficha_procedimentos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `ficha_id` bigint unsigned NOT NULL,
  `nome` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ordem` int NOT NULL,
  `tamanho` int NOT NULL,
  `opcoes` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '',
  `obrigatorio` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ficha_procedimentos_ficha_id_foreign` (`ficha_id`),
  CONSTRAINT `ficha_procedimentos_ficha_id_foreign` FOREIGN KEY (`ficha_id`) REFERENCES `fichas` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
**/
