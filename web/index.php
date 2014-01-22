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
}

$dbOptions = require_once __DIR__.'/../config/db.php';

/* Setup ORM */
use Illuminate\Database\Capsule\Manager as Capsule;
$capsule = new Capsule;
$capsule->addConnection($dbOptions);
$capsule->bootEloquent();

/* Setup Silex */
$app = new App();
$app['debug'] = true;

/* Define Routes */
$app->get('/languages', function() use ($app) {
	return $app->models(PS\Model\Language::all());
});

$app->post('/languages/new', function() use ($app) {
	$language = new PS\Model\Language;
	return $app->ifSaved($language);
});

/* Rock On! */
$app->run();