<?php

namespace EventEspresso\CLI\Tools;

use GuzzleHttp\Client;
use Monolog\Logger;
use Github\Client as GithubClient;
use UnexpectedValueException;

/**
 * Runner
 * This takes care of triggering builds.
 *
 * @package EventEspresso\CLI\Tools
 * @subpackage
 * @author  Darren Ethier
 * @since   1.0.0
 */
class Runner
{
    /**
     * Logger for any errors etc.
     *
     * @var Logger;
     */
    private $logger;


    /**
     * Http client for making requests.
     *
     * @var Client;
     */
    private $http;


    /**
     * @var GithubClient
     */
    private $github;



    /**
     * @var \EventEspresso\CLI\Tools\Config;
     */
    private $config;


    /**
     * Runner constructor.
     *
     * @param \EventEspresso\CLI\Tools\Config $config
     * @param \GuzzleHttp\Client              $http_client
     * @param \Github\Client                  $github_client
     * @param \Monolog\Logger                 $logger
     * @internal param \GuzzleHttp\Client $client
     */
    public function __construct(Config $config, Client $http_client, GithubClient $github_client, Logger $logger)
    {
        $this->config              = $config;
        $this->http                = $http_client;
        $this->logger              = $logger;
        $this->github              = $github_client;
        $this->latest_release_core = $this->getLatestTagFromCore();
    }


    /**
     * Client code calls this to trigger nightly builds for any projects setup for notification.
     * Every 3 loops (6 pings to travis) we sleep for 2 seconds so that we're not hitting any rate limits.
     */
    public function triggerNightlies()
    {
        $count = 0;
        //nightlies for circle
        if ($this->config->projectsToNotify()) {
            //we do a nightly for file path in the given build machine path that has an info.json file where github is true
            //and nightly is true.
            foreach ($this->config->projectsToNotify() as $project) {
                $this->triggerNightlyCircle($project, 'master');
                $this->triggerNightlyCircle($project, $this->latest_release_core);
                $count ++;
                if ($count%3 === 0) {
                    $count = 0;
                    sleep(3);
                }
            }
        }

        $count = 0;
        //nightlies for travis
        if ($this->config->projectsToNotifyTravis()) {
            foreach ($this->config->projectsToNotifyTravis() as $travis_project) {
                $this->triggerNightlyTravis($travis_project, 'master');
                $this->triggerNightlyTravis($travis_project, $this->latest_release_core);
                $count ++;
                if ($count%3 === 0) {
                    $count = 0;
                    sleep(3);
                }
            }
        }
    }


    /**
     * Executes notification to circle for each registered project.
     * @param string $project  Something like 'eventespresso/eea-people-addon'
     * @param string $branch   Something like 'master' or '4.9.27.p'
     */
    private function triggerNightlyCircle($project, $branch)
    {
        $build_url = 'https://circleci.com/api/v1.1/project/github/'
                     . $project
                     . '/tree/master?circle-token='
                     . $this->config->circleToken();
        $response = $this->http->request(
            'POST',
            $build_url,
            array(
                'json' => array(
                    'build_parameters' => array(
                        'RELEASE_BUILD' => $branch
                    )
                )
            )
        );
        if ($response->getStatusCode() !== "200" || $response->getStatusCode() !== "201") {
            $this->logger->warning($response->getBody());
            return;
        }
    }


    /**
     * Executes notification to travis given project
     *
     * @param string $project Something like 'eventespresso/eea-people-addon'
     * @param string $branch
     */
    private function triggerNightlyTravis($project, $branch)
    {
        $build_url = 'https://api.travis-ci.org/repo/' . urlencode($project) . '/requests';
        $response = $this->http->request(
            'POST',
            $build_url,
            array(
                'headers' => array(
                    'Travis-API-Version' => 3,
                    'Authorization' => 'token ' . $this->config->travisToken(),
                    'User-Agent' => 'Travis EventEspressoNightlies/1.0.0',
                    'Accept' => 'application/vnd.travis-ci.2+json',
                    'Content-Type' => 'application/json'
                ),
                'json' => array(
                    'request' => array(
                        'branch' => 'master',
                        'message' => 'Nightly Build against EE core ' . $branch,
                        'config' => array(
                            'merge_mode' => 'deep_merge',
                            'env' => array(
                                'global' => array(
                                    "EE_VERSION=$branch"
                                )
                            )
                        )
                    )
                )
            )
        );
        if ($response->getStatusCode() !== "200" || $response->getStatusCode() !== "201") {
            $this->logger->warning($response->getBody());
        }
    }


    /**
     * Uses the github api to retrieve the latest release tag for ee core.
     * @return string
     */
    private function getLatestTagFromCore()
    {
        $tags = $this->github->api('repo')->tags(
            $this->config->githubUser(),
            $this->config->githubMainProject()
        );

        //the latest tag should be the first element so let's grab that.
        $latest_tag = reset($tags);
        if (! isset($latest_tag['name'])) {
            throw new UnexpectedValueException(
                'Expected a `name` index on the response from the github tags endpoint. No `name` index exists.'
            );
        }
        return $latest_tag['name'];
    }

}

