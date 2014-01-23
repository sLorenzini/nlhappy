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
			return $this->yay($data->toArray());
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
}