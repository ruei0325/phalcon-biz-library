<?php

use PHPUnit\Framework\TestCase;
use Codeages\PhalconBiz\Authentication\ApiUser;
use Codeages\PhalconBiz\Authentication\ApiAuthenticateSubscriber;
use Phalcon\Http\Request;
use Codeages\PhalconBiz\Authentication\UserProvider;
use Phalcon\Http\RequestInterface;

class ApiAuthenticateSubscriberTest extends TestCase
{
    public function testAuthenticate()
    {
        $userProvider = new class() implements UserProvider {
            public function loadUser($identifier, RequestInterface $request)
            {
                return new ApiUser([
                    'id' => 1,
                    'username' => 'testuser',
                    'access_key' => 'test_access_key',
                    'secret_key' => 'test_secret_key',
                    'login_client' => 'test_client',
                    'login_ip' => '127.0.0.1',
                ]);
            }
        };

        $token = [
            'test_access_key',
            time() + 60,
            'test_once',
        ];

        $request = new Request();
        $subsciber = new ApiAuthenticateSubscriber();
        $signingText = "{$token[2]}\n{$token[1]}\n{$request->getURI()}\n{$request->getRawBody()}";
        $token[] = $subsciber->signature($signingText, $userProvider->loadUser($token[0], $request));
        $_SERVER['HTTP_AUTHORIZATION'] = 'Signature '.implode(':', $token);

        $user = $subsciber->authenticate($request, $userProvider);

        $this->assertEquals(1, $user['id']);
        $this->assertEquals('test_access_key', $user['access_key']);
    }
}
