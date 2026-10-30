<?php
declare(strict_types=1);

namespace Credi2\CashpressoPayments\Service;

use Exception;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Sales\Api\Data\OrderAddressInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Psr\Log\LoggerInterface;

class ApiClient
{
    private const API_URLS = [
        'live' => 'https://rest.cashpresso.com',
        'test' => 'https://backend.test-cashpresso.com',
    ];

    public function __construct(
        private readonly ConfigService $configService,
        private readonly Curl $curl,
        private readonly Json $json,
        private readonly LoggerInterface $logger,
        private readonly CacheInterface $cache
    ) {
    }

    public function getPartnerInfo(?string $apiKey = null, ?bool $isLive = null): array
    {
        return $this->send('/backend/ecommerce/v2/partnerInfo', [
            'partnerApiKey' => $apiKey ?? $this->configService->getApiKey(),
        ], $isLive);
    }

    public function getCachedPartnerInfo(?string $apiKey = null, ?bool $isLive = null): array
    {
        $cacheKey = 'cashpresso_partner_info_' . md5(
            ($apiKey ?? $this->configService->getApiKey()) . '_' .
            ($isLive ?? $this->configService->isLive() ? 'live' : 'test')
        );

        $cachedData = $this->cache->load($cacheKey);
        if ($cachedData) {
            return $this->json->unserialize($cachedData);
        }

        try {
            $partnerInfo = $this->getPartnerInfo($apiKey, $isLive);
            $this->cache->save(
                $this->json->serialize($partnerInfo),
                $cacheKey,
                ['cashpresso_partner_info'],
                86400 // 24 hours TTL
            );
            return $partnerInfo;
        } catch (Exception $e) {
            $this->logger->error('Failed to fetch partner info: ' . $e->getMessage());
            throw $e;
        }
    }

    public function testAction(?string $apiKey = null, ?string $apiSecret = null, ?bool $isLive = null): array
    {
        try {
            $result = $this->send('/backend/ecommerce/v2/partnerInfo', [
                'partnerApiKey' => $apiKey ?? $this->configService->getApiKey(),
            ], $isLive);

            $success = $result['success'] ?? false;

            return [
                'success' => $success,
                'data' => $result,
            ];
        } catch (Exception $e) {
            $this->logger->error('Cashpresso API test failed: ' . $e->getMessage());
            return [
                'success' => false,
                'data' => ['error' => $e->getMessage()],
            ];
        }
    }

    public function buyAction(OrderInterface $order, string $cashpressoToken): array
    {
        $data = [
            'partnerApiKey' => $this->configService->getApiKey(),
            'amount' => $order->getGrandTotal(),
            'currency' => $order->getOrderCurrencyCode(),
            'interestFreeDaysMerchant' => $this->configService->getInterestFreeDays(),
            'validUntil' => date('c', strtotime('+ ' . $this->configService->getValidityHours() . ' hours')),
            'bankUsage' => $order->getIncrementId(),
            'description' => $order->getIncrementId(),
            'invoiceAddress' => $this->formatAddress($order->getBillingAddress()),
            'deliveryAddress' => $this->formatAddress($order->getShippingAddress()),
            'basket' => [],
            'callbackUrl' => $this->configService->getCallbackUrl(),
            'c2EcomId' => $cashpressoToken,
        ];

        foreach ($order->getAllItems() as $item) {
            if ($item->getParentItem()) {
                continue; // Skip child items
            }

            $data['basket'][] = [
                'description' => $item->getName(),
                'amount' => $item->getPrice(),
                'times' => (int)$item->getQtyOrdered(),
            ];
        }

        $this->addVerificationHash($data);

        return $this->send('/backend/ecommerce/v2/buy', $data);
    }

    protected function formatAddress(?OrderAddressInterface $orderAddress): ?array
    {
        if (empty($orderAddress)) {
            return null;
        }

        $street = $orderAddress->getStreet();
        $streetLine = is_array($street) ? implode(' ', $street) : $street;

        return [
            'country' => $orderAddress->getCountryId(),
            'zip' => $orderAddress->getPostcode(),
            'city' => $orderAddress->getCity(),
            'street' => $streetLine,
        ];
    }

    protected function addVerificationHash(array &$data, ?string $apiSecret = null): void
    {
        $hashString = ($apiSecret ?? $this->configService->getApiSecret()) . ';' .
            round($data['amount'] * 100) . ';' .
            $data['interestFreeDaysMerchant'] . ';' .
            $data['bankUsage'] . ';';

        $data['verificationHash'] = hash('sha512', $hashString);
    }

    private function send(string $action, array $data, ?bool $forceLive = null): array
    {
        $jsonData = $this->json->serialize($data);
        $url = $this->getApiUrl($forceLive) . $action;

        $this->curl->setHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ]);

        $this->curl->setTimeout(10);
        $this->curl->post($url, $jsonData);

        $response = $this->curl->getBody();
        $httpCode = $this->curl->getStatus();

        if ($httpCode < 200 || $httpCode >= 300) {
            throw new Exception('HTTP Error ' . $httpCode . ': ' . $response);
        }

        $responseData = $this->json->unserialize($response);

        if (!$responseData) {
            throw new Exception('Unexpected response: ' . $response);
        }

        return $responseData;
    }

    public function getApiUrl(?bool $forceLive = null): string
    {
        if ($forceLive !== null) {
            return self::API_URLS[$forceLive ? 'live' : 'test'];
        } else {
            return self::API_URLS[$this->configService->isLive() ? 'live' : 'test'];
        }
    }
}
