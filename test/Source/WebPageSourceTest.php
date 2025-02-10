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
use RunOpenCode\ExchangeRate\NationalBankOfSerbia\Api;
use RunOpenCode\ExchangeRate\NationalBankOfSerbia\Enum\RateType;
use RunOpenCode\ExchangeRate\NationalBankOfSerbia\Source\WebPageSource;
use RunOpenCode\ExchangeRate\NationalBankOfSerbia\Util\NbsBrowser;

/**
 * Class WebPageSourceTest
 *
 * @package RunOpenCode\ExchangeRate\NationalBankOfSerbia\Tests\Source
 */
class WebPageSourceTest extends TestCase
{
    /**
     * @test
     *
     * @return void
     */
    public function name()
    {
        $this->assertEquals(Api::NAME, (new WebPageSource())->getName());
    }

    /**
     * @test
     *
     * @return void
     */
    public function fetchMedian()
    {
        $rate = $this->mockSource(RateType::MEDIAN)->fetch('EUR', RateType::MEDIAN);
        $this->assertSame(121.6261, $rate->getValue());
    }

    /**
     * @test
     *
     * @return void
     */
    public function fetchForeignCash()
    {
        $rate = $this->mockSource(RateType::FOREIGN_CASH_BUYING)->fetch('EUR', RateType::FOREIGN_CASH_BUYING);
        $this->assertSame(120.7747, $rate->getValue());
    }

    /**
     * @test
     *
     * @return void
     */
    public function fetchForeignExchange()
    {
        $rate = $this->mockSource(RateType::FOREIGN_EXCHANGE_BUYING)->fetch('EUR', RateType::FOREIGN_EXCHANGE_BUYING);
        $this->assertSame(121.2612, $rate->getValue());
    }

    /**
     * @test
     *
     * @return void
     *
     * @expectedException \RuntimeException
     */
    public function unsupported()
    {
        $source = new WebPageSource(new NbsBrowser());
        $source->fetch('EUR', 'not_supported');
    }

    /**
     * @test
     *
     * @return void
     * @expectedException \RunOpenCode\ExchangeRate\NationalBankOfSerbia\Exception\SourceNotAvailableException
     */
    public function down()
    {
        /** @var NbsBrowser&\PHPUnit_Framework_MockObject_MockObject $stub */
        $stub = $this->getMockBuilder(NbsBrowser::class)->getMock();

        $stub->method('getXmlDocument')->willThrowException(new \Exception());

        $source = new WebPageSource($stub);

        $source->fetch('EUR');
    }

    /**
     * @test
     *
     * @expectedException \RunOpenCode\ExchangeRate\NationalBankOfSerbia\Exception\RuntimeException
     * @expectedExceptionMessage API Changed: source "national_bank_of_serbia" does not provide rate type "median".
     *
     * @return void
     */
    public function apiChangedNoRateType()
    {
        /** @var NbsBrowser&\PHPUnit_Framework_MockObject_MockObject $stub */
        $stub = $this->getMockBuilder(NbsBrowser::class)->getMock();

        $stream = fopen('php://memory', 'r+b');
        fwrite($stream, '<root></root>');

        $stub->method('getXmlDocument')->willReturn(new Stream($stream));

        $source = new WebPageSource($stub);

        $source->fetch('EUR');
    }

    /**
     * @test
     *
     * @expectedException \RunOpenCode\ExchangeRate\NationalBankOfSerbia\Exception\RuntimeException
     * @expectedExceptionMessage API Changed: source "national_bank_of_serbia" does not provide currency code "EUR" for rate type "median".
     *
     * @return void
     */
    public function apiChangedNoCurrency()
    {
        /** @var NbsBrowser&\PHPUnit_Framework_MockObject_MockObject $stub */
        $stub = $this->getMockBuilder(NbsBrowser::class)->getMock();

        $resource = fopen('php://memory', 'r+b');
        fwrite($resource, '<root><Item><Code>203</Code><Country>Czech Republic</Country><Currency>CZK</Currency><Unit>1</Unit><Middle_Rate>4.4924</Middle_Rate></Item></root>');
        rewind($resource);

        $stub->method('getXmlDocument')->willReturn(new Stream($resource));

        $source = new WebPageSource($stub);

        $source->fetch('EUR');
    }

    /**
     * Mock source
     *
     */
    protected function mockSource(string $rateType): WebPageSource
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

        /** @var NbsBrowser&\PHPUnit_Framework_MockObject_MockObject $stub */
        $stub = $this->getMockBuilder(NbsBrowser::class)->getMock();
        $stub->method('getXmlDocument')->willReturn($stream); //@phpstan-ignore-line

        return new WebPageSource($stub);
    }
}
