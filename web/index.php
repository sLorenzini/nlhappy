<?php


function flog($message)
{
	static $path = '/tmp/flog.log';
	$h = fopen($path, 'a');
	if ($h)
	{
		fwrite($h, '[FLOG - '.date("D M d, Y G:i:s").']: '.$message."\n");
		fflush($h);
		fclose($h);
	}
}

@ini_set('display_errors', 'on');

require_once __DIR__.'/../vendor/autoload.php';

$dbOptions = require_once __DIR__.'/../config/db.php';

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/* Setup ORM */
use Illuminate\Database\Capsule\Manager as Capsule;

$capsule = new Capsule;
$capsule->addConnection($dbOptions);
$capsule->bootEloquent();

/* Setup Silex */
$app = new PS\App();
$app['debug'] = true;

$app->before(function (Request $request) {
	$data = json_decode($request->getContent(), true);
    $request->request->replace(is_array($data) ? $data : array());
});
$app->after(function (Request $request, Response $response) {
	if ($jsonp_callback = $request->get('jsonp_callback'))
	{
		$response->setCallback($jsonp_callback);
	}
	else
	{
		$response->headers->set('Access-Control-Allow-Origin', '*');
		$response->headers->set('Access-Control-Allow-Headers', 'Content-Type');
	}
});

// We need to reply to OPTIONS too, for CORS.
$app->match('{url}', function ($url, Request $request) use ($app) {
	return $app->json(array());
})->assert('url', '.+')->method('OPTIONS');

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

// Delete language
$app->post('/languages/{language_id}/delete', function(Request $request, $language_id) use ($app) {
	return $app->ifDeleted(PS\Model\Language::find($language_id));
});


// Newsletters

// List newsletters
$app->get('/newsletters', function() use ($app) {
	return $app->models(PS\Model\Newsletter::all());
});

// Get newsletters
$app->get('/newsletters/{newsletter_id}', function($newsletter_id) use ($app) {
	$nl = PS\Model\Newsletter::with('languages')->find($newsletter_id);
	return $app->models($nl);
});

// Create newsletter
$app->post('/newsletters', function(Request $request) use ($app) {
	$params = $request->request->all();
	$params['number'] = PS\Model\Newsletter::max('number') + 1;
	$nl = new PS\Model\Newsletter($params);

	return $app->ifSaved($nl);
});

// Update newsletter
$app->post('/newsletters/{newsletter_id}', function(Request $request, $newsletter_id) use ($app) {
	return $app->update(PS\Model\Newsletter::find($newsletter_id), $request->request->all());
});

// Delete newsletter
$app->post('/newsletters/{newsletter_id}/delete', function(Request $request, $newsletter_id) use ($app) {
	return $app->ifDeleted(PS\Model\Newsletter::find($newsletter_id));
});


// NewsletterLanguages

// Create or Update NewsletterLanguage
$app->post('/newsletters/{newsletter_id}/{language_code}', function(Request $request, $newsletter_id, $language_code) use ($app) {
	$newsletter = PS\Model\Newsletter::find($newsletter_id);
	if ($newsletter)
	{
		$language = PS\Model\Language::where('code', $language_code)->first();
		if ($language)
		{
			$newsletter_language = PS\Model\NewsletterLanguage::where('newsletter_id', $newsletter_id)->where('language_id', $language->id)->first();
			if ($newsletter_language)
			{
				$newsletter_language->fill($request->request->all());
				return $app->ifSaved($newsletter_language);
			}
			else
			{
				$newsletter_language = new PS\Model\NewsletterLanguage($request->request->all());
				$newsletter_language->newsletter()->associate($newsletter);
				$newsletter_language->language()->associate($language);
				return $app->ifSaved($newsletter_language);
			}
		}
		else
		{
			return $app->oops('Language does not exist.');
		}
	}
	else
	{
		return $app->oops('Could not find newsletter.');
	}
});

// Get NewsletterLanguage
$app->get('/newsletters/{newsletter_id}/{language_code}', function($newsletter_id, $language_code) use ($app) {
	return $app->models($app->getNewsletterLanguage($newsletter_id, $language_code));
});

// Delete NewsletterLanguage
$app->post('/newsletters/{newsletter_id}/{language_code}/delete', function($newsletter_id, $language_code) use ($app) {
	return $app->ifDeleted($app->getNewsletterLanguage($newsletter_id, $language_code));
});

// Articles

// Create Article
$app->post('/newsletters/{newsletter_id}/{language_code}/articles', function(Request $request, $newsletter_id, $language_code) use ($app) {
	$newsletterLanguage = $app->getNewsletterLanguage($newsletter_id, $language_code);
	if ($newsletterLanguage)
	{
		$params = $request->request->all();

		$params['type'] = 'default';
		$params['position'] = PS\Model\NewsletterArticle::where('type', $params['type'])->max('position') + 1;

		$article = new PS\Model\NewsletterArticle($params);

		$article->newsletterLanguage()->associate($newsletterLanguage);
		return $app->ifSaved($article);
	}
	else
	{
		return $app->oops('Could not find newsletter language.');
	}
});

//Get Article
$app->get('/articles/{article_id}', function ($article_id) use ($app) {
	return $app->models(PS\Model\NewsletterArticle::find($article_id));
});

