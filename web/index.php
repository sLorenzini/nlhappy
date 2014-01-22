<?php

@ini_set('display_errors', 'on');

require_once __DIR__.'/../vendor/autoload.php';

class App extends \Silex\Application
{
	public function models($data)
	{
		return $this->json(array('success' => true, 'data' => $data->toArray()));
	}

	public function ifSaved($model)
	{
		try {
			$model->save();
			return $this->models($model);
		}
		catch (\Illuminate\Database\QueryException $e) {
			return $this->json(array('success' => false, 'error_message' => $e->getMessage()));
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
			return $this->json(array('success' => false, 'error_message' => 'Could not find model to update.'));
		}
	}
}

$dbOptions = require_once __DIR__.'/../config/db.php';

use Symfony\Component\HttpFoundation\Request;

/* Setup ORM */
use Illuminate\Database\Capsule\Manager as Capsule;

$capsule = new Capsule;
$capsule->addConnection($dbOptions);
$capsule->bootEloquent();

/* Setup Silex */
$app = new App();
$app['debug'] = true;

$app->before(function (Request $request) {
	$data = json_decode($request->getContent(), true);
    $request->request->replace(is_array($data) ? $data : array());
});

/* Define Routes */

// Languages

// List languages
$app->get('/languages', function() use ($app) {
	return $app->models(PS\Model\Language::all());
});

// Create language
$app->post('/languages', function(Request $request) use ($app) {
	return $app->ifSaved(new PS\Model\Language($request->request->all()));
});

// Update language
$app->post('/languages/{language_id}', function(Request $request, $language_id) use ($app) {
	return $app->update(PS\Model\Language::find($language_id), $request->request->all());
});

/* Rock On! */
$app->run();