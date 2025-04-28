<?php
/*
 * This file is part of the Exchange Rate package, an RunOpenCode project.
 *
 * Implementation of exchange rate crawler for National Bank of Serbia, http://www.nbs.rs.
 *
 * (c) 2017 RunOpenCode
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace RunOpenCode\ExchangeRate\NationalBankOfSerbia\Util;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Psr7\Stream;
use Psr\Http\Message\StreamInterface;
use RunOpenCode\ExchangeRate\NationalBankOfSerbia\Enum\RateType;
use RunOpenCode\ExchangeRate\NationalBankOfSerbia\Exception\RuntimeException;
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
    const SOURCE = 'https://webappcenter.nbs.rs/ExchangeRateWebApp/ExchangeRate';

    /**
     * @var Client
     */
    private $guzzleClient;

    /**
     * Get XML document with rates.
     *
     * @param \DateTime $date
     * @param string $rateType
     * @return StreamInterface
     */
    public function getXmlDocument(\DateTime $date, $rateType)
    {
        $rateInfo = call_user_func(function ($rateType) {
            switch ($rateType) {
                case RateType::FOREIGN_EXCHANGE_BUYING:     // FALL TROUGH
                case RateType::FOREIGN_EXCHANGE_SELLING:
                    return ['id' => 1, 'rateName' => 'devize'];
                case RateType::FOREIGN_CASH_BUYING:        // FALL TROUGH
                case RateType::FOREIGN_CASH_SELLING:
                    return ['id' => 2, 'rateName' => 'efektiva'];
                default:
                    return ['id' => 3, 'rateName' => 'srednjiKurs'];
            }
        }, $rateType);

        $htmlContent = $this->getHtmlContent($date, $rateInfo['id']);

        $idPosition = strpos($htmlContent, 'ExchangeRateListID=');

        if (!$idPosition) {
            throw new RuntimeException('FATAL ERROR: National Bank of Serbia changed it\'s API, unable to extract list id.'); // @codeCoverageIgnore
        }

        return $this->getXMLContent($idPosition, $htmlContent, $rateInfo['rateName']);
    }

    private function getHtmlContent(\DateTime $date, int $id): string
    {
        $client = $this->getGuzzleClient();

        $response = $client->request('GET', \sprintf('%s/IndexByDate?isSearchExecuted=true&Date=%s.&ExchangeRateListTypeID=%s', self::SOURCE, $date->format('d.m.Y'), $id));

        if (200 !== $response->getStatusCode()) {
            throw new RuntimeException('FATAL ERROR: National Bank of Serbia currency page is not available currently.'); // @codeCoverageIgnore
        }

        return $response->getBody()->getContents(); // It's a small page - no need to stream it
    }

    private function getXMLContent(int $idPosition, string $htmlContent, string $rateName): StreamInterface
    {
        $client = $this->getGuzzleClient();

        $startPos = $idPosition + strlen('ExchangeRateListID=');

        $listId = substr($htmlContent, $startPos, 36); // UUID is 36 characters long

        $url = sprintf(
            '%s/Download?ExchangeRateListID=%s&exchangeRateListTypeID=3&ExchangeRateListTypeName=%s&Format=xml',
            self::SOURCE,
            $listId,
            $rateName
        );

        $response = $client->request('GET', $url);

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
}
