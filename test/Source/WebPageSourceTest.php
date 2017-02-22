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
namespace RunOpenCode\ExchangeRate\NationalBankOfSerbia\Tests\Source;

use GuzzleHttp\Psr7\Stream;
use PHPUnit\Framework\TestCase;
use RunOpenCode\ExchangeRate\NationalBankOfSerbia\Enum\RateType;
use RunOpenCode\ExchangeRate\NationalBankOfSerbia\Source\WebPageSource;
use RunOpenCode\ExchangeRate\NationalBankOfSerbia\Util\NbsBrowser;

class WebPageSourceTest extends TestCase
{
    /**
     * @test
     */
    public function fetchMedian()
    {
        $rate = $this->mockSource(RateType::MEDIAN)->fetch('EUR', RateType::MEDIAN);
        $this->assertSame(121.6261, $rate->getValue());
    }

    /**
     * @test
     */
    public function fetchForeignCash()
    {
        $rate = $this->mockSource(RateType::FOREIGN_CASH_BUYING)->fetch('EUR', RateType::FOREIGN_CASH_BUYING);
        $this->assertSame(120.7747, $rate->getValue());
    }

    /**
     * @test
     */
    public function fetchForeignExchange()
    {
        $rate = $this->mockSource(RateType::FOREIGN_EXCHANGE_BUYING)->fetch('EUR', RateType::FOREIGN_EXCHANGE_BUYING);
        $this->assertSame(121.2612, $rate->getValue());
    }

    /**
     * @test
     *
     * @expectedException \RuntimeException
     */
    public function unsupported()
    {
        $source = new WebPageSource(new NbsBrowser());
        $source->fetch('EUR', 'not_supported');
    }

    /**
     * Mock source.
     *
     * @param string $rateType
     * @return WebPageSource
     */
    protected function mockSource($rateType)
    {
        switch ($rateType) {
            case RateType::MEDIAN:
                $stream = new Stream(fopen(__DIR__ . '/../Fixtures/median.xml', 'rb'));
                break;
            case RateType::FOREIGN_CASH_BUYING:         // FALL TROUGH
            case RateType::FOREIGN_CASH_SELLING:
                $stream = new Stream(fopen(__DIR__ . '/../Fixtures/foreign_cash.xml', 'rb'));
                break;
            case RateType::FOREIGN_EXCHANGE_BUYING:     // FALL TROUGH
            case RateType::FOREIGN_EXCHANGE_SELLING:
                $stream = new Stream(fopen(__DIR__ . '/../Fixtures/foreign_exchange.xml', 'rb'));
                break;
        }

        $stub = $this->getMockBuilder(NbsBrowser::class)->getMock();
        $stub->method('getXmlDocument')->willReturn($stream);

        return new WebPageSource($stub);
    }
}
