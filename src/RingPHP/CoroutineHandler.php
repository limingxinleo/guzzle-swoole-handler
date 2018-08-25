<?php
namespace Guzzlex\SwooleHandlers;

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7;
use Psr\Http\Message\RequestInterface;
use Swoole\Coroutine;
use GuzzleHttp\RequestOptions;
use Swoole\Coroutine\Http\Client;
use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Psr7\Uri;

/**
 * Http handler that uses Swoole Coroutine as a transport layer.
 */
class CoroutineHandler
{
    /**
     * Swoole 协程 Http 客户端
     *
     * @var \Swoole\Coroutine\Http\Client
     */
    private $client;

    /**
     * 配置选项
     *
     * @var array
     */
    private $settings = [];

    public function __invoke($request)
    {
        $method = $request['http_method'];
        $scheme = $request['scheme'];
        $uri = $request['uri'];
        $body = $request['body'];
        $headers = $request['headers'];
        $params = parse_url(Core::url($request));
        $host = $params['host'];
        $port = $params['port'];
        $clientConfig = $request['client']['curl'] ?? [];

        $ssl = 'https' === $scheme;

        $this->client = new Client($host, $port, $ssl);
        $this->client->setMethod($method);
        $this->client->setData($body);

        // 初始化Headers
        $this->initHeaders($headers);

        if (isset($clientConfig[CURLOPT_USERPWD])) {
            list($name, $pwd) = explode(':', $clientConfig[CURLOPT_USERPWD]);
            $this->settings['http_user'] = $name;
            $this->settings['http_password'] = $pwd;
        }

        // 设置客户端参数
        if (!empty($this->settings)) {
            $this->client->set($this->settings);
        }
    }

    protected function initHeaders($headerArray = [])
    {
        $headers = [];
        foreach ($headerArray as $name => $value) {
            $headers[$name] = implode(',', $value);
        }
        // TODO: 不知道为啥，这个扔进来就400
        unset($headers['Content-Length']);
        $this->client->setHeaders($headers);
    }
}