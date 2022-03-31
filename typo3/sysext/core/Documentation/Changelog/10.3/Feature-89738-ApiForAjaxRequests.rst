.. include:: /Includes.rst.txt

=======================================
Feature: #89738 - API for AJAX Requests
=======================================

See :issue:`89738`

Description
===========

Request
-------

In order to become independent of jQuery, a new API to perform AJAX requests has been introduced. This API implements
the `fetch API`_ available in all modern browsers.

To send a request, a new instance of `AjaxRequest` must be created which receives a single argument:

* :js:`url` (string) - The endpoint to send the request to

For compatibility reasons the :js:`Promise` prototype is extended to have basic support for jQuery's :js:`$.Deferred()`.
In all erroneous cases, the internal promise is rejected with an instance of `AjaxResponse` containing the original
`response object`_.

withQueryArguments()
~~~~~~~~~~~~~~~~~~~~

Clones the current request object and sets query arguments used for requests that get sent.

This method receives the following arguments:

* :js:`queryArguments` (string | array | object) - Optional: Query arguments to append to the url

The method returns a clone of the AjaxRequest instance.


get()
~~~~~

Sends a `GET` requests to the configured endpoint.

This method receives the following arguments:

* :js:`init` (object) - Optional: additional `request configuration`_ for the request object used by :js:`fetch()`

The method returns a promise resolved to an `AjaxResponse`.

Example:

.. code-block:: js

   require(['TYPO3/CMS/Core/Ajax/AjaxRequest'], function (AjaxRequest) {
     const request = new AjaxRequest('https://httpbin.org/json');
     request.get().then(
       async function (response) {
         const data = await response.resolve();
         console.log(data);
       }, function (error) {
         console.error('Request failed because of error: ' + error.status + ' ' + error.statusText);
       }
     );
   });


post()
~~~~~~

Sends a `POST` requests to the configured endpoint. All responses are uncached by default.

This method receives the following arguments:

* :js:`data` (object) - Request body sent to the endpoint, get's converted to :js:`FormData`
* :js:`init` (object) - Optional: additional `request configuration`_ for the request object used by :js:`fetch()`

The method returns a promise resolved to an `AjaxResponse`.

Example:

.. code-block:: js

   require(['TYPO3/CMS/Core/Ajax/AjaxRequest'], function (AjaxRequest) {
     const body = {
       foo: 'bar',
       baz: 'quo'
     };
     const init = {
       mode: 'cors'
     };
     const request = new AjaxRequest('https://example.com');
     request.post(body, init).then(
       async function (response) {
         console.log('Data has been sent');
       }, function (error) {
         console.error('Request failed because of error: ' + error.status + ' ' + error.statusText);
       }
     );
   });


put()
~~~~~

Sends a `PUT` requests to the configured endpoint. All responses are uncached by default.

This method receives the following arguments:

* :js:`data` (object) - Request body sent to the endpoint, get's converted to :js:`FormData`
* :js:`init` (object) - Optional: additional `request configuration`_ for the request object used by :js:`fetch()`

The method returns a promise resolved to an `AjaxResponse`.

Example:

.. code-block:: js

   require(['TYPO3/CMS/Core/Ajax/AjaxRequest'], function (AjaxRequest) {
     const fileField = document.querySelector('input[type="file"]');
     const body = {
       file: fileField.files[0],
       username: 'Baz Bencer'
     };
     const request = new AjaxRequest('https://example.com');
     request.put(body).then(null, function (error) {
       console.error('Request failed because of error: ' + error.status + ' ' + error.statusText);
     });
   });


delete()
~~~~~~~~

Sends a `DELETE` requests to the configured endpoint. All responses are uncached by default.

This method receives the following arguments:

* :js:`data` (object) - Request body sent to the endpoint, get's converted to :js:`FormData`
* :js:`init` (object) - Optional: additional `request configuration`_ for the request object used by :js:`fetch()`

The method returns a promise resolved to an `AjaxResponse`.

Example:

.. code-block:: js

   require(['TYPO3/CMS/Core/Ajax/AjaxRequest'], function (AjaxRequest) {
     const request = new AjaxRequest('https://httpbin.org/delete');
     request.delete().then(null, function (error) {
         console.error('Request failed because of error: ' + error.status + ' ' + error.statusText);
       }
     );
   });


abort()
~~~~~~~~~~

Aborts the request by using its instance of `AbortController`_.


Response
--------

Each response received is wrapped in an :js:`AjaxResponse` object. This object contains some methods to handle the response.

resolve()
~~~~~~~~~

Converts and returns the response body according to the **received** `Content-Type` header either into JSON or plaintext.

Example:

.. code-block:: js

   require(['TYPO3/CMS/Core/Ajax/AjaxRequest'], function (AjaxRequest) {
     new AjaxRequest('https://httpbin.org/json').get().then(
       async function (response) {
         // Response is automatically converted into a JSON object
         const data = await response.resolve();
         console.log(data);
       }, function (error) {
         console.error('Request failed because of error: ' + error.status + ' ' + error.statusText);
       }
     );
   });


raw()
~~~~~

Returns the original response object, which is useful for e.g. add additional handling for specific headers in application
logic or to check the response status.

Example:

.. code-block:: js

   require(['TYPO3/CMS/Core/Ajax/AjaxRequest'], function (AjaxRequest) {
     new AjaxRequest('https://httpbin.org/status/200').get().then(
       function (response) {
         const raw = response.raw();
         if (raw.headers.get('Content-Type') !== 'application/json') {
            console.warn('We didn\'t receive JSON, check your request.');
         }
       }, function (error) {
         console.error('Request failed because of error: ' + error.status + ' ' + error.statusText);
       }
     );
   });


.. _`fetch API`: https://developer.mozilla.org/docs/Web/API/Fetch_API
.. _`request configuration`: https://developer.mozilla.org/en-US/docs/Web/API/Request#Properties
.. _`response object`: https://developer.mozilla.org/en-US/docs/Web/API/Response
.. _`AbortController`: https://developer.mozilla.org/en-US/docs/Web/API/AbortController

.. index:: JavaScript, ext:core
