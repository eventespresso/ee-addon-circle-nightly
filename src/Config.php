<?php

namespace EventEspresso\CLI\Tools;

use InvalidArgumentException;
use LogicException;

/**
 * Config
 * Receives a json file and sets up a simple config object from its contents.
 *
 * @package EventEspresso\CLI\Tools
 * @subpackage
 * @author  Darren Ethier
 * @since   1.0.0
 */
class Config
{

    /**
     * An array of project slugs that get notified.  This should correspond to the slug for the github project.
     *
     * @var array
     */
    private $projects_to_notify = array();


    /**
     * The host for redis.
     *
     * @var string
     */
    private $redis_host = '127.0.0.1';


    /**
     * The port for redis
     *
     * @var int
     */
    private $redis_port = 6379;


    /**
     * The password for the redis connection
     *
     * @var string
     */
    private $redis_password = '';


    /**
     * This is the auth token used to authenticate with github.
     *
     * @var string
     */
    private $github_token = '';


    /**
     * The github user/organization
     *
     * @var string
     */
    private $github_user = '';


    /**
     * This is the main project that the add-ons are tested against (usually the "core" plugin that add-ons are for)
     *
     * @var string
     */
    private $github_main_project = '';


    /**
     * Authentication token for circle
     *
     * @var string
     */
    private $circle_token = '';


    /**
     * Config constructor.
     *
     * @param $options_file
     */
    public function __construct($options_file)
    {
        $this->setUp($options_file);
    }


    /**
     * @param $options_file
     */
    private function setUp($options_file)
    {
        //if the file isn't readable then get out.
        if (! is_readable($options_file)) {
            throw new InvalidArgumentException(sprintf('Unable to read the given file %s.', $options_file));
        }

        $decoded = json_decode(file_get_contents($options_file, true));
        if ($decoded === null) {
            throw new LogicException(sprintf('The contents of the file (%s) is not valid json.', $options_file));
        }

        foreach ($decoded as $key => $value) {
            if (property_exists($this, $key) && ! empty($value)) {
                $this->$key = $value;
            }
        }
    }


    /**
     * @return array
     */
    public function projectsToNotify()
    {
        return $this->projects_to_notify;
    }


    /**
     * @return string
     */
    public function redisHost()
    {
        return $this->redis_host;
    }


    /**
     * @return int
     */
    public function redisPort()
    {
        return $this->redis_port;
    }


    /**
     * @return string
     */
    public function redisPassword()
    {
        return $this->redis_password;
    }


    /**
     * @return string
     */
    public function githubAuthToken()
    {
        return $this->github_token;
    }


    /**
     * @return string
     */
    public function githubUser()
    {
        return $this->github_user;
    }


    /**
     * @return string
     */
    public function githubMainProject()
    {
        return $this->github_main_project;
    }


    /**
     * @return string
     */
    public function circleToken()
    {
        return $this->circle_token;
    }
}