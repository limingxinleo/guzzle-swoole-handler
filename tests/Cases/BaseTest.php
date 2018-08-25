<?php
// +----------------------------------------------------------------------
// | BaseTest.php [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016-2017 limingxinleo All rights reserved.
// +----------------------------------------------------------------------
// | Author: limx <715557344@qq.com> <https://github.com/limingxinleo>
// +----------------------------------------------------------------------
namespace Tests\Cases;

use Tests\TestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;
use Guzzlex\SwooleHandlers\CoroutineHandler;

class BaseTest extends TestCase
{
    public function testExample()
    {
        $this->assertTrue(true);
    }

    const URL = 'https://api.tb.swoft.lmx0536.cn';

    protected function tearDown()/* The :void return type declaration that should be here would cause a BC issue */
    {
        parent::tearDown();
        swoole_timer_after(6 * 1000, function () {
            swoole_event_exit();
        });
    }

    protected function getHandler($options = [])
    {
        return new CoroutineHandler($options);
    }

    public function testCreatesCurlErrors()
    {
        go(function () {
            $handler = new CoroutineHandler();
            $request = new Request('GET', 'http://localhost:123');
            try {
                $handler($request, ['timeout' => 0.001, 'connect_timeout' => 0.001])->wait();
            } catch (\Exception $ex) {
                $this->assertInstanceOf(ConnectException::class, $ex);
                $this->assertEquals(0, strpos($ex->getMessage(), 'Connection timed out errCode='));
            }
        });
        $this->assertTrue(true);
    }

    public function testReusesHandles()
    {
        go(function () {
            $a = new CoroutineHandler();
            $request = new Request('GET', static::URL);
            $a($request, []);
            $a($request, []);
        });
        $this->assertTrue(true);
    }

    public function testDoesSleep()
    {
        go(function () {
            $a = new CoroutineHandler();
            $request = new Request('GET', static::URL);
            $s = microtime(true);
            $a($request, ['delay' => 1])->wait();
            $this->assertGreaterThan(0.001, microtime(true) - $s);
        });
        $this->assertTrue(true);
    }

    public function testCreatesErrorsWithContext()
    {
        go(function () {
            $handler = new CoroutineHandler();
            $request = new Request('GET', 'http://localhost:123');
            $called = false;
            $p = $handler($request, ['timeout' => 0.001])
                ->otherwise(function (ConnectException $e) use (&$called) {
                    $called = true;
                    $this->assertArrayHasKey('errCode', $e->getHandlerContext());
                    $this->assertArrayHasKey('statusCode', $e->getHandlerContext());
                });
            $p->wait();
            $this->assertTrue($called);
        });

        $this->assertTrue(true);
    }

    public function testGuzzleClient()
    {
        go(function () {
            $client = new Client([
                'base_uri' => static::URL
            ]);
            $res = $client->get('/echo', [
                'timeout' => 10,
                'headers' => [
                    'X-TOKEN' => md5(1234)
                ],
                'json' => [
                    'id' => 1
                ]
            ])->getBody()->getContents();
            $res = \GuzzleHttp\json_decode($res, true);

            $this->assertEquals(0, $res['code']);
            $res = $res['data'];
            $this->assertEquals(md5(1234), $res['headers']['x-token'][0]);
            $this->assertEquals(1, $res['json']['id']);
        });

        $this->assertTrue(true);
    }
}