<?php

namespace PS\Model;

class ArticleButton extends \Illuminate\Database\Eloquent\Model
{
	protected $table = 'ArticleButton';
	protected $fillable = array(
		'url',
		'title',
		'style',
		'position'
	);
	public $timestamps = false;

	public function article()
	{
		return $this->belongsTo('PS\Model\NewsletterArticle', 'newsletter_article_id');
	}
};