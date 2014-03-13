alter table ArticleButton convert to character set utf8 collate utf8_unicode_ci;
alter table ArticleLanguage convert to character set utf8 collate utf8_unicode_ci;
alter table Language convert to character set utf8 collate utf8_unicode_ci;
/* Diminish index size, in UTF8 256 becomes too long, damn!*/
alter table Message drop index `Message_mkey`;
alter table Message add unique index `Message_mkey` (mkey (128));
alter table Message convert to character set utf8 collate utf8_unicode_ci;
alter table MessageTranslation convert to character set utf8 collate utf8_unicode_ci;
alter table Newsletter convert to character set utf8 collate utf8_unicode_ci;
alter table NewsletterArticle convert to character set utf8 collate utf8_unicode_ci;
alter table NewsletterLanguage convert to character set utf8 collate utf8_unicode_ci;