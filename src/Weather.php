<?php


namespace Jeesonjian\Weather;


use GuzzleHttp\Client;
use Jeesonjian\Weather\Exceptions\InvalidArgumentException;
use Jeesonjian\Weather\Exceptions\HttpException;

class Weather
{

    protected $key;
    protected $guzzleOptions = [];

    public function __construct(string $key)
    {
        $this->key = $key;
    }

    /**
     * 返回guzzle 实例
     * @return Client
     */
    public function getHttpClient()
    {
        return new Client($this->guzzleOptions);
    }

    /**
     * 设置 guzzle 的参数配置，
     * @param array $options
     */
    public function setGuzzleOptions(array $options)
    {
        $this->guzzleOptions = $options;
    }

    public function getWeather($city, string $type, string $format)
    {
        $url = 'https://restapi.amap.com/v3/weather/weatherInfo';
        $types = [
            'live' => 'base',
            'forecast' => 'all',
        ];
        if (!\in_array(\strtolower($format),['xml', 'json'])) {
            throw new InvalidArgumentException('Invalid response format: '.$format);
        }
        if (!\in_array(\strtolower($type), $types)) {
            throw new InvalidArgumentException('Invalid type value(base/all): '.$type);
        }
        $query = array_filter([
            'key' => $this->key,
            'city' => $city,
            'output' => $format,
            'extensions' =>  $type,
        ]);
        try {
            $response = $this->getHttpClient()->get($url,[
                'query'=>$query
            ])->getBody()->getContents();
            return 'json' === $format ? \json_decode($response, true) : $response;
        }catch (\Exception $e){
            throw new HttpException($e->getMessage(),$e->getCode(),$e);
        };

    }

    /**
     * 获取实时天气
     * @param $city
     * @param string $format
     * @return mixed|string
     * @throws HttpException
     * @throws InvalidArgumentException
     */
    public function getLiveWeather($city,$format='json')
    {
        return $this->getWeather($city,'base',$format);
    }

    /**
     * 获取天气预报
     * @param $city
     * @param string $format
     * @return mixed|string
     * @throws HttpException
     * @throws InvalidArgumentException
     */
    public function getForecastsWeather($city, $format = 'json'){
        return $this->getWeather($city,'all',$format);
    }
}