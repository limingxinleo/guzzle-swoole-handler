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

    protected function tearDown()/* The :void return type declaration that should be here would cause a BC issue */
    {
        parent::tearDown();
        swoole_timer_after(6 * 1000, function () {
            swoole_event_exit();
        });
    }

    public function testUserInfo()
    {
        go(function () {
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
        });
        $this->assertTrue(true);
    }
}