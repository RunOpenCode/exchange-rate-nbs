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
namespace RunOpenCode\ExchangeRate\NationalBankOfSerbia;

use RunOpenCode\ExchangeRate\NationalBankOfSerbia\Enum\RateType;

/**
 * Class Api
 *
 * Api definition of National Bank of Serbia crawler.
 *
 * @package RunOpenCode\ExchangeRate\NationalBankOfSerbia
 */
final class Api
{
    /**
     * Unique name of source.
     */
    const NAME = 'national_bank_of_serbia';

    /**
     * Supported rate types and currency codes by National Bank of Serbia.
     *
     * NOTE: National Bank of Serbia still publishes rates of some of the obsolete currencies.
     *
     * @var array
     */
    private static $supports = array(
        RateType::DEFAULT => array('EUR', 'AUD', 'CAD', 'CNY', 'HRK', 'CZK', 'DKK', 'HUF', 'JPY', 'KWD', 'NOK', 'RUB', 'SEK', 'CHF',
                           'GBP', 'USD', 'BAM', 'PLN', 'ATS', 'BEF', 'FIM', 'FRF', 'DEM', 'GRD', 'IEP', 'ITL', 'LUF', 'PTE',
                           'ESP'),
        RateType::FOREIGN_CASH_BUYING => array('EUR', 'CHF', 'USD'),
        RateType::FOREIGN_CASH_SELLING => array('EUR', 'CHF', 'USD'),
        RateType::FOREIGN_EXCHANGE_BUYING => array('EUR', 'AUD', 'CAD', 'CNY', 'DKK', 'JPY', 'NOK', 'RUB', 'SEK', 'CHF', 'GBP', 'USD'),
        RateType::FOREIGN_EXCHANGE_SELLING => array('EUR', 'AUD', 'CAD', 'CNY', 'DKK', 'JPY', 'NOK', 'RUB', 'SEK', 'CHF', 'GBP', 'USD')
    );

    private function __construct()
    {
        // noop
    }

    /**
     * Check if National Bank of Serbia supports given exchange rate currency code for given rate type.
     *
     * @param string $currencyCode Currency code.
     * @param string $rateType Rate type.
     * @return bool TRUE if currency code within rate type is supported.
     */
    public static function supports($currencyCode, $rateType)
    {
        if (
            !array_key_exists($rateType, self::$supports)
            ||
            !in_array($currencyCode, self::$supports[$rateType], true)
        ) {
            return false;
        }

        return true;
    }
}
