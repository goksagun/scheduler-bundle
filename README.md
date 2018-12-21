Scheduler Bundle
================
Command scheduler allows you to fluently and expressively define your command
schedule within application itself. When using the scheduler, only a single
Cron entry is needed on your server. Your task schedule is defined in the
scheduler.yml file. When using the scheduler, you only need to add the
following Cron entry to your server:

```bash
* * * * * php /path-to-your-project/bin/console scheduler:run >> /dev/null 2>&1
```

This Cron will call the command scheduler every minute. When the `scheduler:run`
command is executed, application will evaluate your scheduled tasks and runs the tasks that are due.
If you want to run task(s) as asynchronously call the command scheduler with async flag `scheduler:run --async`.

Installation
============

Step 1: Download the Bundle
---------------------------

Open a command console, enter your project directory and execute the
following command to download the latest stable version of this bundle:

```console
$ composer require goksagun/scheduler-bundle
```

This command requires you to have Composer installed globally, as explained
in the [installation chapter](https://getcomposer.org/doc/00-intro.md)
of the Composer documentation.

Step 2: Enable the Bundle
-------------------------

Then, enable the bundle by adding it to the list of registered bundles
in the `app/AppKernel.php` file of your project:

```php
<?php
// app/AppKernel.php

// ...
class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = array(
            // ...
            new Goksagun\SchedulerBundle\SchedulerBundle(),
        );

        // ...
    }

    // ...
}
```

Step 3: Add the Bundle config file
----------------------------------

Then, add the bundle configuration yml file `scheduler.yml` into `app/config` directory:

```yml
scheduler:
    enable: true
    async: ~
    log: ~
    tasks:
         - { name: command:name argument --option, expression: "* * * * *" }
         - { name: another-command:name, expression: "@hourly" }
```

Import new config file to `config.yml` into `app/config` directory:

```yml
imports:
    ...
    - { resource: scheduler.yml }
```

Step 4: Add the Bundle log table schema (optional)
---------------------------------------

Then, if you want to track scheduled task(s) add the bundle log table schema and store executed task(s) to db:

```console
$ php bin/console doctrine:schema:update --force
```