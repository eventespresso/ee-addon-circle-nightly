# ee-addon-circle-nightly script

PHP script for triggering nightly builds of add-on unit tests on circle-ci.com

## What it does.

If you are using the circleci.com service, they have facility for triggering builds via their api with specific parameters.

This script was built for the EventEspresso team to enable us to trigger nightly builds of all our official add-ons for our [core product](https:/github.com/eventespresso/event-espresso-core).  

The script (along with a corresponding circle.yml file in each of our add-ons) will trigger circle to:

1. Run a phpunit test build against the `master` branch of Event Espresso core.
2. Run a phpunit test build against the *latest release tag* of Event Espresso core.

Since our core plugin receives much more activity than our add-ons, its important that we regularly test our add-ons against the core plugin to ensure there isn't any unexpected breakage due to changes in core.

## Setup

### 1. Clone to a directory on the server you want to use for triggering builds.
### 2. Make sure redis and phpredis is installed on the server.
Calls to github are cached when there are no changes. This just prevents any potential rate-limiting from kicking in.
With a lot of add-ons getting triggered this could be a big deal so caching helps. 

### 3. Use composer to install

```bash
composer install
```

### 4. Setup `src.json` for private creds.

You need at a minimum the following in the json file:

```json
{
  "projects_to_notify" : [
    "eventespresso/eea-people-addon"
  ],
  "projects_to_notify_travis" : [
    "eventespresso/eea-people-addon"
  ],
  "redis_password" : "password_for_redis",
  "github_token" : "personal_github_api_token",
  "github_user" : "organization_username_for_core_plugin",
  "github_main_project" : "slug-for-core-plugin-repository",
  "circle_token" : "api-token-for-circle",
  "travis_token" : "api-token-for-travis"
}
```

| item | description |
|----- |--------------|
projects_to_notify | an array of github `repository_user/repository_slug` references for add-ons.  Tests are triggered on circleci.com
projects_to_notify_travis | an array of github `repository_user/repository_slug` references for add-ons. Tests are triggered on travis-ci.org
redis_password | If your redis install is authed, add the redis password here. If there is no auth leave blank.
github_token | Add your github api token if your core plugin is a private repository.
github_user | This should be the organization/user part where the main plugin resides on github.
github_main_project | This should be the slug for the main plugin on github.
circle_token | The api token for circle.

### 5. Test directly

From the command line in the directory hosting this package:
```bash
php runner.php
```

### 6. Setup cron job.

Set up a cron job on your server for your php user to execute the `runner.php` file on a schedule of your choosing.


## What's next?

- eventually will trigger nightly acceptance test builds for add-ons and our core product.

