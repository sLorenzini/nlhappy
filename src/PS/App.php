<?php

namespace PS;

class App extends \Silex\Application
{
	public function oops($message)
	{
		return $this->json(array('success' => false, 'error_message' => $message));
	}

	public function yay($data)
	{
		return $this->json(array('success' => true, 'data' => $data));
	}

	public function models($data)
	{
		if ($data)
		{
			if (is_array($data))
			{
				return $this->yay(array_map(function($model) {
					return $model->toArray();
				}, $data));
			}
			else
			{
				return $this->yay($data->toArray());
			}
		}
		else
		{
			return $this->oops('Could not find object.');
		}
	}

	public function ifSaved($model)
	{
		try {
			$model->save();
			return $this->models($model);
		}
		catch (\Illuminate\Database\QueryException $e) {
			return $this->oops($e->getMessage());
		}
	}

	public function ifDeleted($model)
	{
		if ($model)
		{
			$model->delete();
			return $this->yay($model->toArray());
		}
		else
		{
			return $this->oops('Could not find object to delete.');
		}
	}

	public function update($model, $data)
	{
		if ($model)
		{
			$model->fill($data);
			return $this->ifSaved($model);
		}
		else
		{
			return $this->oops('Could not find model to update.');
		}
	}

	public function getNewsletterLanguage($newsletter_id, $language_code)
	{
		return \PS\Model\NewsletterLanguage::whereHas('language', function($q) use ($newsletter_id, $language_code) {
			$q->where('code', $language_code);
		})->where('newsletter_id', $newsletter_id)->first();
	}
}