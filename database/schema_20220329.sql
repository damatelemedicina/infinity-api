ALTER TABLE `infinity`.`pacientes` 
DROP COLUMN `peso`,
DROP COLUMN `altura`;
ALTER TABLE `infinity`.`pacientes` 
CHANGE COLUMN `empresa` `empresa` VARCHAR(255) NULL ,
CHANGE COLUMN `funcao` `funcao` VARCHAR(255) NULL ;
ALTER TABLE `infinity`.`pacientes` 
ADD UNIQUE INDEX `doc_UNIQUE` (`doc` ASC) VISIBLE;
;