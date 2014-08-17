<?php

require(__DIR__ . '/vendor/autoload.php');

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

$request = Request::createFromGlobals();

$controller = function() use ($request) {
	$pathInfo = ltrim($request->getPathInfo(), '/');
	if (empty($pathInfo)) {
		$pathInfo = 'index';
	}

	return $pathInfo;
};

$loader = new Twig_Loader_Filesystem(realpath(__DIR__ . '/templates'));
$twig = new Twig_Environment($loader, array(
	'cache' => realpath(__DIR__ . '/cache'),
	'debug' => true,
));

if ($twig->isDebug()) {
	$twig->addExtension(new Twig_Extension_Debug());
}

$response = new Response;
try {
	$controller = call_user_func($controller);
	$globals = require('globals.php');

	$controllers = require('controllers.php');
	$templateData = array('request' => $request, 'globals' => $globals);
	if (array_key_exists($controller, $controllers) && is_callable($controllers[$controller])) {
		$templateData = array_merge($templateData, call_user_func($controllers[$controller]));
	}
	$template = $twig->loadTemplate($controller . '.html.twig');
	$response->setContent($template->render($templateData));
} catch (\Twig_Error_Loader $e) {
	$response->setStatusCode(404);
	$template = $twig->loadTemplate('error.html.twig');
	$response->setContent($template->render(array('title' => $e->getMessage())));
} catch (\Exception $e) {
	$response->setStatusCode(500);
	$template = $twig->loadTemplate('error.html.twig');
	$response->setContent($template->render(array('title' => $e->getMessage())));
}

$response->send();