#!/bin/bash

rm ../composer.json
composer create-project workerman/webman:1.5.4 project
cd project
composer require illuminate/database:^10.23 illuminate/pagination:^10.23 illuminate/events:^10.23 symfony/var-dumper:^6.3 vlucas/phpdotenv:^5.5 workerman/crontab:^1.0 webman/console:^1.2 webman/redis-queue:^1.2 webman/stomp:^1.1 webman/event:^1.0
cd ..
rm -f project/README.md
mv project/* .
mv project/.gitignore .
rm -rf project
rm -rf src
