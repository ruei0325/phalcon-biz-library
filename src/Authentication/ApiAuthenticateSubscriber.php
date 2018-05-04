<?php

namespace Codeages\PhalconBiz\Authentication;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Codeages\PhalconBiz\Event\WebEvents;
use Codeages\PhalconBiz\Event\GetResponseEvent;
use Phalcon\Http\RequestInterface;
use Codeages\PhalconBiz\ErrorCode;

class ApiAuthenticateSubscriber implements EventSubscriberInterface
{
    public function onRequest(GetResponseEvent $event)
    {
        $di = $event->getDI();
        $user = $this->authenticate($event->getRequest(), $di['user_provider']);
        $di['user'] = $di['biz']['user'] = $user;
    }

    public function authenticate(RequestInterface $request, UserProvider $userProvider)
    {
        list($strategy, $token) = $this->parseAuthorizationHeader($request);

        if ('secret' == $strategy) {
            return $this->authenticateUseSecret($token, $request, $userProvider);
        } elseif ('signature' == $strategy) {
            return $this->authenticateUseSignature($token, $request, $userProvider);
        } else {
            throw new AuthenticateException('Authorization token is invalid.', ErrorCode::INVALID_AUTHENTICATION);
        }
    }

    protected function parseAuthorizationHeader(RequestInterface $request)
    {
        $token = $request->getHeader('Authorization');
        if (empty($token)) {
            throw new AuthenticateException('Authorization token is missing.', ErrorCode::INVALID_AUTHENTICATION);
        }

        $token = explode(' ', $token);
        if (2 !== count($token)) {
            throw new AuthenticateException('Authorization token is invalid.', ErrorCode::INVALID_AUTHENTICATION);
        }

        list($strategy, $token) = $token;
        $strategy = strtolower($strategy);

        return [$strategy, $token];
    }

    protected function authenticateUseSecret($token, $request, UserProvider $userProvider)
    {
        $token = explode(':', $token);
        if (2 !== count($token)) {
            throw new AuthenticateException('Authorization token format is invalid.', ErrorCode::INVALID_AUTHENTICATION);
        }
        list($accessKey, $secretKey) = $token;

        $user = $this->getUser($accessKey, $request, $userProvider);

        if ($user['secret_key'] != $secretKey) {
            throw new AuthenticateException('Secret key is invalid.', ErrorCode::INVALID_AUTHENTICATION);
        }

        return $user;
    }

    protected function authenticateUseSignature($token, $request, UserProvider $userProvider)
    {
        $token = explode(':', $token);
        if (4 !== count($token)) {
            throw new AuthenticateException('Authorization token format is invalid.', ErrorCode::INVALID_AUTHENTICATION);
        }
        list($accessKey, $deadline, $once, $signature) = $token;


        if ($deadline < time()) {
            throw new AuthenticateException('Authorization token is expired.', ErrorCode::INVALID_AUTHENTICATION);
        }

        $user = $this->getUser($accessKey, $request, $userProvider);
        $signingText = "{$once}\n{$deadline}\n{$request->getURI()}\n{$request->getRawBody()}";

        if ($this->signature($signingText, $user) != $signature) {
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

    protected function getUser($accessKey, $request, $userProvider)
    {
        $user = $userProvider->loadUser($accessKey, $request);
        if (empty($user)) {
            throw new AuthenticateException('Key is not exist.', ErrorCode::INVALID_AUTHENTICATION);
        }

        return $user;
    }

    public function signature($signingText, $user)
    {
        $signature = hash_hmac('sha1', $signingText, $user['secret_key'], true);

        return  str_replace(array('+', '/'), array('-', '_'), base64_encode($signature));
    }

    public function makeSigningTextForSignature($request)
    {
        $uri = $request->getURI();
        $body = $request->getRawBody();

        return "{$uri}\n{$body}";
    }

    public static function getSubscribedEvents()
    {
        return [
            WebEvents::REQUEST => 'onRequest',
        ];
    }
}
