<?php

namespace LimeSoda\Cashpresso\Api;

class Info extends Base
{
    const METHOD_PARTNER_INFO = 'partnerInfo';

    /**
     * @return bool|null
     * @throws \Magento\Framework\Webapi\Exception
     * @throws \Zend_Http_Client_Exception
     */
    public function getPartnerInfo()
    {
        /** @var \Magento\Framework\HTTP\ZendClient $request */
        $request = $this->getRequest(Info::METHOD_PARTNER_INFO);

        $response = $request->request();

        if ($response->isSuccessful()) {
            $respond = $this->json->deserialize($response->getBody());

            if (is_array($respond)) {
                return $this->handleRespond($respond);
            }
        }

        $this->logger->error('cashpresso getPartnerInfo error: ' . $response->getMessage());

        return null;
    }

    /**
     * @return array
     */
    public function getContent()
    {
        return ['partnerApiKey' => $this->getPartnerApiKey()];
    }
}