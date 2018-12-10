<?php
/**
 * This class defines integration tests to verify cancellation of authorizations.
 *
 * Copyright (C) 2018 heidelpay GmbH
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
 * @link  http://dev.heidelpay.com/
 *
 * @author  Simon Gabriel <development@heidelpay.com>
 *
 * @package  heidelpay/mgw_sdk/test/integration
 */
namespace heidelpay\MgwPhpSdk\test\integration\PaymentTypes;

use heidelpay\MgwPhpSdk\Exceptions\HeidelpayApiException;
use heidelpay\MgwPhpSdk\Resources\TransactionTypes\Authorization;
use heidelpay\MgwPhpSdk\Resources\TransactionTypes\Cancellation;
use heidelpay\MgwPhpSdk\test\BasePaymentTest;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\ExpectationFailedException;

class CancelAfterAuthorizationTest extends BasePaymentTest
{
    /**
     * Verify that a full cancel on an authorization results in a cancelled payment.
     *
     * @test
     *
     * @throws AssertionFailedError
     * @throws ExpectationFailedException
     * @throws \RuntimeException
     * @throws HeidelpayApiException
     */
    public function fullCancelOnAuthorization()
    {
        $card = $this->heidelpay->createPaymentType($this->createCardObject());
        $authorization = $this->heidelpay->authorize(100.0000, 'EUR', $card, self::RETURN_URL);

        /** @var Authorization $fetchedAuthorization */
        $fetchedAuthorization = $this->heidelpay->fetchAuthorization($authorization->getPayment()->getId());
        $payment = $fetchedAuthorization->getPayment();
        $this->assertAmounts($payment, 100.0, 0.0, 100.0, 0.0);
        $this->assertEquals('EUR', $payment->getCurrency());
        $this->assertTrue($payment->isPending());

        $cancellation = $fetchedAuthorization->cancel();
        $secPayment = $this->heidelpay->fetchPayment($payment->getId());
        $this->assertNotEmpty($cancellation);
        $this->assertAmounts($secPayment, 0.0, 0.0, 0.0, 0.0);
        $this->assertTrue($secPayment->isCanceled());
    }

    /**
     * Verify part cancel on an authorization.
     *
     * @test
     *
     * @throws AssertionFailedError
     * @throws ExpectationFailedException
     * @throws HeidelpayApiException
     * @throws \RuntimeException
     */
    public function partCancelOnPayment()
    {
        $card = $this->heidelpay->createPaymentType($this->createCardObject());
        $authorization = $this->heidelpay->authorize(100.0000, 'EUR', $card, self::RETURN_URL);
        $payment = $this->heidelpay->fetchPayment($authorization->getPayment()->getId());

        /** @var Cancellation $cancel */
        $cancel = $payment->cancel(10.0);
        $this->assertNotNull($cancel);
        $this->assertNotEmpty($cancel->getId());
        $this->assertNotEmpty($cancel->getUniqueId());
        $this->assertNotEmpty($cancel->getShortId());
        $this->assertEquals(10.0, $cancel->getAmount());
    }

    /**
     * Verify part cancel after authorization.
     *
     * @test
     *
     * @throws AssertionFailedError
     * @throws ExpectationFailedException
     * @throws HeidelpayApiException
     * @throws \RuntimeException
     */
    public function partCancelOnAuthorize()
    {
        $card = $this->heidelpay->createPaymentType($this->createCardObject());
        $authorization = $this->heidelpay->authorize(100.0000, 'EUR', $card, self::RETURN_URL);

        /** @var Authorization $fetchedAuthorization */
        $fetchedAuthorization = $this->heidelpay->fetchAuthorization($authorization->getPayment()->getId());

        $cancel = $fetchedAuthorization->cancel(10.0);
        $this->assertNotNull($cancel);
        $this->assertNotEmpty($cancel->getId());
        $this->assertEquals(10.0, $cancel->getAmount());

        $payment = $this->heidelpay->fetchPayment($fetchedAuthorization->getPayment()->getId());
        $this->assertAmounts($payment, 90.0, 0.0, 90.0, 0.0);
        $this->assertTrue($payment->isPending());
    }

    /**
     * Verify a cancel can be fetched.
     *
     * @test
     *
     * @throws AssertionFailedError
     * @throws ExpectationFailedException
     * @throws HeidelpayApiException
     * @throws \RuntimeException
     */
    public function anAuthorizationsFullReversalShallBeFetchable()
    {
        $card = $this->heidelpay->createPaymentType($this->createCardObject());
        $authorization = $this->heidelpay->authorize(100.0000, 'EUR', $card, self::RETURN_URL);
        $payment = $authorization->getPayment();
        $this->assertAmounts($payment, 100.0, 0, 100.0, 0);
        $this->assertTrue($payment->isPending());

        $cancel = $this->heidelpay->cancelAuthorization($authorization);
        $this->assertNotNull($cancel);
        $this->assertNotNull($cancel->getId());
        $this->assertEquals(100.0, $cancel->getAmount());
        $secondPayment = $cancel->getPayment();
        $this->assertAmounts($secondPayment, 0, 0, 0, 0);
        $this->assertTrue($secondPayment->isCanceled());


        $fetchedCancel = $this->heidelpay->fetchReversalByAuthorization($authorization, $cancel->getId());
        $this->assertNotNull($fetchedCancel);
        $this->assertNotEmpty($fetchedCancel->getId());
        $thirdPayment = $authorization->getPayment();
        $this->assertAmounts($thirdPayment, 0, 0, 0, 0);
        $this->assertTrue($thirdPayment->isCanceled());

        $fetchedCancelSecond = $this->heidelpay->fetchReversal($authorization->getPayment()->getId(), $cancel->getId());
        $this->assertNotNull($fetchedCancelSecond);
        $this->assertNotEmpty($fetchedCancelSecond->getId());
        $this->assertEquals($fetchedCancel->expose(), $fetchedCancelSecond->expose());
        $fourthPayment = $fetchedCancelSecond->getPayment();
        $this->assertAmounts($fourthPayment, 0, 0, 0, 0);
        $this->assertTrue($fourthPayment->isCanceled());
    }

