<?php
declare(strict_types=1);

namespace Credi2\CashpressoPayments\Service;

use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Serialize\Serializer\Json;
use Psr\Log\LoggerInterface;

class BnplApiClient
{
    private const TEST_BASE_URL = 'https://rest.purchase.c2-postea.com';
    private const LIVE_BASE_URL = 'https://rest.purchase.bnpl.cashpresso.com/';

    public function __construct(
        private readonly Curl            $curl,
        private readonly Json            $json,
        private readonly LoggerInterface $logger,
        private string                   $apiKey = '',
        private bool                     $isLive = true
    )
    {
    }

    public function setApiKey(string $apiKey): void
    {
        $this->apiKey = $apiKey;
    }

    public function setIsLive(bool $isLive): void
    {
        $this->isLive = $isLive;
    }

    /**
     * Initialize a new Checkout
     * POST /bnpl/purchase/v1/checkouts
     */
    public function checkoutInit(array $body): array
    {
        return $this->sendRequest('POST', '/bnpl/purchase/v1/checkouts', $body);
    }

    /**
     * Initialize a new Authorization
     * POST /bnpl/purchase/v1/checkouts/{checkoutId}/authorizationInit
     */
    public function authorizationInit(string $checkoutId, array $body): array
    {
        $path = "/bnpl/purchase/v1/checkouts/{$checkoutId}/authorizationInit";
        return $this->sendRequest('POST', $path, $body);
    }

    /**
     * Place a new Order
     * POST /bnpl/purchase/v1/authorizations/{authorizationId}/order
     */
    public function placeOrder(string $authorizationId, array $body): array
    {
        $path = "/bnpl/purchase/v1/authorizations/{$authorizationId}/order";
        return $this->sendRequest('POST', $path, $body);
    }

    /**
     * Cancel an Authorization
     * DELETE /bnpl/purchase/v1/authorizations/{authorizationId}
     */
    public function cancelAuthorization(string $authorizationId, array $body): array
    {
        $path = "/bnpl/purchase/v1/authorizations/{$authorizationId}";
        return $this->sendRequest('DELETE', $path, $body);
    }

    /**
     * Create a Refund for an Order
     * POST /bnpl/purchase/v1/orders/{orderId}/refunds
     */
    public function createRefund(string $orderId, array $body): array
    {
        $path = "/bnpl/purchase/v1/orders/{$orderId}/refunds";
        return $this->sendRequest('POST', $path, $body);
    }

    /**
     * Capture an Order or parts of it as shipped
     * POST /bnpl/purchase/v1/orders/{orderId}/captures
     */
    public function captureOrder(string $orderId, array $body): array
    {
        $path = "/bnpl/purchase/v1/orders/{$orderId}/captures";
        return $this->sendRequest('POST', $path, $body);
    }

    /**
     * Update Merchant References of an Order
     * PATCH /bnpl/purchase/v1/orders/{orderId}/merchantreferences
     */
    public function updateOrdersMerchantReferences(string $orderId, array $body): array
    {
        $path = "/bnpl/purchase/v1/orders/{$orderId}/merchantreferences";
        return $this->sendRequest('PATCH', $path, $body);
    }

    /**
     * Update Basket of an Order
     * PATCH /bnpl/purchase/v1/orders/{orderId}/basket
     */
    public function updateOrdersBasket(string $orderId, array $body): array
    {
        $path = "/bnpl/purchase/v1/orders/{$orderId}/basket";
        return $this->sendRequest('PATCH', $path, $body);
    }

    /**
     * Cancel an Order
     * DELETE /bnpl/purchase/v1/orders/{orderId}
     */
    public function cancelOrder(string $orderId, array $body): array
    {
        $path = "/bnpl/purchase/v1/orders/{$orderId}";
        return $this->sendRequest('DELETE', $path, $body);
    }

    /**
     * Generic HTTP request handler
     */
    private function sendRequest(string $method, string $uri, ?array $body = null): array
    {
        $url = ($this->isLive ? self::LIVE_BASE_URL : self::TEST_BASE_URL) . $uri;

        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $this->apiKey,
        ];

        $this->curl->setHeaders($headers);
        $this->curl->setTimeout(30);

        if ($body !== null) {
            $payload = $this->json->serialize($body);
        } else {
            $payload = '';
        }

        switch (strtoupper($method)) {
            case 'GET':
                $this->curl->get($url);
                break;
            case 'POST':
                $this->curl->post($url, $payload);
                break;
            case 'PATCH':
                $this->curl->setOption(CURLOPT_CUSTOMREQUEST, 'PATCH');
                $this->curl->post($url, $payload);
                break;
            case 'DELETE':
                $this->curl->setOption(CURLOPT_CUSTOMREQUEST, 'DELETE');
                $this->curl->post($url, $payload);
                break;
            default:
                throw new \InvalidArgumentException("Unsupported HTTP method: {$method}");
        }

        $response = $this->curl->getBody();
        $httpCode = $this->curl->getStatus();

        if ($httpCode < 200 || $httpCode >= 300) {
            $this->logger->error("BNPL API error ({$httpCode}): {$response}");

            $errorData = [];
            try {
                $errorData = $this->json->unserialize($response);
            } catch (\Exception $e) {
                // Invalid JSON response
            }

            $message = $errorData['description'] ?? 'Unknown error';
            throw new \Exception("API error ({$httpCode}): {$message}");
        }

        try {
            $data = $this->json->unserialize($response);
        } catch (\Exception $e) {
            $this->logger->error("Invalid JSON response from BNPL API: {$response}");
            throw new \Exception("Invalid JSON response: " . $e->getMessage());
        }

        return $data;
    }
}
