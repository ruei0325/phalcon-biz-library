<?php

namespace Codeages\PhalconBiz\JsonRpcClient;

use Datto\JsonRpc\Responses\ErrorResponse;
use Datto\JsonRpc\Responses\ResultResponse;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;

class JsonRpcClient
{
    private $protocol;

    private $http;

    private $options;

    public function __construct(array $options = [])
    {
        $this->options = array_merge([
            'timeout' =>  1,
        ], $options);
    }

    public function call()
    {
        $args = func_get_args();

        if (count($args) < 2) {
            throw new \InvalidArgumentException("Missing rpc endpoint or  method name.");
        }

        $endpoint = array_shift($args);
        $method = array_shift($args);

        $protocol = $this->getProtocol();
        $http = $this->getHttp();

        $protocol->query($this->makeId(), $method, $args);

        try {
            $response = $http->request('POST', $endpoint, [
                'timeout' => $this->options['timeout'],
                'headers' => [
                    'Content-Type' => 'application/json'
                ],
                'body' => $protocol->encode(),
            ]);
            $content = $response->getContent();
        } catch (ExceptionInterface $e) {
            $detail = null;
            if ($e instanceof ServerExceptionInterface || $e instanceof ClientExceptionInterface) {
                $response = $e->getResponse();
                $detail = [
                    'http_code' => $response->getStatusCode(),
                    'content' => $response->getContent(false),
                    'info' => $response->getInfo(),
                ];
            }
            throw new JsonRpcException($e->getMessage(), $e->getCode(), $detail);
        }

        try {
            $response = $protocol->decode($content);
        } catch (\Exception $e) {
            throw new JsonRpcException($e->getMessage(), $e->getCode());
        }

        $response = array_pop($response);

        if ($response instanceof ResultResponse) {
            return $response->getValue();
        } elseif ($response instanceof  ErrorResponse) {
            $code = $response->getCode();
            if (in_array($code, [-32700, -32600, -32601, -32602])) {
                throw new JsonRpcClientException($response->getMessage(), $code, $response->getData());
            }

            throw new JsonRpcServerException($response->getMessage(), $code, $response->getData());
        }

        throw new JsonRpcException("Invalid json rpc response.");
    }

    private function makeId()
    {
        return 1;
    }

    private function getProtocol()
    {
        if (!$this->protocol)  {
            $this->protocol = new \Datto\JsonRpc\Client();
        }
        $this->protocol->reset();
        return $this->protocol;
    }

    private function getHttp()
    {
        if (!$this->http)  {
            $this->http = new \Symfony\Component\HttpClient\CurlHttpClient();
        }

        return $this->http;
    }
}
