<?php

@ini_set('display_errors', 'on');

require_once __DIR__.'/../vendor/autoload.php';

$dbOptions = require_once __DIR__.'/../config/db.php';

use Symfony\Component\HttpFoundation\Request;

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
	return $app->models(PS\Model\Newsletter::with('languages')->find($newsletter_id));
});

// Create newsletter
$app->post('/newsletters', function(Request $request) use ($app) {
	return $app->ifSaved(new PS\Model\Newsletter($request->request->all()));
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
		$article = new PS\Model\NewsletterArticle($request->request->all());
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
		$article->fill($request->request->all());
		return $app->ifSaved($article);
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

/* Rock On! */
$app->run();