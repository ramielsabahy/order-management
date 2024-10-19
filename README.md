
## Order Management

Pre-requisites you must have before running the project
- PHP 8.2 installed
- Composer
- MySQL

Steps to get the project up and running :
- run `cp .env.example .env` 
- configure the `.env` and put your database credentials
- run `composer install`
- run `php artisan key:generate`
- run `php artisan passport:keys --force`
- run `php artisan migrate --seed`
- run `php artisan passport:client --personal `
- run `php artisan serve`

Optionally if you have docker installed you can do the following
- run `cp .env.docker .env`
- run `composer install`
- run `./vendor/bin/sail up -d`
- run `./vendor/bin/sail artisan migrate --seed`

Configure the `.env` to put your paypal credentials or keep it as is to use my sandbox

run `php artisan test` to run the unit and feature tests

view Postman collection at https://documenter.getpostman.com/view/3208343/2sAXxWb9jK

Also the project is hosted on AWS in case you found any issues installing it you can use the previous collection as it holds the URL hosted


You can find the test coverage report at http://35.179.119.46/reports/index.html
