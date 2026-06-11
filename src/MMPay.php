<?php

namespace MMPay;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class MMPay
{
    private $appId;
    private $publishableKey;
    private $secretKey;
    private $apiBaseUrl;
    private $isSandbox;
    private $client;
    private $btoken;
    private $listeners = [];

    public function __construct(array $options)
    {
        $this->appId = $options['appId'];
        $this->publishableKey = $options['publishableKey'];
        $this->secretKey = $options['secretKey'];
        $this->apiBaseUrl = rtrim($options['apiBaseUrl'], '/');
        $this->isSandbox = (strpos($this->publishableKey, '_test_') !== false || strpos($this->secretKey, '_test_') !== false);
        
        $this->client = new Client([
            'base_uri' => $this->apiBaseUrl,
            'timeout'  => 30.0,
        ]);
    }

    private function generateSignature(string $bodyString, string $nonce): string
    {
        $stringToSign = "{$nonce}.{$bodyString}";
        return hash_hmac('sha256', $stringToSign, $this->secretKey);
    }

    private function jsonStringify($data): string
    {
        return json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    private function getNonce(): string
    {
        return (string) round(microtime(true) * 1000);
    }

    public function on(string $event, callable $callback): self
    {
        if (!isset($this->listeners[$event])) {
            $this->listeners[$event] = [];
        }
        $this->listeners[$event][] = $callback;
        return $this;
    }

    private function emit(string $event, ...$args): void
    {
        if (isset($this->listeners[$event])) {
            foreach ($this->listeners[$event] as $callback) {
                call_user_func_array($callback, $args);
            }
        }
    }

    public function handShake(array $payload)
    {
        $segment = $this->isSandbox ? 'sandbox-handshake' : 'handshake';
        $endpoint = '/payments/' . $segment;
        $nonce = $this->getNonce();
        
        $bodyString = $this->jsonStringify($payload);
        $signature = $this->generateSignature($bodyString, $nonce);

        try {
            $response = $this->client->post($endpoint, [
                'headers' => [
                    'Authorization'     => 'Bearer ' . $this->publishableKey,
                    'X-Mmpay-Nonce'     => $nonce,
                    'X-Mmpay-Signature' => $signature,
                    'Content-Type'      => 'application/json',
                ],
                'body' => $bodyString
            ]);

            $data = json_decode($response->getBody(), true);
            if (isset($data['token'])) {
                $this->btoken = $data['token'];
            }
            return $data;

        } catch (GuzzleException $e) {
            $msg = $e->getMessage();
            if ($e->hasResponse()) {
                $msg .= " Details: " . (string) $e->getResponse()->getBody();
            }
            throw new \Exception($msg);
        }
    }

    public function pay(array $params)
    {
        $segment = $this->isSandbox ? 'sandbox-create' : 'create';
        $endpoint = '/payments/' . $segment;
        $nonce = $this->getNonce();

        $xpayload = [];
        $xpayload['appId']   = $this->appId;
        $xpayload['nonce']   = $nonce;
        $xpayload['amount']  = $params['amount'];
        $xpayload['orderId'] = $params['orderId'];

        if (isset($params['callbackUrl'])) {
            $xpayload['callbackUrl'] = $params['callbackUrl'];
        }

        if (isset($params['customMessage'])) {
            $xpayload['customMessage'] = $params['customMessage'];
        }
        
        if (isset($params['items'])) {
            $xpayload['items'] = $params['items'];
        }

        $bodyString = $this->jsonStringify($xpayload);
        $signature = $this->generateSignature($bodyString, $nonce);

        $this->handShake([
            'orderId' => (string) $xpayload['orderId'], 
            'nonce'   => (string) $xpayload['nonce']
        ]);

        try {
            $response = $this->client->post($endpoint, [
                'headers' => [
                    'Authorization'     => 'Bearer ' . $this->publishableKey,
                    'X-Mmpay-Btoken'    => $this->btoken,
                    'X-Mmpay-Nonce'     => $nonce,
                    'X-Mmpay-Signature' => $signature,
                    'Content-Type'      => 'application/json',
                ],
                'body' => $bodyString
            ]);

            return json_decode($response->getBody(), true);

        } catch (GuzzleException $e) {
            $msg = $e->getMessage();
            if ($e->hasResponse()) {
                $msg .= " Details: " . (string) $e->getResponse()->getBody();
            }
            throw new \Exception($msg);
        }
    }

    public function get(array $params)
    {
        $segment = $this->isSandbox ? 'sandbox-get' : 'get';
        $endpoint = '/payments/' . $segment;
        $nonce = $this->getNonce();

        $xpayload = [];
        $xpayload['orderId'] = $params['orderId'];
        $xpayload['nonce']   = $nonce;

        $bodyString = $this->jsonStringify($xpayload);
        $signature = $this->generateSignature($bodyString, $nonce);

        $this->handShake([
            'orderId' => (string) $xpayload['orderId'], 
            'nonce'   => (string) $xpayload['nonce']
        ]);

        try {
            $response = $this->client->post($endpoint, [
                'headers' => [
                    'Authorization'     => 'Bearer ' . $this->publishableKey,
                    'X-Mmpay-Btoken'    => $this->btoken,
                    'X-Mmpay-Nonce'     => $nonce,
                    'X-Mmpay-Signature' => $signature,
                    'Content-Type'      => 'application/json',
                ],
                'body' => $bodyString
            ]);

            return json_decode($response->getBody(), true);

        } catch (GuzzleException $e) {
            $msg = $e->getMessage();
            if ($e->hasResponse()) {
                $msg .= " Details: " . (string) $e->getResponse()->getBody();
            }
            throw new \Exception($msg);
        }
    }

    public function cancel(array $params)
    {
        $segment = $this->isSandbox ? 'sandbox-cancel' : 'cancel';
        $endpoint = '/payments/' . $segment;
        $nonce = $this->getNonce();

        $xpayload = [];
        $xpayload['orderId'] = $params['orderId'];
        $xpayload['nonce']   = $nonce;

        $bodyString = $this->jsonStringify($xpayload);
        $signature = $this->generateSignature($bodyString, $nonce);

        $this->handShake([
            'orderId' => (string) $xpayload['orderId'], 
            'nonce'   => (string) $xpayload['nonce']
        ]);

        try {
            $response = $this->client->post($endpoint, [
                'headers' => [
                    'Authorization'     => 'Bearer ' . $this->publishableKey,
                    'X-Mmpay-Btoken'    => $this->btoken,
                    'X-Mmpay-Nonce'     => $nonce,
                    'X-Mmpay-Signature' => $signature,
                    'Content-Type'      => 'application/json',
                ],
                'body' => $bodyString
            ]);

            return json_decode($response->getBody(), true);

        } catch (GuzzleException $e) {
            $msg = $e->getMessage();
            if ($e->hasResponse()) {
                $msg .= " Details: " . (string) $e->getResponse()->getBody();
            }
            throw new \Exception($msg);
        }
    }

    public function verifyCb(string $payload, string $nonce, string $expectedSignature): bool
    {
        if (empty($payload) || empty($nonce) || empty($expectedSignature)) {
            return false;
        }

        $stringToSign = "{$nonce}.{$payload}";
        $generatedSignature = hash_hmac('sha256', $stringToSign, $this->secretKey);

        return hash_equals($generatedSignature, $expectedSignature);
    }

    public function listen(string $payload, string $nonce, string $expectedSignature): self
    {
        try {
            if (!$this->verifyCb($payload, $nonce, $expectedSignature)) {
                throw new \Exception('Signature verification failed');
            }

            $tx = json_decode($payload, true);
            $status = $tx['status'] ?? null;
            $condition = $tx['condition'] ?? null;

            switch ($status) {
                case 'PENDING':
                    $this->emit('tx:create', $tx);
                    break;
                case 'SUCCESS':
                    if ($condition === 'TOUCHED') {
                        $this->emit('tx:heartbeat', $tx);
                    } else {
                        $this->emit('tx:success', $tx);
                    }
                    break;
                case 'FAILED':
                    $this->emit('tx:failed', $tx);
                    break;
                case 'REFUNDED':
                    $this->emit('tx:refunded', $tx);
                    break;
                case 'CANCELLED':
                    $this->emit('tx:cancel', $tx);
                    break;
                case 'EXPIRED':
                    $this->emit('tx:expire', $tx);
                    break;
                default:
                    $this->emit('tx:unknown', $tx);
            }
        } catch (\Exception $e) {
            $this->emit('error', $e);
        }

        return $this;
    }

    public function onTxCreate(callable $cb): self { return $this->on('tx:create', $cb); }
    public function onTxSuccess(callable $cb): self { return $this->on('tx:success', $cb); }
    public function onTxFail(callable $cb): self { return $this->on('tx:failed', $cb); }
    public function onTxRefund(callable $cb): self { return $this->on('tx:refunded', $cb); }
    public function onTxCancel(callable $cb): self { return $this->on('tx:cancel', $cb); }
    public function onTxExpire(callable $cb): self { return $this->on('tx:expire', $cb); }
    public function onHeartbeat(callable $cb): self { return $this->on('tx:heartbeat', $cb); }
}