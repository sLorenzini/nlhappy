<?php

namespace PS\Model;

class MessageTranslation extends \Illuminate\Database\Eloquent\Model
{
	protected $table = 'MessageTranslation';
	protected $fillable = array('translation');
	public $timestamps = false;

	public function message()
	{
		return $this->belongsTo('PS\Model\Message');
	}

	public function language()
	{
		return $this->belongsTo('PS\Model\Language');
	}
};