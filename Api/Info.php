<?php

namespace LimeSoda\Cashpresso\Api;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Webapi\Exception;

class Info extends Base
{
    const METHOD_PARTNER_INFO = 'partnerInfo';

    /**
     * @return bool|null
     * @throws Exception
     */
    public function getPartnerInfo()
    {
        $request = $this->getRequest(Info::METHOD_PARTNER_INFO);

        $response = $request->send();

        if ($response->isSuccess()) {
            $respond = $this->json->deserialize($response->getBody());

            if (is_array($respond)) {
                return $this->handleRespond($respond);
            }
        }

        $this->logger->error('cashpresso getPartnerInfo error: ' . $response->getReasonPhrase());

        return null;
    }

    /**
     * @return array
     * @throws LocalizedException
     */
    public function getContent(): array
    {
        return ['partnerApiKey' => $this->getPartnerApiKey()];
    }
}
