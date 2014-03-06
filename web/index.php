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

$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/views',
));

$app['twig'] = $app->share($app->extend('twig', function($twig, $app) {
    $twig->addFilter('localDate', new \Twig_Filter_Function(function($date, $locale, $format='long'){
    	return CLDR\I18n\DateFormatter::formatDate($date, $format, $locale);
    }));

    return $twig;
}));

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
	return $app->models(PS\Model\Language::orderBy('position')->get());
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
	return $app->models($app->getNewsletterLanguage($newsletter_id, $language_code, true));
});

// Render NewsletterLanguage
$app->get('/newsletters/{newsletter_id}/{language_code}/render', function(Request $request, $newsletter_id, $language_code) use ($app) {
	$nl = $app->getNewsletterLanguage($newsletter_id, $language_code, true)->toArray();

	$nl['default_articles'] = array();
	$nl['footer_articles'] = array();

	foreach ($nl['articles'] as $article)
	{
		if ($article['type'] === 'footer')
		{
			$article['default_color'] = $article['buttons'][0]['style'];
			$nl['footer_articles'][] = $article;
		}
		else
			$nl['default_articles'][] = $article;
	}

	foreach (array('default_articles', 'footer_articles') as $key)
		usort($nl[$key], function($a, $b){
			return (int)$a['position'] - (int)$b['position'];
		});


	$template = $request->query->get('template', 'newsletter');

	$raw_messages = PS\Model\MessageTranslation::with('message')
	->whereHas('language', function($q) use ($language_code){
		$q->where('code', $language_code);
	})->get()->toArray();

	$messages = array();


	foreach ($raw_messages as $message)
	{
		$messages[$message['message']['mkey']] = $message['translation'];
	}

	$raw_languages = PS\Model\NewsletterLanguage::with('language')
	->where('newsletter_id', $newsletter_id)
	->get()
	->toArray();

	$languages = array();

	foreach ($raw_languages as $language)
	{
		$l = $language['language'];
		$l['img_code'] = $l['code'] === 'br' ? 'pt' : $l['code'];
		$languages[] = $l;
	}

	usort($languages, function($a, $b){
		return $a['position'] - $b['position'];
	});

	$html = $app['twig']->render(basename($template).'.html.twig', array('nl' => $nl, 'messages' => $messages, 'languages' => $languages));

	/*
	if ($request->query->get('inline') != '0')
	{
		$cssin = new FM\CSSIN();
		$html = $cssin->inlineCSS(null, $html);
	}*/

	// Tricky thingy to htmlencode everything... except the HTML :)
	$list = get_html_translation_table(HTML_ENTITIES);
	unset($list['"']);
	unset($list['<']);
	unset($list['>']);
	unset($list['&']);

	$search = array_keys($list);
	$values = array_values($list);
	//$search = array_map('utf8_encode', $search);

	$html = str_replace($search, $values, $html);

	return $html;
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

		if (!isset($params['type']) || !$params['type'])
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

		return $app->yay($move);
	}
	else
	{
		return $app->oops('Could not find button.');
	}
});

// Messages
$app->post('/messages', function(Request $request) use ($app){
	$message = new PS\Model\Message($request->request->all());
	return $app->ifSaved($message);
});

$app->get('/messages', function() use ($app){
	return $app->models(PS\Model\Message::all());
});

$app->get('/messages/{message_id}/translations', function($message_id) use ($app){
	return $app->models(PS\Model\MessageTranslation::with('language')->where('message_id', $message_id)->get());
});

$app->post('/messages/{message_id}/delete', function(Request $request, $message_id) use ($app){
	return $app->ifDeleted(PS\Model\Message::find($message_id));
});

$app->post('/messages/{message_id}/{language_code}', function(Request $request, $message_id, $language_code) use ($app){
	if ($language = PS\Model\Language::where('code', $language_code)->first())
	{
		$messageTranslation = PS\Model\MessageTranslation::where('message_id', $message_id)
		->where('language_id', $language->id)->first();

		if (!$messageTranslation)
		{
			$messageTranslation = new PS\Model\MessageTranslation();
			$messageTranslation->language()->associate($language);

			$message = PS\Model\Message::find($message_id);
			$messageTranslation->message()->associate($message);
		}

		$messageTranslation->fill($request->request->all());
		return $app->ifSaved($messageTranslation);
	}
	else
	{
		return $app->oops('Could not find language.');
	}
});

/* Rock On! */
$app->run();