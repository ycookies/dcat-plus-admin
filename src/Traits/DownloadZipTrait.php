<?php

namespace Dcat\Admin\Traits;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\RetryMiddleware;
use Psr\Http\Message\ResponseInterface;
use Dcat\Admin\Exception\DcatExtensionInstallException;

trait DownloadZipTrait
{
    /**
     * @param $url
     * @param $file
     *
     * @throws DcatExtensionInstallException
     * @throws \GuzzleHttp\Exception\GuzzleException
     *
     * @return void
     */
    protected function downloadZipFile($url, $file)
    {
        try {
            $client = $this->downloadClient();
            $response = $client->get($url);
            $body = $response->getBody();
            $status = $response->getStatusCode();
            if ($status == 404) {
                throw new DcatExtensionInstallException('安装包不存在');
            }
            $zip_content = $body->getContents();
            if (empty($zip_content)) {
                throw new DcatExtensionInstallException('安装包不存在');
            }
            file_put_contents($file, $zip_content);
        } catch (RequestException $exception) {
            throw new DcatExtensionInstallException('下载链接超时，请重试：'.$exception->getMessage());
        }
    }

    /**
     * 获取下载httpclient.
     *
     * @return Client
     */
    protected function downloadClient()
    {
        // 下载zip
        $options = [
            'timeout'         => 30,
            'connect_timeout' => 5,
            'verify'          => false,
            'http_errors'     => false,
            'headers'         => [
                'Referer'    => request()->fullUrl(),
                'User-Agent' => 'sparkinzy.dcat-extension-client',
            ],
        ];
        $stack = HandlerStack::create(new CurlHandler());
        $stack->push(Middleware::retry(
            $this->retryDecider(),
            $this->retryDelay()
        ));
        $options['handler'] = $stack;

        return new Client($options);
    }

    public function retryDecider()
    {
        return function (
            $retries,
            Request $request,
            Response $response = null,
            TransferException $exception = null
        ) {
            // Limit the number of retries to 5
            if ($retries >= 5) {
                return false;
            }

            // Retry connection exceptions
            if ($exception instanceof ConnectException) {
//                echo "失败一次".PHP_EOL;
                return true;
            }

            if ($response) {
                // Retry on server errors
                if ($response->getStatusCode() >= 500) {
                    return true;
                }

                if ($response->getStatusCode() === 429) {
                    return true;
                }
            }

            return false;
        };
    }

    /**
     * delay 1s 2s 3s 4s 5s.
     *
     * @return \Closure
     */
    public function retryDelay()
    {
        return function ($numberOfRetries, ResponseInterface $response = null) {
            if (is_null($response) || !$response->hasHeader('Retry-After')) {
                return RetryMiddleware::exponentialDelay($numberOfRetries);
            }

            $retryAfter = $response->getHeaderLine('Retry-After');

            if (!is_numeric($retryAfter)) {
                $retryAfter = (new \DateTime($retryAfter))->getTimestamp()
                    - time();
            }

            return (int) $retryAfter * 1000;
        };
    }
}
