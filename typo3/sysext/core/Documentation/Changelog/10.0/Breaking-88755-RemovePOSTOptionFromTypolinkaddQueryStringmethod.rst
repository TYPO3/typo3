.. include:: /Includes.rst.txt

=========================================================================
Breaking: #88755 - Remove POST option from typolink.addQueryString.method
=========================================================================

See :issue:`88755`

Description
===========

Setting :typoscript:`addQueryString.method` of typolink could be used like shown below in order to transform
HTTP POST parameters into according GET parameters.

.. code-block:: typoscript

   typolink {
     parameter = 123
     addQueryString = 1
     addQueryString.method = POST
   }

In terms of correctly using HTTP verbs it's bad practise in general to treat GET and POST equally, besides that
documentation already mentioned potential side-effects like accidentally exposing sensitive data submitted via
POST to proxies or log files.

That's why values :typoscript:`POST`, :typoscript:`GET,POST` and :typoscript:`POST,GET` are not allowed anymore
for :typoscript:`typolink.addQueryString.method`. Maintaining functionality - if required at all - has to be done
using domain specific logic in according controllers or middleware implementations.


Impact
======

* using :typoscript:`GET,POST`, :typoscript:`POST,GET` or :typoscript:`POST` will trigger an :php:`E_USER_WARNING`
* using :typoscript:`GET,POST` or :typoscript:`POST,GET` will fall back to :typoscript:`GET`
* using :typoscript:`POST` will be ignored and an empty result

In a consequence only query parameters submitted via HTTP GET are taken into account, parameters of HTTP POST
body are ignored.


Affected Installations
======================

* TypoScript defining :typoscript:`typolink.addQueryString.method` with values mentioned in previous section
* invocations of :php:`TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder::setAddQueryStringMethod()` with values
  mentioned in previous section
* as an effect Fluid view helpers forwarding this information to
  :php:`TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder::setAddQueryStringMethod()` are affected -
  argument :php:`addQueryStringMethod` is affected in view helper of TYPO3 core like shown below
  + :html:`<f:form ... addQueryStringMethod="POST">`
  + :html:`<f:link.action addQueryStringMethod="POST">`
  + :html:`<f:link.page ... addQueryStringMethod="POST">`
  + :html:`<f:link.typolink addQueryStringMethod="POST">`
  + :html:`<f:uri.action ... addQueryStringMethod="POST">`
  + :html:`<f:uri.page ... addQueryStringMethod="POST">`
  + :html:`<f:uri.typolink addQueryStringMethod="POST">`
  + :html:`<f:widget.uri ... addQueryStringMethod="POST">`
  + :html:`<f:widget.link addQueryStringMethod="POST">`
  + :html:`<f:widget.paginate ... configuration="{addQueryStringMethod: 'POST'}">`


Migration
=========

* change to mentioned assignments in TypoScript, Fluid templates or PHP code to :typoscript:`GET`
* analyse and try to understand whether :typoscript:`POST` is still required or could be substituted


.. index:: Backend, Fluid, Frontend, PHP-API, TypoScript, NotScanned
