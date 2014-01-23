<?php

namespace PS\Model;

class NewsletterLanguage extends \Illuminate\Database\Eloquent\Model
{
	protected $table = 'NewsletterLanguage';
	protected $fillable = array('title', 'title_size', 'edito');
	public $timestamps = false;

	public function newsletter()
	{
		return $this->belongsTo('PS\Model\Newsletter');
	}

	public function language()
	{
		return $this->belongsTo('PS\Model\Language');
	}
};