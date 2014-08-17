<?php
return array(
	'charge' => function() use ($request, $globals) {
		$amount = $request->request->get('amount');
		$description = $request->request->get('description');
		$token = $request->request->get('token');
		$emailAddress = $request->request->get('emailAddress');

		try {
			Stripe::setApiKey($globals['stripe']['secretKey']);
			$charge = Stripe_Charge::create(array(
				'amount' => intval($amount * 100),
				'currency' => $globals['currency'],
				'card' => $token,
				'description' => $description,
				'capture' => false
			));
		} catch (Stripe_CardError $e) {
			$body = $e->getJsonBody();
			$err  = $body['error'];
			return array('success' => false, 'error' => $err['message']);
		}

		return array(
			'success' => true,
			'email' => $emailAddress,
			'description' => $description,
			'amount' => $charge->amount,
			'currency' => $charge->currency,
			'card' => array(
				'last4' => $charge->card->last4,
				'type' => $charge->card->brand,
			)
		);
	}
);