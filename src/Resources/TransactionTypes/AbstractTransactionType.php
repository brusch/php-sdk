<?php
/**
 * This is the base class for all transaction types.
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
 * @package  heidelpay/mgw_sdk/transaction_types
 */
namespace heidelpay\MgwPhpSdk\Resources\TransactionTypes;

use heidelpay\MgwPhpSdk\Adapter\HttpAdapterInterface;
use heidelpay\MgwPhpSdk\Exceptions\HeidelpayApiException;
use heidelpay\MgwPhpSdk\Resources\AbstractHeidelpayResource;
use heidelpay\MgwPhpSdk\Resources\Payment;
use heidelpay\MgwPhpSdk\Traits\HasOrderId;

abstract class AbstractTransactionType extends AbstractHeidelpayResource
{
    use HasOrderId;

    //<editor-fold desc="Properties">
    /** @var Payment $payment */
    private $payment;

    /** @var \DateTime $date */
    private $date;

    /** @var string $uniqueId */
    private $uniqueId;

    /** @var string $shortId */
    private $shortId;

    /** @var string $url */
    private $url;

    //</editor-fold>

    //<editor-fold desc="Getters/Setters">

    /**
     * Return the payment property.
     *
     * @return Payment|null
     */
    public function getPayment()
    {
        return $this->payment;
    }

    /**
     * Set the payment object property.
     *
     * @param Payment $payment
     *
     * @return $this
     */
    public function setPayment($payment): self
    {
        $this->payment = $payment;
        $this->setParentResource($payment);
        return $this;
    }

    /**
     * Return the Id of the referenced payment object.
     *
     * @return null|string The Id of the payment object or null if nothing is found.
     */
    public function getPaymentId()
    {
        if ($this->payment instanceof Payment) {
            return $this->payment->getId();
        }

        return null;
    }

    /**
     * Return the redirect url stored in the payment object.
     *
     * @return string|null
     */
    public function getRedirectUrl()
    {
        return $this->payment->getRedirectUrl();
    }

    /**
     * This returns the date of the Transaction as string.
     *
     * @return string|null
     */
    public function getDate()
    {
        $date = $this->date;
        return $date ? $date->format('Y-m-d h:i:s') : null;
    }

    /**
     * @param string $date
     *
     * @return $this
     */
    public function setDate(string $date): self
    {
        $this->date = new \DateTime($date);
        return $this;
    }

    /**
     * @return string|null
     */
    public function getUniqueId()
    {
        return $this->uniqueId;
    }

    /**
     * @param string $uniqueId
     *
     * @return $this
     */
    protected function setUniqueId(string $uniqueId): self
    {
        $this->uniqueId = $uniqueId;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getShortId()
    {
        return $this->shortId;
    }

    /**
     * @param string $shortId
     *
     * @return AbstractTransactionType
     */
    protected function setShortId(string $shortId): AbstractTransactionType
    {
        $this->shortId = $shortId;
        return $this;
    }

    //</editor-fold>

    //<editor-fold desc="Overridable methods">

    /**
     * {@inheritDoc}
     *
     * @throws HeidelpayApiException
     * @throws \RuntimeException
     */
    public function handleResponse(\stdClass $response, $method = HttpAdapterInterface::REQUEST_GET)
    {
        parent::handleResponse($response, $method);

        /** @var Payment $payment */
        $payment = $this->getPayment();
        if (isset($response->resources->paymentId)) {
            $payment->setId($response->resources->paymentId);
        }

        if (isset($response->redirectUrl)) {
            $payment->setRedirectUrl($response->redirectUrl);
        }

        if ($method !== HttpAdapterInterface::REQUEST_GET) {
            $this->fetchPayment();
        }
    }

    //</editor-fold>

    /**
     * Updates the referenced payment object if it exists and if this is not the payment object itself.
     * This is called from the crud methods to update the payments state whenever anything happens.
     *
     * @throws \RuntimeException
     * @throws HeidelpayApiException
     */
    public function fetchPayment()
    {
        $payment = $this->getPayment();
        if ($payment instanceof AbstractHeidelpayResource) {
            $this->fetchResource($payment);
        }
    }
}
