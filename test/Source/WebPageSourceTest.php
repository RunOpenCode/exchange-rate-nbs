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
namespace RunOpenCode\ExchangeRate\NationalBankOfSerbia\Tests\Source;

use GuzzleHttp\Psr7\Stream;
use RunOpenCode\ExchangeRate\NationalBankOfSerbia\Source\WebPageSource;
use RunOpenCode\ExchangeRate\NationalBankOfSerbia\Util\NbsBrowser;

class WebPageSourceTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function fetchMedian()
    {
        $rate = $this->mockSource('default')->fetch('EUR', 'default');
        $this->assertSame(121.6261, $rate->getValue());
    }

    /**
     * @test
     */
    public function fetchForeignCash()
    {
        $rate = $this->mockSource('foreign_cash_buying')->fetch('EUR', 'foreign_cash_buying');
        $this->assertSame(120.7747, $rate->getValue());
    }

    /**
     * @test
     */
    public function fetchForeignExchange()
    {
        $rate = $this->mockSource('foreign_exchange_buying')->fetch('EUR', 'foreign_exchange_buying');
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
            case 'default':
                $stream = new Stream(fopen(__DIR__ . '/../Fixtures/median.xml', 'r'));
                break;
            case 'foreign_cash_buying':         // FALL TROUGH
            case 'foreign_cash_selling':
                $stream = new Stream(fopen(__DIR__ . '/../Fixtures/foreign_cash.xml', 'r'));
                break;
            case 'foreign_exchange_buying':     // FALL TROUGH
            case 'foreign_exchange_selling':
                $stream = new Stream(fopen(__DIR__ . '/../Fixtures/foreign_exchange.xml', 'r'));
                break;
        }

        $stub = $this->getMockBuilder(NbsBrowser::class)->getMock();
        $stub->method('getXmlDocument')->willReturn($stream);

        return new WebPageSource($stub);
    }
}
