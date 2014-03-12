alter table `NewsletterLanguage` alter column title_size set default 24;

alter table `NewsletterArticle` alter column title_size set default 24;

alter table `ArticleButton` alter column width set default 125;
alter table `ArticleButton` alter column height set default 37;
alter table `ArticleButton` alter column line_height set default 37;
alter table `ArticleButton` alter column addons set default 0;
alter table `ArticleButton` alter column style set default '#83B817';