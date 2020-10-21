<?php
/**
 * This is the controller for the Prepayment example.
 * It is called when the pay button on the index page is clicked.
 *
 * Copyright (C) 2020 - today Unzer E-Com GmbH
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * @link  https://docs.heidelpay.com/
 *
 * @author  Simon Gabriel <development@heidelpay.com>
 *
 * @package  UnzerSDK\examples
 */

/** Require the constants of this example */
require_once __DIR__ . '/Constants.php';

/** @noinspection PhpIncludeInspection */
/** Require the composer autoloader file */
require_once __DIR__ . '/../../../../autoload.php';

use UnzerSDK\examples\ExampleDebugHandler;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Unzer;
use UnzerSDK\Resources\CustomerFactory;
use UnzerSDK\Resources\PaymentTypes\Prepayment;

session_start();
session_unset();

$clientMessage = 'Something went wrong. Please try again later.';
$merchantMessage = 'Something went wrong. Please try again later.';

function redirect($url, $merchantMessage = '', $clientMessage = '')
{
    $_SESSION['merchantMessage'] = $merchantMessage;
    $_SESSION['clientMessage']   = $clientMessage;
    header('Location: ' . $url);
    die();
}

// Catch API errors, write the message to your log and show the ClientMessage to the client.
try {
    // Create a heidelpay object using your private key and register a debug handler if you want to.
    $heidelpay = new Unzer(HEIDELPAY_PHP_PAYMENT_API_PRIVATE_KEY);
    $heidelpay->setDebugMode(true)->setDebugHandler(new ExampleDebugHandler());

    /** @var Prepayment $prepayment */
    $prepayment = $heidelpay->createPaymentType(new Prepayment());

    $customer = CustomerFactory::createCustomer('Max', 'Mustermann');
    $orderId = 'o' . str_replace(['0.', ' '], '', microtime(false));

    $transaction = $prepayment->charge(12.99, 'EUR', CONTROLLER_URL, $customer, $orderId);

    // You'll need to remember the shortId to show it on the success or failure page
    $_SESSION['ShortId'] = $transaction->getShortId();
    $_SESSION['PaymentId'] = $transaction->getPaymentId();
    $_SESSION['additionalPaymentInformation'] =
        sprintf(
            "Please transfer the amount of %f %s to the following account:<br /><br />"
            . "Holder: %s<br/>"
            . "IBAN: %s<br/>"
            . "BIC: %s<br/><br/>"
            . "<i>Please use only this identification number as the descriptor: </i><br/>"
            . "%s",
            $transaction->getAmount(),
            $transaction->getCurrency(),
            $transaction->getHolder(),
            $transaction->getIban(),
            $transaction->getBic(),
            $transaction->getDescriptor()
        );

    // To avoid redundant code this example redirects to the general ReturnController which contains the code example to handle payment results.
    redirect(RETURN_CONTROLLER_URL);

} catch (UnzerApiException $e) {
    $merchantMessage = $e->getMerchantMessage();
    $clientMessage = $e->getClientMessage();
} catch (RuntimeException $e) {
    $merchantMessage = $e->getMessage();
}
redirect(FAILURE_URL, $merchantMessage, $clientMessage);
