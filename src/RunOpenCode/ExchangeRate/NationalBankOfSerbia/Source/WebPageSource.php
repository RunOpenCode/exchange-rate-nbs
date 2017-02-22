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
namespace RunOpenCode\ExchangeRate\NationalBankOfSerbia\Source;

use RunOpenCode\ExchangeRate\Contract\RateInterface;
use RunOpenCode\ExchangeRate\Contract\SourceInterface;
use RunOpenCode\ExchangeRate\Log\LoggerAwareTrait;
use RunOpenCode\ExchangeRate\NationalBankOfSerbia\Api;
use RunOpenCode\ExchangeRate\NationalBankOfSerbia\Exception\RuntimeException;
use RunOpenCode\ExchangeRate\NationalBankOfSerbia\Exception\SourceNotAvailableException;
use RunOpenCode\ExchangeRate\NationalBankOfSerbia\Util\NbsBrowser;
use RunOpenCode\ExchangeRate\NationalBankOfSerbia\Parser\XmlParser;
use RunOpenCode\ExchangeRate\Utils\CurrencyCodeUtil;

/**
 * Class WebPageSource
 *
 * Fetch rates from National Bank of Serbia website, as public user, without using their API service.
 *
 * @package RunOpenCode\ExchangeRate\NationalBankOfSerbia\Source
 */
final class WebPageSource implements SourceInterface
{
    use LoggerAwareTrait;

    /**
     * @var array
     */
    private $cache;

    /**
     * @var NbsBrowser
     */
    private $browser;

    public function __construct(NbsBrowser $browser = null)
    {
        $this->browser = ($browser !== null) ? $browser : new NbsBrowser();
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
            throw new RuntimeException(sprintf('National Bank of Serbia does not supports currency code "%s" for rate type "%s".', $currencyCode, $rateType));
        }

        if ($date === null) {
            $date = new \DateTime('now');
        }

        if (!array_key_exists($rateType, $this->cache)) {

            try {

                $this->load($date, $rateType);

            } catch (\Exception $e) {
                $message = sprintf('Unable to load data from "%s" for "%s" of rate type "%s".', $this->getName(), $currencyCode, $rateType);

                $this->getLogger()->emergency($message);
                throw new SourceNotAvailableException($message, 0, $e);
            }
        }

        if (array_key_exists($currencyCode, $this->cache[$rateType])) {
            return $this->cache[$rateType][$currencyCode];
        }

        $message = sprintf('API Changed: source "%s" does not provide currency code "%s" for rate type "%s".', $this->getName(), $currencyCode, $rateType);
        $this->getLogger()->critical($message);
        throw new RuntimeException($message);
    }

    /**
     * Load rates from National Bank of Serbia website.
     *
     * @param \DateTime $date
     * @param string $rateType
     * @throws SourceNotAvailableException
     */
    private function load(\DateTime $date, $rateType)
    {
        $parser = new XmlParser();

        /**
         * @var RateInterface[] $rates
         */
        $rates = $parser->parse($this->browser->getXmlDocument($date, $rateType));

        /**
         * @var RateInterface $rate
         */
        foreach ($rates as $rate) {

            if (!array_key_exists($rate->getRateType(), $this->cache)) {
                $this->cache[$rate->getRateType()] = array();
            }

            $this->cache[$rate->getRateType()][$rate->getCurrencyCode()] = $rate;
        }
    }
}
