<?php

require(__DIR__ . '/vendor/autoload.php');

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

$app = new Silex\Application();

$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/templates',
));

$app['globals'] = require('globals.php');

$app->get('/', function (Request $request) use ($app) {
    return $app['twig']->render('index.html.twig', array());
});

$app->post('/charge', function (Request $request) use ($app) {
	$amount = $request->request->get('amount');
	$description = $request->request->get('description');
	$token = $request->request->get('token');
	$emailAddress = $request->request->get('emailAddress');

	try {
		Stripe::setApiKey($app['globals']['stripe']['secretKey']);
		$charge = Stripe_Charge::create(array(
			'amount' => intval($amount * 100),
			'currency' => $app['globals']['currency'],
			'card' => $token,
			'description' => $description,
			'capture' => false
		));
		$return = array(
			'success' => true,
			'email' => $emailAddress,
			'description' => $description,
			'amount' => $charge->amount,
			'currency' => $charge->currency,
			'card' => array(
				'last4' => $charge->card->last4,
				'type' => $charge->card->brand,
			),
		);
	} catch (Stripe_CardError $e) {
		$body = $e->getJsonBody();
		$err  = $body['error'];
		$return = array('success' => false, 'error' => $err['message']);
	}

    return $app['twig']->render('charge.html.twig', $return);
});

$app->run();