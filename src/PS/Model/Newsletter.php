<?php

namespace PS\Model;

class Newsletter extends \Illuminate\Database\Eloquent\Model
{
	protected $table = 'Newsletter';
	protected $fillable = array('number', 'date');
	public $timestamps = false;

	public function languages()
	{
		return $this->hasMany('PS\Model\NewsletterLanguage');
	}
};