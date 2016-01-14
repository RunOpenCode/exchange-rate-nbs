<?php

namespace RunOpenCode\ExchangeRate\NationalBankOfSerbia\Util;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use Psr\Http\Message\StreamInterface;

class Browser
{
    /**
     * @var Client
     */
    private $guzzleClient;

    /**
     * @var CookieJar
     */
    private $guzzleCookieJar;

    /**
     * Execute HTTP request and get raw body response.
     *
     * @param string $url URL to fetch.
     * @param string $method HTTP Method.
     * @param array $params Params to send with request.
     * @return StreamInterface
     */
    public function request($url, $method, array $query = array(), array $params = array())
    {
        $client = $this->getGuzzleClient();

        $response = $client->request($method, $url, array(
            'cookies' => $this->getGuzzleCookieJar(),
            'form_params' => $params,
            'query' => $query
        ));

        return $response->getBody();
    }

    /**
     * Get Guzzle Client.
     *
     * @return Client
     */
    private function getGuzzleClient()
    {
        if ($this->guzzleClient === null) {
            $this->guzzleClient = new Client(array('cookies' => true));
        }

        return $this->guzzleClient;
    }

    /**
     * Get Guzzle CookieJar.
     *
     * @return CookieJar
     */
    private function getGuzzleCookieJar()
    {
        if ($this->guzzleCookieJar === null) {
            $this->guzzleCookieJar = new CookieJar();
        }

        return $this->guzzleCookieJar;
    }
}
