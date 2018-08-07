<?php

use PHPUnit\Framework\TestCase;
use Codeages\PhalconBiz\Authentication\ApiUser;
use Codeages\PhalconBiz\Authentication\AbstractApiRemoteAuthenticateSubscriber;
use Phalcon\Http\Request;

class ApiRemoteAuthenticateSubscriberTest extends TestCase
{
    public function testAuthenticate()
    {
        $subsciber = new class() extends AbstractApiRemoteAuthenticateSubscriber {
            public function signatureRemotely($signingText, $accessKey)
            {
                $user = new ApiUser([
                    'id' => 1,
                    'username' => 'testuser',
                    'access_key' => 'test_access_key',
                    'secret_key' => 'test_secret_key',
                    'login_client' => 'test_client',
                    'login_ip' => '127.0.0.1',
                ]);

                return ['test_signuature', $user];
            }
        };

        $request = new Request();
        $token = [
            'test_access_key',
            time() + 60,
            'test_once',
            'test_signuature',
        ];
        $_SERVER['HTTP_AUTHORIZATION'] = 'Signature '.implode(':', $token);

        $user = $subsciber->authenticateRemotely($request);

        $this->assertEquals(1, $user['id']);
        $this->assertEquals('test_access_key', $user['access_key']);
    }
}
