USE `infinity`;
ALTER TABLE `infinity`.`tipo_exames` 
ADD COLUMN `situacao` INT NULL DEFAULT 0 AFTER `updated_at`;