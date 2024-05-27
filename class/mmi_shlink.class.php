<?php

dol_include_once('mmicrm/vendor/autoload.php');

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use Shlinkio\Shlink\SDK\Builder\ShlinkClientBuilder;
use Shlinkio\Shlink\SDK\ShortUrls\Model\ShortUrlIdentifier;
use Shlinkio\Shlink\SDK\Config\Exception\InvalidConfigException;
use Shlinkio\Shlink\SDK\Config\ShlinkConfig;

use Shlinkio\Shlink\SDK\Exception\InvalidDataException;
use Shlinkio\Shlink\SDK\ShortUrls\Exception\InvalidLongUrlException;
use Shlinkio\Shlink\SDK\ShortUrls\Exception\NonUniqueSlugException;
use Shlinkio\Shlink\SDK\ShortUrls\Model\ShortUrlCreation;

class mmi_shlink
{

protected static $config;
protected static $builder;
protected static $shortUrlsClient;

static function __init()
{
	try {
		static::$config = ShlinkConfig::fromArray([
			'baseUrl' => getDolGlobalString('MMICRM_SHLINK_URL'),
			'apiKey' => getDolGlobalString('MMICRM_SHLINK_KEY'),
			'version' => '3',
		]);
	} catch (InvalidConfigException $e) {
		var_dump($e); die();
		// Either 'baseUrl' or 'apiKey' props were missing in the array,
		// or 'version' prop has a value different from "2" or "3".
	}

	/////

	static::$builder = new ShlinkClientBuilder(
		new Client(), // Any object implementing PSR-18's Psr\Http\Client\ClientInterface
		new HttpFactory(), // Any object implementing PSR-17's Psr\Http\Message\RequestFactoryInterface
		new HttpFactory(), // Any object implementing PSR-17's Psr\Http\Message\StreamFactoryInterface
	);


	static::$shortUrlsClient = static::$builder->buildShortUrlsClient(static::$config);
	//$shortUrlsClient->deleteShortUrl(ShortUrlIdentifier::fromShortCode('bar'));

	/////

}

static function generate($link)
{

	try {
		$creation = ShortUrlCreation::forLongUrl($link);
			//->withCustomSlug('shlink')
			//->withMaxVisits(1000)
			//->validSince(new DateTimeImmutable('2022-05-30'));
		$shortUrl = static::$shortUrlsClient->createShortUrl($creation);

		// echo $shortUrl->shortUrl;
		// echo $shortUrl->longUrl;
	} catch (NonUniqueSlugException $e) {
		echo 'There is already a short URL using this custom slug';
		return;
	} catch (InvalidLongUrlException $e) {
		echo 'The long URL is not reachable';
		return;
	} catch (InvalidDataException $e) {
		echo 'Provided data is invalid';
		return;
	}

	return $shortUrl;
}

}

mmi_shlink::__init();
