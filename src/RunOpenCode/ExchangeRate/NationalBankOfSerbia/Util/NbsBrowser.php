<?php
/*
 * This file is part of the Exchange Rate package, an RunOpenCode project.
 *
 * Implementation of exchange rate crawler for National Bank of Serbia, http://www.nbs.rs.
 *
 * (c) 2016 RunOpenCode
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace RunOpenCode\ExchangeRate\NationalBankOfSerbia\Util;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use Psr\Http\Message\StreamInterface;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Class NbsBrowser
 *
 * Browser browses trough web site of National Bank of Serbia and fetches documents with rates.
 *
 * @package RunOpenCode\ExchangeRate\NationalBankOfSerbia\Util
 */
class NbsBrowser
{
    const SOURCE = 'http://www.nbs.rs/kursnaListaModul/naZeljeniDan.faces';
    /**
     * @var Client
     */
    private $guzzleClient;

    /**
     * @var CookieJar
     */
    private $guzzleCookieJar;

    /**
     * Get XML document with rates.
     *
     * @param \DateTime $date
     * @param string $rateType
     * @return StreamInterface
     */
    public function getXmlDocument(\DateTime $date, $rateType)
    {
        return $this->request('POST', array(), array(
            'index:brKursneListe:' => '',
            'index:year' => $date->format('Y'),
            'index:inputCalendar1' => $date->format('d/m/Y'),
            'index:vrsta' => call_user_func(function($rateType) {
                switch ($rateType) {
                    case 'foreign_cache_buying':        // FALL TROUGH
                    case 'foreign_cache_selling':
                        return 1;
                        break;
                    case 'foreign_exchange_buying':     // FALL TROUGH
                    case 'foreign_exchange_selling':
                        return 2;
                        break;
                    default:
                        return 3;
                        break;
                }
            }, $rateType),
            'index:prikaz' => 3, // XML
            'index:buttonShow' => 'Show',
            'index' => 'index',
            'com.sun.faces.VIEW' => $this->getFormCsrfToken()
        ));
    }

    /**
     * Execute HTTP request and get raw body response.
     *
     * @param string $method HTTP Method.
     * @param array $params Params to send with request.
     * @return StreamInterface
     */
    private function request($method, array $query = array(), array $params = array())
    {
        $client = $this->getGuzzleClient();

        $response = $client->request($method, self::SOURCE, array(
            'cookies' => $this->getGuzzleCookieJar(),
            'form_params' => $params,
            'query' => $query
        ));

        return $response->getBody();
    }

    /**
     * Get NBS's form CSRF token.
     *
     * @return string CSRF token.
     *
     * @throws \RuntimeException When API is changed.
     */
    private function getFormCsrfToken()
    {
        $crawler = new Crawler($this->request('GET')->getContents());

        $hiddens = $crawler->filter('input[type="hidden"]');

        /**
         * @var \DOMElement $hidden
         */
        foreach ($hiddens as $hidden) {

            if ($hidden->getAttribute('id') === 'com.sun.faces.VIEW') {
                return $hidden->getAttribute('value');
            }
        }

        throw new \RuntimeException('FATAL ERROR: National Bank of Serbia changed it\'s API, unable to extract token.');
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
