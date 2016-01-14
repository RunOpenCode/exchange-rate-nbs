<?php

namespace RunOpenCode\ExchangeRate\NationalBankOfSerbia\Source;

use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;
use RunOpenCode\ExchangeRate\Contract\RateInterface;
use RunOpenCode\ExchangeRate\Contract\SourceInterface;
use RunOpenCode\ExchangeRate\Exception\SourceNotAvailableException;
use RunOpenCode\ExchangeRate\Log\LoggerAwareTrait;
use RunOpenCode\ExchangeRate\NationalBankOfSerbia\Api;
use RunOpenCode\ExchangeRate\NationalBankOfSerbia\Util\Browser;
use RunOpenCode\ExchangeRate\NationalBankOfSerbia\Parser\XmlParser;
use RunOpenCode\ExchangeRate\Utils\CurrencyCodeUtil;
use Symfony\Component\DomCrawler\Crawler;

final class WebPageSource implements SourceInterface
{
    use LoggerAwareTrait;

    const SOURCE = 'http://www.nbs.rs/kursnaListaModul/naZeljeniDan.faces';

    /**
     * @var array
     */
    private $cache;

    /**
     * @var Browser
     */
    private $browser;

    public function __construct()
    {
        $this->browser = new Browser();
        $this->cache = array();
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return Api::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function fetch($currencyCode, $rateType = 'default', \DateTime $date = null)
    {
        $currencyCode = CurrencyCodeUtil::clean($currencyCode);

        if (!Api::supports($currencyCode, $rateType)) {
            throw new \RuntimeException(sprintf('National Bank of Serbia does not supports currency code "%s" for rate type "%s".', $currencyCode, $rateType));
        }

        if ($date === null) {
            $date = new \DateTime('now');
        }

        if (!array_key_exists($rateType, $this->cache)) {

            try {

                $this->load($date, $rateType);

            } catch (\Exception $e) {
                $message = sprintf('Unable to load data from "%s" for "%s" of rate type "%s".', $this->getName(), $currencyCode, $rateType);

                $this->getLogger()->emergency($message);;
                throw new SourceNotAvailableException($message, 0, $e);
            }
        }

        if (array_key_exists($currencyCode, $this->cache[$rateType])) {
            return $this->cache[$rateType][$currencyCode];
        }

        $message = sprintf('API Changed: source "%s" does not provide currency code "%s" for rate type "%s".', $this->getName(), $currencyCode, $rateType);
        $this->getLogger()->critical($message);
        throw new \RuntimeException($message);
    }

    /**
     * Load rates from National Bank of Serbia website.
     *
     * @param \DateTime $date
     * @return RateInterface[]
     * @throws SourceNotAvailableException
     */
    private function load(\DateTime $date, $rateType)
    {
        $parser = new XmlParser();
        $parser->parse($this->getXmlDocument($date, $rateType), \Closure::bind(function($rates) {
            /**
             * @var RateInterface $rate
             */
            foreach ($rates as $rate) {

                if (!array_key_exists($rate->getRateType(), $this->cache)) {
                    $this->cache[$rate->getRateType()] = array();
                }

                $this->cache[$rate->getRateType()][$rate->getCurrencyCode()] = $rate;
            }

        }, $this));
    }

    /**
     * Get XML document with rates.
     *
     * @param \DateTime $date
     * @param string $rateType
     * @return StreamInterface
     */
    private function getXmlDocument(\DateTime $date, $rateType)
    {
        return $this->browser->request(self::SOURCE, 'POST', array(), array(
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
     * Get NBS's form CSRF token.
     *
     * @return string CSRF token.
     *
     * @throws \RuntimeException When API is changed.
     */
    private function getFormCsrfToken()
    {
        $crawler = new Crawler($this->browser->request(self::SOURCE, 'GET')->getContents());

        $hiddens = $crawler->filter('input[type="hidden"]');

        /**
         * @var \DOMElement $hidden
         */
        foreach ($hiddens as $hidden) {

            if ($hidden->getAttribute('id') === 'com.sun.faces.VIEW') {
                return $hidden->getAttribute('value');
            }
        }

        $message = 'FATAL ERROR: National Bank of Serbia changed it\'s API, unable to extract token.';
        $this->getLogger()->emergency($message);
        throw new \RuntimeException($message);
    }
}
