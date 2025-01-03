<?php

namespace v5;

use Monolog\Logger;
use Unicity\Core;

class Utilities
{
    protected static $affiliation = null;
    protected static $logger = null;
    protected static $redisCache = null;
    protected static $s3Client = null;

    #region Initializers

    public static function initLeapDataSources(): void
    {
        $databases = include(BASEPATH . 'configs/databases.php');

        foreach ($databases as $database) {
            \Leap\Core\DB\DataSource::instance($database);
        }

        $drivers = ['pdo' => '', 'odbc' => 'pdo']; // $driver: 'pdo' = export.php, 'odbc' = export2.php
        $spokes = Utilities::getInfoTraxSpokeIds();

        foreach ($drivers as $driver => $suffix) {
            foreach ($spokes as $spoke) {
                \Leap\Core\DB\DataSource::instance([
                    'id' => 'Infotrax' . $spoke . $suffix,
                    'type' => 'SQL',
                    'dialect' => 'ACUCOBOL',
                    'driver' => 'XDBC',
                    'connection' => [
                        'database' => '/v2' . (Utilities::isTestMode() ? '-test/' : '/') . "sql/query?spoke={$spoke}&driver={$driver}",
                        'hostname' => 'infotrax-svc' . '=' . Core\Env::get('SERVICE_PROXY_PROTOCOL') . '://' . Core\Env::get('SERVICE_PROXY_ADDRESS'),
                        'password' => '',
                        'persistent' => false,
                        'port' => Core\Env::get('SERVICE_PROXY_EGRESS_PORT'),
                        'role' => '',
                        'username' => '',
                    ],
                    'caching' => false,
                    'charset' => 'utf8',
                ]);
            }
        }
    }

    #endregion

    #region Market Helpers

    private static function getCountry($country): object
    {
        return Service\Countries::about($country)->value();
    }

    public static function getCountryAlpha2($country, $uppercase = true): string
    {
        $alpha2 = Utilities::getCountry($country)->alpha2 ?? '';

        return ($uppercase) ? strtoupper($alpha2) : strtolower($alpha2);
    }

    public static function getCountryAlpha3($country, $uppercase = true): string
    {
        $alpha3 = Utilities::getCountry($country)->alpha3 ?? '';

        return ($uppercase) ? strtoupper($alpha3) : strtolower($alpha3);
    }

    public static function getInfoTraxDatabaseId($country): string
    {
        return 'Infotrax' . Utilities::getInfoTraxSpokeId($country); // 'Infotrax' . $spoke . 'pdo'
    }

    public static function getInfoTraxSpokeId($country)
    {
        $spoke = Service\Spokes::about($country)->value()->spoke;
        if (empty($spoke)) {
            throw new Throwable\UnsupportedMarket($country);
        }

        return $spoke;
    }

    public static function getInfoTraxSpokeIds()
    {
        return Service\BusinessRule::about('spokes_in_use')->items();
    }

    public static function getLanguageCode(string $market, ?string $shipToAddress_country = null)
    {
        return Service\Languages::factory()->query($market, $shipToAddress_country);
    }

    public static function getLocale(string $market, ?string $shipToAddress_country = null)
    {
        try {
            if (HTTP_ACCEPT_LANGUAGE !== null) {
                $http_accept_languages = explode(',', HTTP_ACCEPT_LANGUAGE);
                if (isset($http_accept_languages[0])) {
                    $http_accept_language = explode(';', $http_accept_languages[0]);
                    if (isset($http_accept_language[0])) {
                        if (strpos($http_accept_language[0], '-') < 0) {
                            return $http_accept_language[0] . '-' . Utilities::getCountryAlpha2($market);
                        }

                        return $http_accept_language[0];
                    }
                }
            }

            return Utilities::getLanguageCode($market, $shipToAddress_country) . '-' . Utilities::getCountryAlpha2($market);
        } catch (\Throwable $ex) {
            return 'en-US';
        }
    }

    public static function isDestinationCountry($market): bool
    {
        return is_string($market) && Service\BusinessRule::about('markets_allowing_orders')->matches(Utilities::getCountryAlpha2($market));
    }

