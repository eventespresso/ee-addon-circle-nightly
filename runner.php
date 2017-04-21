<?php

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use EventEspresso\CLI\Tools\Runner;
use EventEspresso\CLI\Tools\Config;
use GuzzleHttp\Client;
use Github\Client as GithubClient;
use Cache\Adapter\Redis\RedisCachePool;

require 'vendor/autoload.php';

$path = dirname(__FILE__) . '/';

$logger = new Logger('addon_nightly');
$logger->pushHandler(new StreamHandler('/var/log/nightlies/addon_nightly.log', Logger::WARNING));

if (! file_exists($path . 'src.json')) {
    $logger->error('Missing src.json.  This contains necessary file paths and configuration for the nightly builder to use.');
    exit;
}

try{
    //grab our config from json.
    $config = new Config($path . 'src.json');

    //setup redis caching.
    $redis = new Redis();
    $redis->connect($config->redisHost(), $config->redisPort());
    if ($config->redisPassword()) {
        $redis->auth($config->redisPassword());
    }
    $pool = new RedisCachePool($redis);
    $github_client = new GithubClient();
    $github_client->addCache($pool);

    //auth github
    $github_client->authenticate($config->githubAuthToken(), null, GithubClient::AUTH_HTTP_TOKEN);

    //instantiate runner class with dependencies.
    $runner = new Runner($config, new Client, $github_client, $logger);
    $runner->triggerNightlies();
} catch(Exception $e) {
    $logger->error($e->getMessage());
}

//byby
exit;