// Update Article
$app->post('/articles/{article_id}', function(Request $request, $article_id) use ($app) {
	if ($article = PS\Model\NewsletterArticle::find($article_id))
	{
		$params = $request->request->all();

		// Make sure we set a valid position if changing the article type
		if (array_key_exists('type', $params))
		{
			if ($params['type'] != $article['type'])
			{
				$taken = $article->newsletterLanguage->articles()
				->where('type', $params['type'])
				->where('position', $article->position)->get();

				if (count($taken) > 0)
				{
					$position = $article->newsletterLanguage->articles()
					->where('type', $params['type'])
					->max('position') + 1;

					$params['position'] = $position;
				}
			}
		}
		$article->fill($params);
		return $app->ifSaved($article);
	}
	else
	{
		return $app->oops('Could not find article.');
	}
});

// Move Article
$app->post('/articles/{article_id}/move', function(Request $request, $article_id) use ($app, $capsule) {
	if ($article = PS\Model\NewsletterArticle::find($article_id))
	{
		$delta = $request->request->get('delta', -1);
		$articles = $article->newsletterLanguage->articles()
		->where('type', $article->type)
		->orderBy('position', 'asc')
		->get();

		$move = array();

		foreach ($articles as $n => $toMove)
		{
			if ($toMove->id === $article->id && isset($articles[$n+$delta]))
			{
				$move[0] = array('id' => $articles[$n+$delta]->id, 'position' => $toMove->position);
				$move[1] = array('id' => $article->id, 'position' => $articles[$n+$delta]->position);
				break;
			}			
		}

		if (count($move) === 2)
		{
			$conn = $capsule->getConnection();
			$conn->transaction(function () use ($move, $conn) {
				$safePos = PS\Model\NewsletterArticle::max('position') + 1;
				$conn->update('UPDATE NewsletterArticle SET position=? WHERE id=?', array($safePos, $move[1]['id']));
				$conn->update('UPDATE NewsletterArticle SET position=? WHERE id=?', array($move[0]['position'], $move[0]['id']));
				$conn->update('UPDATE NewsletterArticle SET position=? WHERE id=?', array($move[1]['position'], $move[1]['id']));
			});
		}

		flog(print_r($move, 1));

		return $app->yay($move);
	}
	else
	{
		return $app->oops('Could not find article.');
	}
});

// List Articles
$app->get('/newsletters/{newsletter_id}/{language_code}/articles', function($newsletter_id, $language_code) use ($app) {
	$newsletterLanguage = $app->getNewsletterLanguage($newsletter_id, $language_code);
	if ($newsletterLanguage)
	{
		return $app->models($newsletterLanguage->articles);
	}
	else
	{
		return $app->oops('Could not find newsletter language.');
	}
});

// Delete Article
$app->post('/articles/{article_id}/delete', function(Request $request, $article_id) use ($app) {
	return $app->ifDeleted(PS\Model\NewsletterArticle::find($article_id));
});

// Buttons

// Create Article Button
$app->post('/articles/{article_id}/buttons', function(Request $request, $article_id) use ($app) {
	if ($article = PS\Model\NewsletterArticle::find($article_id))
	{
		$params = $request->request->all();
		$params['position'] = PS\Model\ArticleButton::where('newsletter_article_id', $article_id)->max('position') + 1;
		$button = new PS\Model\ArticleButton($params);
		$button->article()->associate($article);
		return $app->ifSaved($button);
	}
	else
	{
		return $app->oops('Could not find article.');
	}
});

// List Article Buttons
$app->get('/articles/{article_id}/buttons', function(Request $request, $article_id) use ($app) {
	if ($article = PS\Model\NewsletterArticle::find($article_id))
	{
		return $app->models($article->buttons);
	}
	else
	{
		return $app->oops('Could not find article.');
	}
});

// Update Article Button
$app->post('/buttons/{button_id}', function(Request $request, $button_id) use ($app) {
	if ($button = PS\Model\ArticleButton::find($button_id))
	{
		$button->fill($request->request->all());
		return $app->ifSaved($button);
	}
	else
	{
		return $app->oops('Could not find button.');
	}
});

// Delete Article Button
$app->post('/buttons/{button_id}/delete', function(Request $request, $button_id) use ($app) {
	return $app->ifDeleted(PS\Model\ArticleButton::find($button_id));
});

// Move Button
$app->post('/buttons/{button_id}/move', function(Request $request, $button_id) use ($app, $capsule) {
	if ($button = PS\Model\ArticleButton::find($button_id))
	{
		$delta = $request->request->get('delta', -1);
		$buttons = $button->article->buttons()
		->orderBy('position', 'asc')
		->get();

		$move = array();

		foreach ($buttons as $n => $toMove)
		{
			if ($toMove->id === $button->id && isset($buttons[$n+$delta]))
			{
				$move[0] = array('id' => $buttons[$n+$delta]->id, 'position' => $toMove->position);
				$move[1] = array('id' => $button->id, 'position' => $buttons[$n+$delta]->position);
				break;
			}			
		}

		if (count($move) === 2)
		{
			$conn = $capsule->getConnection();
			$conn->transaction(function () use ($move, $conn) {
				$safePos = PS\Model\ArticleButton::max('position') + 1;
				$conn->update('UPDATE ArticleButton SET position=? WHERE id=?', array($safePos, $move[1]['id']));
				$conn->update('UPDATE ArticleButton SET position=? WHERE id=?', array($move[0]['position'], $move[0]['id']));
				$conn->update('UPDATE ArticleButton SET position=? WHERE id=?', array($move[1]['position'], $move[1]['id']));
			});
		}

		flog(print_r($move, 1));

		return $app->yay($move);
	}
	else
	{
		return $app->oops('Could not find button.');
	}
});

/* Rock On! */
$app->run();