    public static function isInfoTraxMarket($market): bool
    {
        return (false
            || Utilities::isInfoTraxMarket_($market)
            || (Utilities::isInfoTraxMarket_('US') && Utilities::isMarketMappingToUS($market))
        );
    }

    public static function isInfoTraxMarket_($market): bool
    {
        return is_string($market) && Service\BusinessRule::about('markets_in_infotrax')->matches(Utilities::getCountryAlpha2($market));
    }

    public static function isMarketMappingToUS($market): bool
    {
        return is_string($market) && Service\BusinessRule::about('markets_mapping_to_us')->matches(Utilities::getCountryAlpha2($market));
    }

    public static function isUnicityMarket($market): bool
    {
        return Utilities::isInfoTraxMarket($market);
    }

    #endregion

    #region Environment/Mode

    public static function getAffiliation(): ?string
    {
        if (static::$affiliation !== null) {
            return static::$affiliation;
        }

        return X_AFFILIATION;
    }

    public static function getBaseCref(): string
    {
        if (Utilities::isProductionEnvironment()) {
            return '';
        }
        if (Utilities::isDevelopmentEnvironment()) {
            return 'qa.';
        }

        return Utilities::getEnvironment() . '.';
    }

    public static function getBaseHref(): string
    {
        return implode('/', [Utilities::getBaseUrl(), Utilities::getVersionAndMode()]);
    }

    public static function getBaseUrl(): string
    {
        return Core\Env::get('BASE_URL');
    }

    public static function getDomainUrl(): string
    {
        return static::getBaseUrl();
    }

    public static function getEnvironment(): string
    {
        return Core\Env::get('HYDRA_ENV');
    }

    public static function getEnvironmentLongName(): string
    {
        switch (Utilities::getEnvironment()) {
            case 'dev':
                return 'local';
            case 'qa':
                return 'qa';
            case 'stg':
                return 'staging';
            case 'test':
                return 'testing';
            default:
                return 'production';
        }
    }

    public static function getExecutionEnvironment(): string
    {
        return implode('.', [Utilities::getVersion(), Utilities::getEnvironmentLongName()]);
    }

    public static function getMode(): string
    {
        if (defined('CLI_MODE')) {
            return CLI_MODE;
        }
        if (preg_match('/\/v[0-9]a?-test\//', $_SERVER['REQUEST_URI'] ?? '')) {
            return 'test';
        }

        return 'live';
    }

    public static function getVersion(): string
    {
        if (defined('CLI_MODE')) {
            return 'v5a';
        }
        if (stripos($_SERVER['REQUEST_URI'] ?? '', '/v5a') === 0) {
            return 'v5a';
        }

        return 'v5';
    }

    public static function getVersionAndMode(): string
    {
        if (Utilities::isLiveMode()) {
            return Utilities::getVersion();
        }

        return implode('-', [Utilities::getVersion(), Utilities::getMode()]);
    }

    public static function getXReferer(): string
    {
        return static::getDomainUrl() . '/' . Utilities::getVersionAndMode();
    }

    public static function isDevelopmentEnvironment(): bool
    {
        return (Utilities::getEnvironment() === 'dev');
    }

    public static function isProductionEnvironment(): bool
    {
        return (Utilities::getEnvironment() === 'live');
    }

    public static function isQAEnvironment(): bool
    {
        return (Utilities::getEnvironment() === 'qa');
    }

    public static function isLiveMode(): bool
    {
        return (Utilities::getMode() === 'live');
    }

    public static function isTestMode(): bool
    {
        return (Utilities::getMode() === 'test');
    }

    public static function showErrors(): void
    {
        if (Utilities::isDevelopmentEnvironment()) {
            ini_set('display_errors', '1');
            ini_set('display_startup_errors', '1');
            error_reporting(-1);
        }
    }

    #endregion

    #region Service Handlers

    public static function getDbConnection($alias = 'default'): \Leap\Core\DB\Connection\Driver
    {
        return \Leap\Core\DB\Connection\Pool::instance()->get_connection(\Leap\Core\DB\DataSource::instance($alias));
    }

