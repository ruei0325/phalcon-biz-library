<?php

namespace Codeages\PhalconBiz\JsonRpcClient;

use Codeages\Biz\Framework\Service\Exception\ServiceException;

trait JsonRpcClientTrait
{
    protected $endpoint;

    protected $biz;

    /**
     * @return \App\JsonRpcClient
     */
    private function rpc()
    {
        return $this->biz['rpc'];
    }

    private function call()
    {
        $arguments = array_merge([$this->endpoint], func_get_args());

        try {
            $result = call_user_func_array([$this->rpc(), 'call'], $arguments);
        } catch (JsonRpcException $e) {
            throw new ServiceException($e->getMessage(), $e->getCode(), $e);
        }

        return $result;
    }
}