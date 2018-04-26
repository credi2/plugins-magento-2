<?php
/**
 * 26.04.18
 * LimeSoda - Limesoda M2Demo
 *
 * Created by Anton Sannikov.
 *
 * @category    Lime_Soda
 * @package     Limesoda M2Demo
 * @copyright   Copyright (c) 2018 LimeSoda. (http://www.limesoda.com)
 *
 * @file Account.php
 */

namespace LimeSoda\Cashpresso\Api;

class Account extends Base
{
    const METHOD_TARGET_ACCOUNTS = 'partner/targetAccounts';

    private $postData;

    public function getContent()
    {
        $data = [
            'partnerApiKey' => $this->getPartnerApiKey(),
            'verificationHash' => hash('sha512', $this->getSecretKey() . ';' . $this->getPartnerApiKey())
        ];

        $this->postData = $data;

        return $data;
    }

    public function getTargetAccounts()
    {
        if ($this->getConfig()->isDebugEnabled()) {
            $this->logger->debug(print_r($this->postData, true));
        }

        /** @var \Magento\Framework\HTTP\ZendClient $request */
        $request = $this->getRequest(Account::METHOD_TARGET_ACCOUNTS);

        $response = $request->request();

        if ($response->isSuccessful()) {
            $respond = $this->json->deserialize($response->getBody());

            if (is_array($respond)) {
                $respond = $this->handleRespond($respond);

                if (!empty($respond['targetAccounts'])) {
                    return $respond['targetAccounts'];
                }
            }

            return [];
        }

        throw new \DomainException(__("cashpresso target account request error: %s", $response->getMessage()));
    }
}