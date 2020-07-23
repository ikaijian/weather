<?php

namespace Jeesonjian\Weather\Tests;


use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response;
use Jeesonjian\Weather\Exceptions\HttpException;
use Jeesonjian\Weather\Exceptions\InvalidArgumentException;
use Jeesonjian\Weather\Weather;
use PHPUnit\Framework\TestCase;
use Mockery\Matcher\AnyArgs;

class testGetWeather extends TestCase
{

    public function testGetWeather()
    {
        //json
        $response = new Response(200,[],'{"success": true}');
        $client = \Mockery::mock(Client::class);

        $client->allows()->get('https://restapi.amap.com/v3/weather/weatherInfo', [
            'query' => [
                'key' => 'mock-key',
                'city' => '深圳',
                'output' => 'json',
                'extensions' => 'base',
            ]
        ])->andReturn($response);
        $w = \Mockery::mock(Weather::class,['mock-key'])->makePartial();
        $w->allows()->getHttpClient()->andReturn($client);

        $this->assertSame(['success' => true], $w->getWeather('深圳'));

        // xml
        $response = new Response(200, [], '<hello>content</hello>');
        $client = \Mockery::mock(Client::class);
        $client->allows()->get('https://restapi.amap.com/v3/weather/weatherInfo', [
            'query' => [
                'key' => 'mock-key',
                'city' => '深圳',
                'extensions' => 'all',
                'output' => 'xml',
            ],
        ])->andReturn($response);

        $w = \Mockery::mock(Weather::class, ['mock-key'])->makePartial();
        $w->allows()->getHttpClient()->andReturn($client);

        $this->assertSame('<hello>content</hello>', $w->getWeather('深圳', 'all', 'xml'));
    }

    public function testGetHttpClient()
    {
        $w = new Weather('mock-key');
        // 断言返回结果为 GuzzleHttp\ClientInterface 实例
        $this->assertInstanceOf(ClientInterface::class,$w->getHttpClient());
    }

    public function testSetGuzzleOptions()
    {
        $w = new Weather('mock-key');

        // 设置参数前，timeout 为 null
        $this->assertNull($w->getHttpClient()->getConfig('timeout'));

        // 设置参数
        $w->setGuzzleOptions(['timeout' => 5000]);

        // 设置参数后，timeout 为 5000
        $this->assertSame(5000, $w->getHttpClient()->getConfig('timeout'));
    }

    public function testGetWeatherWithInvalidType()
    {
        $w = new Weather('mock_key');
        // 断言会抛出此异常类
        $this->expectException(InvalidArgumentException::class);
        // 断言异常消息为 'Invalid type value(base/all): foo'
        $this->expectExceptionMessage('Invalid type value(base/all): foo');

        $w->getWeather('广州', 'foo');
        $this->fail('Failed to assert getWeather throw exception with invalid argument.');
    }

    // 检查 $format 参数
    public function testGetWeatherWithInvalidFormat()
    {
        $w = new Weather('mock-key');
        // 断言会抛出此异常类
        $this->expectException(InvalidArgumentException::class);

        // 断言异常消息为 'Invalid response format: array'
        $this->expectExceptionMessage('Invalid response format: array');

        // 因为支持的格式为 xml/json，所以传入 array 会抛出异常
        $w->getWeather('深圳', 'base', 'array');

        // 如果没有抛出异常，就会运行到这行，标记当前测试没成功
        $this->fail('Failed to assert getWeather throw exception with invalid argument.');
    }

    public function testGetWeatherWithGuzzleRuntimeException()
    {
        $client = \Mockery::mock(Client::class);
        $client->allows()
            ->get(new AnyArgs())
            ->andThrow(new \Exception('request timeout')); // 当调用 get 方法时会抛出异常。;

        $w = \Mockery::mock(Weather::class, ['mock-key'])->makePartial();
        $w->allows()->getHttpClient()->andReturn($client);

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('request timeout');
        $w->getWeather('深圳','base','json');
    }

    public function testGetLiveWeather(){
        $w= \Mockery::mock(Weather::class,['mock-key'])->makePartial();
        $w->expects()->getWeather('广州','base','json')->andReturn(['success'=>true]);
        $this->assertSame(['success'=>true],$w->getLiveWeather('深圳','json'));
    }

    public function testGetForecastsWeather()
    {
        // 将 getWeather 接口模拟为返回固定内容，以测试参数传递是否正确
        $w = \Mockery::mock(Weather::class, ['mock-key'])->makePartial();
        $w->expects()->getWeather('广州', 'all', 'json')->andReturn(['success' => true]);

        // 断言正确传参并返回
        $this->assertSame(['success' => true], $w->getForecastsWeather('广州','json'));
    }
}