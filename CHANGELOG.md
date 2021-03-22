## 1.1.0 (March 22, 2021)
  - Add http trigger endpoint GET scheduler/run

## 1.0.3 (March 04, 2021)
  - Removed container get usages for the fetch private services

## 1.0.2 (December 04, 2020)
  - Compatible with doctrine bundle 2.0
  - Compatible with symfony 4 and symfony 5

## 1.0.1 (December 16, 2019)
  - Fix command name parser quoted argument bug

## 1.0.0 (December 10, 2019)
  - Add symfony 4.4 support

## 0.4.7 (May 16, 2019)
  - Fix command name parser by quoted single or double

## 0.4.6 (May 15, 2019)
  - Add command name validation helper

## 0.4.5 (April 18, 2019)
  - Add expression validation to service

## 0.4.4 (April 16, 2019)
  - Fix service not exists error for repository by 'doctrine.service_repository' tag

## 0.4.3 (April 15, 2019)
  - Fix service non-defined repository

## 0.4.2 (April 15, 2019)
  - Fix on a non-existent parameter "console.command.ids" bug

## 0.4.1 (April 15, 2019)
  - Add service layer and access repository as a service

## 0.4.0 (April 02, 2019)
  - Add database as a resource for scheduled tasks

## 0.3.4 (March 26, 2019)
  - Async process timeout configured as 24 hours (1 day)

## 0.3.3 (March 14, 2019)
  - Fix list command missing argument bug

## 0.3.2 (March 14, 2019)
  - Seperate command list argument as a new command

## 0.3.1 (March 13, 2019)
  - Fix annotation partial cron expression divided by backslash

## 0.3.0 (March 12, 2019)
  - Add scheduled command task list option

## 0.2.1 (March 11, 2019)
  - Fix multiple schedule on one command

## 0.2.0 (March 10, 2019)
  - Add annotation functionality

## 0.1.15 (February 28, 2019)
  - Fix travis memory issue
  - Add process started status

## 0.1.14 (February 27, 2019)
  - Fix failed process remove bug and add timeout an hour for process

## 0.1.13 (February 27, 2019)
  - Fix remaining control bug
  - Add travis badge

## 0.1.12 (January 13, 2019)
  - Remove symfony 2.8 version

## 0.1.11 (January 13, 2019)
  - Fix php version

## 0.1.10 (January 13, 2019)
  - Fix deprecation

## 0.1.9 (January 13, 2019)
  - Fix simple-phpunit

## 0.1.8 (January 13, 2019)
  - Finish extra options

## 0.1.7 (January 8, 2019)
  - Check empty output

## 0.1.6 (January 3, 2019)
  - Output log every succeded task

## 0.1.5 (December 26, 2018)
  - Fix async command argument bug

## 0.1.4 (December 21, 2018)
  - Change readme file for async config

## 0.1.3 (December 21, 2018)
  - Add asyc options to config
  - Add allow cotrib for symfony recipie
  - Update readme file for async flag
  - Add --async option for running task(s) as asynchronously
  - Improve command name parsing
  - Fix command name bug in arguments
  - Add default options for config and give an axample
  - Change cron expression repo
  - Fix phpunit config
  - Fix readme doc typo
  - Add command tests
  - Fix update method
  - Remove unnecessary const time format
  - Fix typo info message
  - Add enable/disable mod for logging
  - Fix symfony version 4 duplicate db log
  - Add symfony 4.0 support
  - Fix table searc index order
  - Make storing scheduled task's to db is optional
  - Show empty config tasks message and update readme
  - Show message when command is disabled
  - Fix autoload typo error
  - Update composer requirements
  - Initial commit

