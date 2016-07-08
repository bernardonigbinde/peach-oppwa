<?php
/**
 * Copyright (c) 2016. Francois Raubenheimer
 */

namespace Peach\Oppwa\Payments;

use GuzzleHttp\Exception\RequestException;
use Peach\Oppwa\Cards\AbstractCard;
use Peach\Oppwa\Client;
use Peach\Oppwa\ClientInterface;
use Peach\Oppwa\ResponseJson;

/**
 * Class Debit
 * @package Peach\Oppwa\Payments
 */
class Debit extends AbstractCard implements ClientInterface
{
    /**
     * @var Client
     */
    private $client;
    private $amount;
    private $currency = 'ZAR';
    private $paymentType = 'DB';
    private $createRegistration = false;

    /**
     * PreAuthorization constructor.
     * @param Client $appClient
     */
    public function __construct(Client $appClient)
    {
        $this->client = $appClient;
    }

    /**
     * @return object
     */
    public function process()
    {
        try {
            $this->isCardDetailsValid();
        } catch (\Exception $e) {
            return (object)['result' => ['code' => $e->getCode(), 'message' => $e->getMessage()]];
        }
        
        $appClient = $this->client->getClient();

        try {
            $response = $appClient->post($this->buildUrl(), [
                'form_params' => $this->getParams()
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
        return $this->client->getApiUri() . '/payments';
    }

    /**
     * @return array
     */
    public function getParams()
    {
        $params = [
            'authentication.userId' => $this->client->getConfig()->getUserId(),
            'authentication.password' => $this->client->getConfig()->getPassword(),
            'authentication.entityId' => $this->client->getConfig()->getEntityId(),
            'paymentBrand' => $this->getCardBrand(),
            'card.number' => $this->getCardNumber(),
            'card.holder' => $this->getCardHolder(),
            'card.expiryMonth' => $this->getCardExpiryMonth(),
            'card.expiryYear' => $this->getCardExpiryYear(),
            'card.cvv' => $this->getCardCvv(),
            'paymentType' => $this->getPaymentType(),
            'amount' => $this->getAmount(),
            'currency' => $this->getCurrency(),
        ];

        // save card and make sure the transaction is done correctly via the INITIAL trigger
        if ($this->isCreateRegistration()) {
            $params['createRegistration'] = true;
            $params['recurringType'] = 'INITIAL';
        }

        return $params;
    }

    /**
     * @return string
     */
    public function getPaymentType()
    {
        return $this->paymentType;
    }

    /**
     * @param string $paymentType
     * @return $this
     */
    public function setPaymentType($paymentType)
    {
        $this->paymentType = $paymentType;
        return $this;
    }

    /**
     * @param $authOnly
     * @return $this
     */
    public function setAuthOnly($authOnly)
    {
        if ($authOnly === true) {
            $this->setPaymentType('PA');
        }
        return $this;
    }

    /**
     * @return string
     */
    public function getCurrency()
    {
        return strtoupper($this->currency);
    }

    /**
     * @param string $currency
     * @return $this
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;
        return $this;
    }

    /**
     * @return float
     */
    public function getAmount()
    {
        // oppwa format
        return number_format($this->amount, 2, '.', '');
    }

    /**
     * @param float $amount
     * @return $this
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;
        return $this;
    }

    /**
     * @return boolean
     */
    public function isCreateRegistration()
    {
        return $this->createRegistration;
    }

    /**
     * @param boolean $createRegistration
     * @return $this
     */
    public function setCreateRegistration($createRegistration)
    {
        $this->createRegistration = $createRegistration;
        return $this;
    }
}
