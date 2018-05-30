# Summary
A basic RESTful API framework written in PHP. Allows you to create a web service where data can be accessed in a simple URL format such as **http://example.com/api/[resource]/[id]**.

# Installation
-Simply deploy the files on your web server. Then, create your controllers as in the **classes/controllers/** directory. The sample `PlatformController` uses a flat-file approach to storing data, but this can be easily extended to use a database or other data source.
-Import the countries.sql file into your database
-Edit beepconfig.php and provide database credentials

# Sample request
http://YOURADDRESS/platform/countries
This should return a list of countries from the sample sql data included above

# Features in development
-SendGrid email service support
-Dev/pre-live/live environment support
