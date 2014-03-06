ALTER TABLE `Language`
ADD COLUMN `locale` VARCHAR(16) AFTER `code`;

UPDATE `Language` SET locale = 'en_US' WHERE code = 'en';
UPDATE `Language` SET locale = 'fr_FR' WHERE code = 'fr';
UPDATE `Language` SET locale = 'de_DE' WHERE code = 'de';
UPDATE `Language` SET locale = 'pl_PL' WHERE code = 'pl';
UPDATE `Language` SET locale = 'ru_RU' WHERE code = 'ru';
UPDATE `Language` SET locale = 'it_IT' WHERE code = 'it';
UPDATE `Language` SET locale = 'es_ES' WHERE code = 'es';
UPDATE `Language` SET locale = 'pt_BR' WHERE code = 'br';