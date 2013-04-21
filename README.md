## Better, well tested and developed cURL Libraries you can use.
- [Guzzle](www.guzzlephp.org)
- [Buzz](https://github.com/kriswallsmith/Buzz)
- [Requests](http://requests.ryanmccue.info/)


# Laravel-cURL

Laravel-cURL is a library born out of Codeigniter-cURL by [Phil](http://philsturgeon.co.uk/code/codeigniter-curl) which makes it easy to do simple cURL requests and makes more complicated cURL requests easier too.

## Requirements

1. PHP 5.1+
2. Laravel 3
3. PHP 5 (configured with cURL enabled)
4. libcurl

## Features

* POST/GET/PUT/DELETE requests over HTTP
* HTTP Authentication
* Follows redirects
* Returns error string
* Provides debug information
* Proxy support
* Cookies

## Download

https://github.com/mogetutu/laravel-curl

## Examples

    $curl = New Curl;

### Simple calls

These do it all in one line of code to make life easy. They return the body of the page, or FALSE on fail.

    // Simple call to remote URL
    echo $curl->simple_get('http://example.com/');

    // Simple call to CI URI
    $curl->simple_post('controller/method', array('foo'=>'bar'));

    // Set advanced options in simple calls
    // Can use any of these flags http://uk3.php.net/manual/en/function.curl-setopt.php

    $curl->simple_get('http://example.com', array(CURLOPT_PORT => 8080));
    $curl->simple_post('http://example.com', array('foo'=>'bar'), array(CURLOPT_BUFFERSIZE => 10));

### Advanced calls

These methods allow you to build a more complex request.

    // Start session (also wipes existing/previous sessions)
    $curl->create('http://example.com/');

    // Option & Options
    $curl->option(CURLOPT_BUFFERSIZE, 10);
    $curl->options(array(CURLOPT_BUFFERSIZE => 10));

    // More human looking options
    $curl->option('buffersize', 10);

    // Login to HTTP user authentication
    $curl->httpLogin('username', 'password');

    // Post - If you do not use post, it will just run a GET request
    $post = array('foo'=>'bar');
    $curl->post($post);

    // Cookies - If you do not use post, it will just run a GET request
    $vars = array('foo'=>'bar');
    $curl->setCookies($vars);

    // Proxy - Request the page through a proxy server
    // Port is optional, defaults to 80
    $curl->proxy('http://example.com', 1080);
    $curl->proxy('http://example.com');

    // Proxy login
    $curl->proxyLogin('username', 'password');

    // Execute - returns response
    echo $curl->execute();

    // Debug data ------------------------------------------------

    // Errors
    $curl->error_code; // int
    $curl->error_string;

    // Information
    $curl->info; // array

