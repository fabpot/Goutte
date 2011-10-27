<?php

namespace Goutte;

use Symfony\Component\BrowserKit\Client as BaseClient;
use Symfony\Component\BrowserKit\History;
use Symfony\Component\BrowserKit\CookieJar;
use Symfony\Component\BrowserKit\Request;
use Symfony\Component\BrowserKit\Response;

use Zend\Http\Client as ZendClient;
use Zend\Http\Response as ZendResponse;

/*
 * This file is part of the Goutte package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Client.
 *
 * @package Goutte
 * @author  Fabien Potencier <fabien.potencier@symfony-project.com>
 */
class Client extends BaseClient
{
    const VERSION = '0.1';

    protected $zendConfig;
    protected $headers = array();
    protected $auth = null;

    public function __construct(array $zendConfig = array(), array $server = array(), History $history = null, CookieJar $cookieJar = null)
    {
        $this->zendConfig = $zendConfig;

        parent::__construct($server, $history, $cookieJar);
    }

    public function setHeader($name, $value)
    {
        $this->headers[$name] = $value;
    }

    public function setAuth($user, $password = '', $type = ZendClient::AUTH_BASIC)
    {
        $this->auth = array(
            'user'     => $user,
            'password' => $password,
            'type'     => $type
        );
    }

    protected function doRequest($request)
    {
        $client = $this->createClient($request);

        $response = $client->send($client->getRequest());

        return $this->createResponse($response);
    }

    protected function createClient(Request $request)
    {
        $client = $this->createZendClient();
        $client->setUri($request->getUri());
        $client->setConfig(array_merge(array(
            'maxredirects' => 0,
            'timeout'      => 30,
            'useragent'    => $this->server['HTTP_USER_AGENT'],
            'adapter'      => 'Zend\\Http\\Client\\Adapter\\Socket',
            ), $this->zendConfig));
        $client->setMethod(strtoupper($request->getMethod()));

        if ('POST' == $request->getMethod()) {
            $client->setParameterPost($request->getParameters());
        }

        foreach ($this->headers as $name => $value) {
            $client->setHeaders(array($name => $value));
        }

        if ($this->auth !== null) {
            $client->setAuth(
                $this->auth['user'],
                $this->auth['password'],
                $this->auth['type']
            );
        }

        foreach ($this->getCookieJar()->allValues($request->getUri()) as $name => $value) {
            $client->addCookie($name, $value);
        }

        foreach ($request->getFiles() as $name => $info) {
            if (isset($info['tmp_name']) && '' !== $info['tmp_name']) {
                $filename = $info['name'];
                if (false === ($data = @file_get_contents($info['tmp_name']))) {
                    throw new \RuntimeException("Unable to read file '{$filename}' for upload");
                }
                $client->setFileUpload($filename, $name, $data);
            }
        }

        return $client;
    }

    protected function createResponse(ZendResponse $response)
    {
        return new Response($response->getBody(), $response->getStatusCode(), $response->headers()->toArray());
    }

    protected function createZendClient()
    {
        return new ZendClient();
    }
}
