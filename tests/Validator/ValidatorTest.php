<?php

use Codeages\PhalconBiz\Validator\ValidationException;
use Codeages\PhalconBiz\Validator\Validator;
use PHPUnit\Framework\TestCase;

class AliyunStorageTest extends TestCase
{
    public function testFilter_Pass()
    {
        $v = new Validator();
        $v->required('username')->lengthBetween(0, 16);
        $v->required('email')->email();
        $v->optional('about')->lengthBetween(0, 1000);

        $user = [
            'username' => 'xxxxxxxxxx',
            'email' => 'xxxxx@xxx.com',
            'about' => '',
            'age' => 18,
        ];

        $filteredUser = $v->filter($user);

        $this->assertEquals($user['username'], $filteredUser['username']);
        $this->assertEquals($user['email'], $filteredUser['email']);
        $this->assertEquals($user['about'], $filteredUser['about']);
        $this->assertArrayNotHasKey('age', $filteredUser);
    }

    public function testFilter_Failed()
    {
        $v = new Validator();
        $v->required('username')->lengthBetween(0, 16);
        $v->required('email')->email();

        $user = [
            'username' => 'user5678901234567890',
            'email' => 'xxxxx',
        ];

        $this->expectException(ValidationException::class);
        $filteredUser = $v->filter($user);
    }
}
