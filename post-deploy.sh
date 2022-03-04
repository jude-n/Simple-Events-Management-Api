#!/bin/sh
docker exec simple-events-management-api_app_1 cp .env.example .env

docker exec simple-events-management-api_app_1 php artisan migrate

docker exec simple-events-management-api_app_1 php artisan db:seed