    /**
     * Returns the logger instance or creates one.
     *
     * @return Logger The logger instance.
     */
    public static function getLogger(): Logger
    {
        if (is_null(static::$logger)) {
            static::$logger = new Logger(TRANSACTIONUUID . '.v' . RELEASE . '/' . FEATURES . '.hydra.hydra.unicity.net');

            $stream = (defined('PHPUNIT_RUNNING') && PHPUNIT_RUNNING) ? '/tmp/local-testing.txt' : 'php://stdout';

            // default to INFO logging in non-dev enviroments
            $logLevel = static::isDevelopmentEnvironment() ? Logger::DEBUG : Logger::INFO;

            $handler = new \Monolog\Handler\StreamHandler($stream, $logLevel);
            $streamHandler = new \Monolog\Handler\BufferHandler($handler, $logLevel);
            $formatter = new \v5\Monolog\Formatter\MaskingJsonFormatter(\Monolog\Formatter\JsonFormatter::BATCH_MODE_JSON, true);

            $handler->setFormatter($formatter);
            static::$logger->pushHandler($streamHandler);
            \Leap\Core\DB\SQL::setLogger(static::$logger);
        }

        return static::$logger;
    }

    public static function getRedisCache(): \Predis\Client
    {
        if (Utilities::$redisCache === null) {
            Utilities::$redisCache = new \Predis\Client('tcp://' . Core\Env::get('SERVICE_KVS_ADDRESS') . ':' . Core\Env::get('SERVICE_KVS_PORT'));
        }

        return Utilities::$redisCache;
    }

    public static function getS3Client(): \Aws\S3\S3Client
    {
        if (static::$s3Client === null) {
            static::$s3Client = \Aws\S3\S3Client::factory(Service\Connection::getS3Credentials());
        }

        return static::$s3Client;
    }

    #endregion

    #region Caching Helpers

    public static function doResetCache($data = true): bool
    {
        return (($data === null) || ($data === false) || (isset($_GET['__resetCaches']) && ($_GET['__resetCaches'] == 1)));
    }

    public static function doResetFlags($data = true): bool
    {
        return (($data === null) || ($data === false) || ($data === '') || (isset($_GET['__resetFlags']) && ($_GET['__resetFlags'] == 1)));
    }

    public static function createCacheKey($args, $suffix = ''): string
    {
        if (is_array($suffix)) {
            $suffix = implode('.', $suffix);
        }

        return rtrim(implode('.', [
            Utilities::getMode(),
            Utilities::getEnvironment(),
            sha1(serialize($args)),
            Core\Convert::toString($suffix),
        ]), '.');
    }

    public static function cacheWrapper($cacheKeyArgs, $callback, $lifeTime = 0, $serialize = null, $deserialize = null)
    {
        $cacheClient = Utilities::getRedisCache();
        $cacheKey = Utilities::createCacheKey($cacheKeyArgs);
        $result = $cacheClient->get($cacheKey);
        if (Utilities::doResetCache($result)) {
            $result = $callback();
            $cacheClient->setex($cacheKey, $lifeTime, is_callable($serialize) ? $serialize($result) : json_encode($result));
        } else {
            if (is_callable($deserialize)) {
                $result = $deserialize($result);
            } else {
                $result = !is_null($result) ? json_decode($result) : null;
            }
        }

        return $result;
    }

    #endregion

    #region Helpers

    public static function getIPAddress($default = ''): string
    {
        $ipv4 = isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? static::getIPv4Address($_SERVER['HTTP_X_FORWARDED_FOR']) : null;
        if ($ipv4 !== null) {
            return $ipv4;
        }
        $ipv4 = isset($_SERVER['REMOTE_ADDR']) ? static::getIPv4Address($_SERVER['REMOTE_ADDR']) : null;
        if ($ipv4 !== null) {
            return $ipv4;
        }

        return $default;
    }

    protected static function getIPv4Address($address): ?string
    {
        $result = preg_match('/([0-9]+\.[0-9]+\.[0-9]+\.[0-9]+)/', $address, $matches);

        return ($result === 1 && count($matches) > 1) ? $matches[1] : null;
    }

    public static function getUserAgent($default = ''): string
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? $default;
    }

    #endregion

    public static function convertArrayToCSV(array $array)
    {
        $csv = new \Leap\Core\Data\Serialization\CSV([
            'default_headers' => true,
            'delimiter' => '|',
        ]);

        foreach ($array as $record) {
            $csv->add_row((array) $record);
        }

        return $csv;
    }

}
