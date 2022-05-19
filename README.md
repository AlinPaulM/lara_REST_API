Lara_REST_API - Laravel REST API
---------------------------------

SETUP INSTRUCTIONS(for local env):  
-use phpmyadmin/etc to create a database with the configuration found in .env.example(i.e. see the DB_* rows)  (e.g. on a standard WAMP, all configuration is as in .env.example, so just create a "laracrm" database in phpmyadmin)  
-open your terminal(git bash/etc), navigate to the project folder and run:  
composer install  
cp .env.example .env  
php artisan key:generate  
php artisan cache:clear  
php artisan migrate:fresh  
composer dump-autoload  
php artisan serve  
-start your MySQL(e.g. start WAMP)  

-go to http://127.0.0.1:8000/register and create a user  
-you will be redirected to http://127.0.0.1:8000/dashboard - there, press "CREATE NEW TOKEN" - give it a name, then save its value somewhere temporarily(as you'll only be able to see this value once, when you create the token)  
-open Postman, go to File->Import, press "Upload Files", choose "postman_collection.json" from the repo's base directory, press Import  
-go to Variables, and put the copied token value in the INITIAL VALUE and CURRENT VALUE fields, then press CTRL+S to save  

-now you can play with the API endpoints in Postman  
*********************************************************************************  
*This was a step-by-step tutorial follow-up of:*  
*https://www.youtube.com/watch?v=bvvVX9Pny84*  
*https://github.com/thecodeholic/laravel-image-manipulation-rest-api*  