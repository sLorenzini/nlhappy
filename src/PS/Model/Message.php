<?php

namespace PS\Model;

class Message extends \Illuminate\Database\Eloquent\Model
{
	protected $table = 'Message';
	protected $fillable = array('mkey');
	public $timestamps = false;

	public function translations()
	{
		return $this->hasMany('PS\Model\MessageTranslation');
	}
};