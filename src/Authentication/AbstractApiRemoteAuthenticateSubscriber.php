<?php

namespace Codeages\PhalconBiz\Authentication;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Codeages\PhalconBiz\Event\WebEvents;
use Codeages\PhalconBiz\Event\GetResponseEvent;
use Phalcon\Http\RequestInterface;
use Codeages\PhalconBiz\ErrorCode;

abstract class AbstractApiRemoteAuthenticateSubscriber extends ApiAuthenticateSubscriber
{
    /**
     * 调用远程接口签名
     *
     * @param string $signingText
     * @param string $accessKey
     * @return array array[0] signuature, array[1]: ApiUser
     */
    abstract public function signatureRemotely($signingText, $accessKey);

    public function onRequest(GetResponseEvent $event)
    {
        $di = $event->getDI();
        $user = $this->authenticateRemotely($event->getRequest());
        $di['user'] = $di['biz']['user'] = $user;
    }

    public function authenticateRemotely(RequestInterface $request)
    {
        list($strategy, $token) = $this->parseAuthorizationHeader($request);

        if ('signature' == $strategy) {
            return $this->authenticateSignatureRemotely($token, $request);
        } else {
            throw new AuthenticateException('Authorization token is invalid.', ErrorCode::INVALID_AUTHENTICATION);
        }
    }

    protected function authenticateSignatureRemotely($token, $request)
    {
        $token = explode(':', $token);
        if (4 !== count($token)) {
            throw new AuthenticateException('Authorization token format is invalid.', ErrorCode::INVALID_AUTHENTICATION);
        }
        list($accessKey, $deadline, $once, $signature) = $token;

        if ($deadline < time()) {
            throw new AuthenticateException('Authorization token is expired.', ErrorCode::INVALID_AUTHENTICATION);
        }

        $signingText = "{$once}\n{$deadline}\n{$request->getURI()}\n{$request->getRawBody()}";

        list($remoteSignature, $user) = $this->signatureRemotely($signingText, $accessKey);

        if ($remoteSignature != $signature) {
            throw new AuthenticateException('Signature is invalid.', ErrorCode::INVALID_AUTHENTICATION);
        }

        if ($user['locked']) {
            throw new AuthenticateException('User is locked.', ErrorCode::INVALID_AUTHENTICATION);
        }

        if ($user['expired']) {
            throw new AuthenticateException('User is expired.', ErrorCode::INVALID_AUTHENTICATION);
        }

        if ($user['disabled']) {
            throw new AuthenticateException('User is disabled.', ErrorCode::INVALID_AUTHENTICATION);
        }

        return $user;
    }
}