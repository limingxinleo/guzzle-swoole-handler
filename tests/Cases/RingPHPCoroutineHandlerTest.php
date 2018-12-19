<?php
// +----------------------------------------------------------------------
// | BaseTest.php [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016-2017 limingxinleo All rights reserved.
// +----------------------------------------------------------------------
// | Author: limx <715557344@qq.com> <https://github.com/limingxinleo>
// +----------------------------------------------------------------------
namespace Tests\Cases;

use Guzzlex\SwooleHandlers\RingPHP\CoroutineHandler;
use Tests\TestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;

class RingPHPCoroutineHandlerTest extends TestCase
{
    const URL = 'https://api.tb.swoft.lmx0536.cn';

    public function testUserInfo()
    {
        if (Coroutine::getuid() > 0) {
            $url = 'api.tb.swoft.lmx0536.cn';
            $handler = new CoroutineHandler();

            $res = $handler([
                'http_method' => 'GET',
                'headers' => ['host' => [$url]],
                'uri' => '/echo',
                'client' => [
                    'curl' => [
                        CURLOPT_USERPWD => 'username:password',
                    ],
                ]
            ]);

            $json = json_decode($res['body'], true);

            $this->assertEquals(0, $json['code']);
            $json = $json['data'];
            $this->assertEquals('Basic ' . base64_encode('username:password'), $json['headers']['authorization'][0]);
        }
        $this->assertTrue(true);
    }

    public function testCreatesErrors()
    {
        if (Coroutine::getuid() > 0) {
            $handler = new CoroutineHandler();
            $response = $handler([
                'http_method' => 'GET',
                'uri' => '/',
                'headers' => ['host' => [static::URL]],
                'client' => ['timeout' => 0.001],
            ]);

            $this->assertNull($response['status']);
            $this->assertNull($response['reason']);
            $this->assertEquals([], $response['headers']);
            $this->assertInstanceOf(
                'GuzzleHttp\Ring\Exception\RingException',
                $response['error']
            );

            $this->assertEquals(
                0,
                strpos('Connection timed out errCode=', $response['error']->getMessage())
            );
        }
    }
}