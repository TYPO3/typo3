
# HTTP_Request2

Provides an easy way to perform HTTP requests, uses pluggable adapters

* Socket: pure PHP implementation of HTTP protocol (does *not* use http stream wrapper), based on older [PEAR HTTP_Request] package
* Curl: wrapper around PHP's cURL extension
* Mock: used for testing packages depending on HTTP_Request2, returns predefined responses without network interaction

Both Socket and Curl adapters support POST requests with data and file uploads, basic and digest
authentication, cookies, managing cookies across requests, HTTP and SOCKS5 proxies, gzip and
deflate encodings, redirects, monitoring the request progress with Observers...

This package is [PEAR HTTP_Request2] and has been migrated from [PEAR SVN]

Please report all issues via the [PEAR bug tracker].

Pull requests are welcome.

[PEAR HTTP_Request]: http://pear.php.net/package/HTTP_Request/
[PEAR HTTP_Request2]: http://pear.php.net/package/HTTP_Request2/
[PEAR SVN]: https://svn.php.net/repository/pear/packages/HTTP_Request2
[PEAR bug tracker]: http://pear.php.net/bugs/search.php?cmd=display&package_name[]=HTTP_Request2

## Basic usage

```PHP
require_once 'HTTP/Request2.php';

$request = new HTTP_Request2('http://pear.php.net/', HTTP_Request2::METHOD_GET);
try {
    $response = $request->send();
    if (200 == $response->getStatus()) {
        echo $response->getBody();
    } else {
        echo 'Unexpected HTTP status: ' . $response->getStatus() . ' ' .
             $response->getReasonPhrase();
    }
} catch (HTTP_Request2_Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
```

## Documentation

...is available on PEAR website

 * Numerous [configuration options](http://pear.php.net/manual/en/package.http.http-request2.config.php)
 * How to populate [the request object](http://pear.php.net/manual/en/package.http.http-request2.request.php)
 * Description of [available adapters](http://pear.php.net/manual/en/package.http.http-request2.adapters.php)
 * Processing of [HTTP response](http://pear.php.net/manual/en/package.http.http-request2.response.php)
 * Monitoring the progress of request with [observers](http://pear.php.net/manual/en/package.http.http-request2.observers.php)
 * Possible [exceptions](http://pear.php.net/manual/en/package.http.http-request2.exceptions.php)

[Generated API documentation](http://pear.php.net/package/HTTP_Request2/docs/latest/) for the current release is also there.

## Testing, Packaging and Installing (Pear)

To test, run either

    $ phpunit tests/

or

    $ pear run-tests -r

You may need to set up the NetworkConfig.php file if you want to perform tests that interact with a web server.
Its template is NetworkConfig.php.dist file, consult it for the details.

To build, simply

    $ pear package

To install from scratch

    $ pear install package.xml

To upgrade

    $ pear upgrade -f package.xml
