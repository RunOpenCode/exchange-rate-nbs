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
namespace RunOpenCode\ExchangeRate\NationalBankOfSerbia\Tests\Parser;

use RunOpenCode\ExchangeRate\Contract\RateInterface;
use RunOpenCode\ExchangeRate\NationalBankOfSerbia\Parser\XmlParser;
use RunOpenCode\Sax\SaxParser;

class XmlParserTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function median()
    {
        $this->assertParsing(__DIR__ . '/../Fixtures/median.xml', __DIR__ . '/../Fixtures/median_out.php');
    }

    /**
     * @test
     */
    public function foreignCash()
    {
        $this->assertParsing(__DIR__ . '/../Fixtures/foreign_cash.xml', __DIR__ . '/../Fixtures/foreign_cash_out.php');
    }

    /**
     * @test
     */
    public function foreignExchange()
    {
        $this->assertParsing(__DIR__ . '/../Fixtures/foreign_exchange.xml', __DIR__ . '/../Fixtures/foreign_exchange_out.php');
    }

    protected function assertParsing($pathToXmlInput, $pathToPhpOutput, $message = 'Should provide given rates.') {

        $rates = null;

        SaxParser::factory()->parse(new XmlParser(), fopen($pathToXmlInput, 'r'), function($result) use (&$rates) {
            $rates = $result;
        });

        $this->assertSame(require_once $pathToPhpOutput, call_user_func(function($rates) {
            $flatten = array();
            /**
             * @var RateInterface $rate
             */
            foreach ($rates as $rate) {
                $flatten[] = array(
                    'currencyCode' => $rate->getCurrencyCode(),
                    'rateType' => $rate->getRateType(),
                    'value' => (string)$rate->getValue(),
                    'date' => $rate->getDate()->format('Y-m-d')
                );
            }

            return $flatten;
        }, $rates), $message);
    }
}
