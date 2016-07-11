<?php
/**
 * Copyright (c) 2016. Francois Raubenheimer
 */

namespace Peach\Oppwa\Payments;

use GuzzleHttp\Exception\RequestException;
use Peach\Oppwa\Client;
use Peach\Oppwa\ClientInterface;
use Peach\Oppwa\ResponseJson;

/**
 * Class Reverse
 * @package Peach\Oppwa\Payments
 */
class Reverse implements ClientInterface
{
    /**
     * @var Client
     */
    private $reverseClient;
    /**
     * @var float|null
     */
    private $refundAmount;
    /**
     * @var null|string
     */
    private $refundCurrency = 'ZAR';
    /**
     * @var string
     */
    private $transactionId;

    /**
     * Reverse constructor.
     * @param Client $reverseClient
     * @param string $transactionId
     * @param float $refundAmount
     * @param string $refundCurrency
     */
    public function __construct(
        Client $reverseClient,
        $transactionId = null,
        $refundAmount = null,
        $refundCurrency = null
    ) {
        $this->reverseClient = $reverseClient;

        if (!empty($transactionId)) {
            $this->setTransactionId($transactionId);
        }

        if (!empty($refundAmount)) {
            $this->refundAmount = $refundAmount;
        }

        if (!empty($refundCurrency)) {
            $this->refundCurrency = $refundCurrency;
        }
    }

    /**
     * @return ResponseJson
     */
    public function process()
    {
        $reverseClient = $this->reverseClient->getClient();
        try {
            $response = $reverseClient->post($this->buildUrl(), [
                'form_params' => $this->getReverseParams()
            ]);
            return new ResponseJson((string)$response->getBody(), true);
        } catch (RequestException $e) {
            return new ResponseJson((string)$e->getResponse()->getBody(), false);
        }
    }

    /**
     * @return string
     */
    public function buildUrl()
    {
        return $this->reverseClient->getApiUri() . '/payments/' . $this->getTransactionId();
    }

    /**
     * @return array
     */
    public function getReverseParams()
    {
        $params = [
            'authentication.userId' => $this->reverseClient->getConfig()->getUserId(),
            'authentication.password' => $this->reverseClient->getConfig()->getPassword(),
            'authentication.entityId' => $this->reverseClient->getConfig()->getEntityId(),
        ];

        if (!empty($this->getRefundAmount())) {
            $params['paymentType'] = 'RF';
            $params['amount'] = $this->getRefundAmount();
            $params['currency'] = $this->getRefundCurrency();
        }

        if (empty($this->getRefundAmount())) {
            $params['paymentType'] = 'RV';
        }

        return $params;
    }

    /**
     * @return float|null
     */
    public function getRefundAmount()
    {
        return $this->refundAmount;
    }

    /**
     * @param float|null $refundAmount
     * @return $this
     */
    public function setRefundAmount($refundAmount)
    {
        $this->refundAmount = $refundAmount;
        return $this;
    }

    /**
     * @return null|string
     */
    public function getRefundCurrency()
    {
        return $this->refundCurrency;
    }

    /**
     * @param null|string $refundCurrency
     * @return $this
     */
    public function setRefundCurrency($refundCurrency)
    {
        $this->refundCurrency = $refundCurrency;
        return $this;
    }

    /**
     * @return string
     */
    public function getTransactionId()
    {
        return $this->transactionId;
    }

    /**
     * @param string $transactionId
     * @return $this
     */
    public function setTransactionId($transactionId)
    {
        $this->transactionId = $transactionId;
        return $this;
    }
}
