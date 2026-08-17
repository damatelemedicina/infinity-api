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
