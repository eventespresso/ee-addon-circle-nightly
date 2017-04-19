<?php

namespace EventEspresso\CLI\Tools;

use GuzzleHttp\Client;
use Monolog\Logger;
use Github\Client as GithubClient;
use EventEspresso\CLI\Tools\Config;
use UnexpectedValueException;

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
     * @var Config;
     */
    private $config;


    /**
     * Runner constructor.
     *
     * @param \EventEspresso\CLI\Tools\Config $config
     * @param \GuzzleHttp\Client              $client
     * @param \Monolog\Logger                 $logger
     */
    public function __construct(Config $config, Client $http_client, GithubClient $github_client, Logger $logger)
    {
        $this->config              = $config;
        $this->http                = $http_client;
        $this->logger              = $logger;
        $this->github              = $github_client;
        $this->latest_release_core = $this->getLatestTagFromCore();
    }


    public function triggerNightlies()
    {
        //we do a nightly for file path in the given build machine path that has an info.json file where github is true
        //and nightly is true.
        foreach ($this->config->projectsToNotify() as $project) {
            $this->triggerNightly($project, 'master');
            $this->triggerNightly($project, $this->latest_release_core);
        }
    }



    private function triggerNightly($project, $branch)
    {
        $build_url = 'https://circleci.com/api/v1.1/project/github/'
                     . $project
                     . '/tree/master?circle-token='
                     . $this->config->circleToken();
        $response = $this->http->request(
            'POST',
            $build_url,
            array(
                'json' => array('RELEASE_BUILD' => $branch)
            )
        );
        if ($response->getStatusCode() !== 200) {
            $this->logger->warning($response->getBody());
            return;
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

