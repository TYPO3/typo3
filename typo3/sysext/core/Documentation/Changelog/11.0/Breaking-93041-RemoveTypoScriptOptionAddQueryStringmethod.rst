.. include:: /Includes.rst.txt

=================================================================
Breaking: #93041 - Remove TypoScript option addQueryString.method
=================================================================

See :issue:`93041`

Description
===========

The TypoScript option :typoscript:`addQueryString.method` has been removed.

If omitted, this added all parameters from the PHP `$_SERVER[QUERY_STRING]`
value, which was used heavily in PHP 3 / PHP 4 times, instead of the more
"modern" `$_GET` parameters, which was set via `addQueryString.method = GET`.

However, the latter solution was / is the default for working in PSR-7
requests, and with routing. The option itself is removed, in order to
have TYPO3 use the same values throughout TYPO3 Core, making `method = GET`
the default and thus, the only option.

To further streamline TYPO3s source code, the underlying PHP method
:php:`ContentObjectRenderer->getQueryArguments()` now only accepts exactly
one argument.

All Fluid arguments related to that setting, or Extbase UriBuilder methods
do not change any behavior anymore related to building an Uri.

Impact
======

Calling :php:`UriBuilder->setAddQueryStringMethod()` will trigger a PHP :php:`E_USER_DEPRECATED` error.

Calling :php:`ContentObjectRenderer->getQueryArguments()` with more
than one argument will have no effect anymore.

Setting the TypoScript option :typoscript:`addQueryString.method` will
have no effect anymore.

Using the :html:`addQueryStringMethod` argument in the following
ViewHelpers will trigger a deprecation notice:

* :html:`<f:form>`
* :html:`<f:link.action>`
* :html:`<f:link.page>`
* :html:`<f:link.typolink>`
* :html:`<f:uri.action>`
* :html:`<f:uri.page>`
* :html:`<f:uri.typolink>`


Affected Installations
======================

Any TYPO3 installation

* with extensions calling Extbase's :php:`UriBuilder->setAddQueryStringMethod()` method
* with extensions calling :php:`ContentObjectRenderer->getQueryArguments()` with more then one argument
* with custom templates setting the :html:`addQueryStringMethod` argument in Fluid using one of the mentioned ViewHelper.


Migration
=========

Remove any usages within the Fluid templates or Extension code.

.. index:: Frontend, TypoScript, FullyScanned, ext:frontend
