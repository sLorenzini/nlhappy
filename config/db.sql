CREATE SCHEMA IF NOT EXISTS `nlhappy` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ;
USE `nlhappy` ;

-- -----------------------------------------------------
-- Table `Newsletter`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `Newsletter` ;

CREATE  TABLE IF NOT EXISTS `Newsletter` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `number` INT NOT NULL ,
  `date` DATE NULL ,
  PRIMARY KEY (`id`) ,
  UNIQUE INDEX `number_UNIQUE` (`number` ASC) )
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `Language`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `Language` ;

CREATE  TABLE IF NOT EXISTS `Language` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `code` VARCHAR(45) NOT NULL ,
  `name` VARCHAR(256) NOT NULL,
  PRIMARY KEY (`id`) ,
  UNIQUE INDEX `code_UNIQUE` (`code` ASC) )
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `NewsletterLanguage`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `NewsletterLanguage` ;

CREATE  TABLE IF NOT EXISTS `NewsletterLanguage` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `newsletter_id` INT NOT NULL ,
  `language_id` INT NOT NULL COMMENT '	' ,
  `title` VARCHAR(256) NOT NULL ,
  `title_size` INT NOT NULL ,
  `edito` TEXT NOT NULL ,
  PRIMARY KEY (`id`) ,
  UNIQUE INDEX `NewsletterLanguage` (`newsletter_id` ASC, `language_id` ASC) ,
  INDEX `fk_NewsletterLanguage_1_idx` (`language_id` ASC) ,
  INDEX `fk_NewsletterLanguage_2_idx` (`newsletter_id` ASC) ,
  CONSTRAINT `fk_NewsletterLanguage_1`
    FOREIGN KEY (`language_id` )
    REFERENCES `Language` (`id` )
    ON DELETE CASCADE
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_NewsletterLanguage_2`
    FOREIGN KEY (`newsletter_id` )
    REFERENCES `Newsletter` (`id` )
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `NewsletterArticle`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `NewsletterArticle` ;

CREATE  TABLE IF NOT EXISTS `NewsletterArticle` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `newsletter_language_id` INT NOT NULL ,
  `type` VARCHAR(45) NOT NULL ,
  `position` INT NOT NULL ,
  `title` VARCHAR(256) NOT NULL ,
  `title_size` INT NOT NULL ,
  `body` TEXT NOT NULL ,
  `image_url` VARCHAR(256) NOT NULL ,
  `image_anchor` VARCHAR(256) NOT NULL ,
  `image_alt` VARCHAR(256) NOT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_NewsletterArticle_1_idx` (`newsletter_language_id` ASC) ,
  UNIQUE INDEX `index3` (`type` ASC, `position` ASC, `newsletter_language_id` ASC) ,
  CONSTRAINT `fk_NewsletterArticle_1`
    FOREIGN KEY (`newsletter_language_id` )
    REFERENCES `NewsletterLanguage` (`id` )
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `ArticleButton`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `ArticleButton` ;

CREATE  TABLE IF NOT EXISTS `ArticleButton` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `newsletter_article_id` INT NOT NULL ,
  `title` VARCHAR(64) NULL ,
  `style` VARCHAR(45) NULL ,
  `position` INT NOT NULL ,
  `url` VARCHAR(256) NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_ArticleButton_1_idx` (`newsletter_article_id` ASC) ,
  UNIQUE INDEX `index3` (`newsletter_article_id` ASC, `position` ASC) ,
  CONSTRAINT `fk_ArticleButton_1`
    FOREIGN KEY (`newsletter_article_id` )
    REFERENCES `NewsletterArticle` (`id` )
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB;

-- FIXTURES --

INSERT INTO Language (`code`, `name`)
VALUES
('en', 'English'),
('fr', 'French'),
('de', 'German'),
('pl', 'Polish'),
('ru', 'Russian'),
('it', 'Italian'),
('es', 'Spanish'),
('br', 'Brazilian')