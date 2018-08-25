<?php
namespace Guzzlex\SwooleHandlers\RingPHP;

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7;
use GuzzleHttp\Ring\Core;
use GuzzleHttp\Ring\Future\CompletedFutureArray;
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

    private $btime;

    private $effectiveUrl = '';

    private $options;

    public function __construct($options = [])
    {
        $this->options;
    }

    public function __invoke($request)
    {
        $method = $request['http_method'] ?? 'GET';
        $scheme = $request['scheme'] ?? 'http';
        $ssl = 'https' === $scheme;
        $uri = $request['uri'] ?? '/';
        $body = $request['body'] ?? '';
        $this->effectiveUrl = Core::url($request);
        $params = parse_url($this->effectiveUrl);
        $host = $params['host'];
        if (!isset($params['port'])) {
            $params['port'] = $ssl ? '443' : '80';
        }
        $port = $params['port'];
        $path = $params['path'] ?? '/';

        $this->client = new Client($host, $port, $ssl);
        $this->client->setMethod($method);
        $this->client->setData($body);

        // 初始化Headers
        $this->initHeaders($request);
        $this->initSettings($this->options);

        // 设置客户端参数
        if (!empty($this->settings)) {
            $this->client->set($this->settings);
        }

        $this->btime = microtime(true);
        $this->client->execute($path);

        $ex = $this->checkStatusCode($request);
        if ($ex !== true) {
            return \GuzzleHttp\Promise\rejection_for($ex);
        }

        return $this->getResponse();
    }

    protected function initSettings($options)
    {
        if (isset($options['delay'])) {
            Coroutine::sleep((float)$options['delay'] / 1000);
        }

        // 超时
        if (isset($options['timeout']) && $options['timeout'] > 0) {
            $this->settings['timeout'] = $options['timeout'];
        }
    }

    protected function initHeaders($request)
    {
        $headers = [];
        foreach ($request['headers'] ?? [] as $name => $value) {
            $headers[$name] = implode(',', $value);
        }

        $clientConfig = $request['client']['curl'] ?? [];
        if (isset($clientConfig[CURLOPT_USERPWD])) {
            $userInfo = $clientConfig[CURLOPT_USERPWD];
            $headers['Authorization'] = sprintf('Basic %s', base64_encode($userInfo));
        }

        // TODO: 不知道为啥，这个扔进来就400
        unset($headers['Content-Length']);
        $this->client->setHeaders($headers);
    }

    protected function getResponse()
    {
        return new CompletedFutureArray([
            'transfer_stats' => [
                'total_time' => microtime(true) - $this->btime,
            ],
            'effective_url' => $this->effectiveUrl,
            'headers' => isset($this->client->headers) ? $this->client->headers : [],
            'status' => $this->client->statusCode,
            'body' => $this->client->body
        ]);
    }

    protected function checkStatusCode($request)
    {
        $statusCode = $this->client->statusCode;
        $errCode = $this->client->errCode;
        $ctx = [
            'statusCode' => $statusCode,
            'errCode' => $errCode,
        ];
        if ($statusCode === -1) {
            return new ConnectException(sprintf('Connection timed out errCode=%s', $errCode), $request, null, $ctx);
        } elseif ($statusCode === -2) {
            return new RequestException('Request timed out', $request, null, null, $ctx);
        }

        return true;
    }
}