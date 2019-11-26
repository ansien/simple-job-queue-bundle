# SimpleJobQueueBundle

[![Latest Version on Packagist](https://img.shields.io/packagist/v/ansien/simple-job-queue-bundle.svg?style=flat-square)](https://packagist.org/packages/ansien/simple-job-queue-bundle)
[![MIT Licensed](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Build Status](https://img.shields.io/travis/ansien/simple-job-queue-bundle/master.svg?style=flat-square)](https://travis-ci.org/ansien/simple-job-queue-bundle)
[![Total Downloads](https://img.shields.io/packagist/dt/ansien/simple-job-queue-bundle.svg?style=flat-square)](https://packagist.org/packages/ansien/simple-job-queue-bundle)

The bundle makes it very easy to run background jobs in your Symfony application, without the use of a message queue such as RabbitMQ.

This allows you to speed up your Symfony application by runnings heavy tasks or calculations in the background.

## Installation

You can install the package via composer:

```bash
composer require ansien/simple-job-queue-bundle
```
Make sure to create a migration or run the `php bin/console doctrine:schema:update --force` command to create the `sjqb_jops` table.

## Usage

### Creating a job
You can inject the `Ansien\SimpleJobQueueBundle\Service\SimpleJobService` service. This service allows you to easily create a job. You can also manually create and persist an instance of `Ansien\SimpleJobQueueBundle\Entity\Job`.
```php
$this->simpleJobService->createJob('app:test-command', [
    'foo' => 'bar'
    '--optional-foo' => 'bar'
]);
```

### Running pending jobs (development environment)
You can run all pending jobs while developing by using:
```bash
php bin/console simple-job-queue:run
```

### Running pending jobs (production environment)
In your production environment it is recommended to use Supervisor which allows you to run multiple jobs in parallel and automatically recover if a job crashes guaranteeing high availability.

Below is an example config which you can use:
```bash
[program:simple_job_queue]
command=php %kernel.root_dir%/console simple-job-queue:run --env=prod --verbose
process_name=%(program_name)s
numprocs=1
directory=/tmp
autostart=true
autorestart=true
startsecs=5
startretries=10
user=www-data
redirect_stderr=false
stdout_logfile=/var/log/simple_job_queue.out.log
stdout_capture_maxbytes=1MB
stderr_logfile=/var/log/simple_job_queue.error.log
stderr_capture_maxbytes=1MB
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Testing

``` bash
composer test
```

## Credits

This bundle is heavily inspired by the more popular [JMSJobQueueBundle](https://github.com/schmittjoh/JMSJobQueueBundle). The reason for this bundle being created is the lack of updates on that bundle and missing Symfony 5 support.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