    /**
     * Verify cancels can be fetched.
     *
     * @test
     *
     * @throws AssertionFailedError
     * @throws ExpectationFailedException
     * @throws HeidelpayApiException
     * @throws \RuntimeException
     */
    public function anAuthorizationsReversalsShouldBeFetchable()
    {
        $card = $this->heidelpay->createPaymentType($this->createCardObject());
        $authorization = $this->heidelpay->authorize(100.0000, 'EUR', $card, self::RETURN_URL);
        $payment = $authorization->getPayment();
        $this->assertAmounts($payment, 100.0, 0, 100.0, 0);
        $this->assertTrue($payment->isPending());

        $firstCancel = $this->heidelpay->cancelAuthorization($authorization, 50.0);
        $this->assertNotNull($firstCancel);
        $this->assertNotNull($firstCancel->getId());
        $this->assertEquals(50.0, $firstCancel->getAmount());
        $secondPayment = $firstCancel->getPayment();
        $this->assertAmounts($secondPayment, 50.0, 0, 50.0, 0);
        $this->assertTrue($secondPayment->isPending());
        $this->assertCount(1, $authorization->getCancellations());

        $secondCancel = $this->heidelpay->cancelAuthorization($authorization, 20.0);
        $this->assertNotNull($secondCancel);
        $this->assertNotNull($secondCancel->getId());
        $this->assertEquals(20.0, $secondCancel->getAmount());
        $thirdPayment = $secondCancel->getPayment();
        $this->assertAmounts($thirdPayment, 30.0, 0, 30.0, 0);
        $this->assertTrue($thirdPayment->isPending());
        $this->assertCount(2, $authorization->getCancellations());

        $firstCancelFetched = $this->heidelpay->fetchReversalByAuthorization($authorization, $firstCancel->getId());
        $this->assertNotNull($firstCancelFetched);
        $this->assertEquals($firstCancel->expose(), $firstCancelFetched->expose());

        $secondCancelFetched = $this->heidelpay->fetchReversalByAuthorization($authorization, $secondCancel->getId());
        $this->assertNotNull($secondCancelFetched);
        $this->assertEquals($secondCancel->expose(), $secondCancelFetched->expose());
    }

    // todo:
//    /**
//     * Verify a full cancel can be performed on a partly charged card authorization.
//     *
//     * @test
//     */
//    public function fullCancelOnPartlyChargedAuthorization()
//    {
//        $card = $this->heidelpay->createPaymentType($this->createCard());
//        $authorization = $this->heidelpay->authorize(100.0000, Currency::EURO, $card, self::RETURN_URL);
//        $payment = $this->heidelpay->fetchPaymentById($authorization->getPayment()->getId());
//
//        $this->assertAmounts($payment, 100.0, 0.0, 100.0, 0.0);
//        $this->assertTrue($payment->isPending());
//
//        $payment->charge(10.0);
//        $this->assertAmounts($payment, 90.0, 10.0, 100.0, 0.0);
//        $this->assertTrue($payment->isPartlyPaid());
//
//        $cancellation = $authorization->cancel();
//        $this->assertNotEmpty($cancellation);
//        $this->assertAmounts($payment, 0.0, 10.0, 10.0, 0.0);
//        $this->assertTrue($payment->isCompleted());
//    }

//    /**
//     * Verify an exception is thrown when trying to charge an already fully charged authorization.
//     *
//     * @test
//     */
//    public function fullCancelOnFullyChargedAuthorizationThrowsException()
//    {
//        /** @var Card $card */
//        $card = $this->createCard();
//        $card = $this->heidelpay->createPaymentType($card);
//        $authorization = $card->authorize(100.0, Currency::EURO, self::RETURN_URL);
//        $payment = $authorization->getPayment();
//        $this->assertAmounts($payment, 100.0, 0.0, 100.0, 0.0);
//        $this->assertTrue($payment->isPending());
//
//        $payment->charge(100.0);
//        $this->assertAmounts($payment, 0.0, 100.0, 100.0, 0.0);
//        $this->assertTrue($payment->isCompleted());
//
//        $this->expectException(HeidelpayApiException::class);
//        $this->expectExceptionCode(ApiResponseCodes::API_ERROR_ALREADY_CHARGED);
//        $authorization->cancel();
//    }
}
