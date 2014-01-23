<?php

namespace PS\Model;

class NewsletterArticle extends \Illuminate\Database\Eloquent\Model
{
	protected $table = 'NewsletterArticle';
	protected $fillable = array(
		'type',
		'title', 
		'title_size',
		'position',
		'body',
		'image_url',
		'image_anchor',
		'image_alt'
	);
	public $timestamps = false;

	public function newsletterLanguage()
	{
		return $this->belongsTo('PS\Model\NewsletterLanguage');
	}
};