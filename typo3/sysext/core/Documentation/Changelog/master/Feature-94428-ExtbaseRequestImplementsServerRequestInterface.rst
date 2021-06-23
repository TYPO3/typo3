.. include:: ../../Includes.txt

===================================================================
Feature: #94428 - Extbase Request implements ServerRequestInterface
===================================================================

See :issue:`94428`

Description
===========

The extbase :php:`TYPO3\CMS\Extbase\Mvc\Request` now implements
the PSR-7 :php:`ServerRequestInterface` and thus holds all request
related information of the main core request in addition to the
plugin namespace specific extbase arguments.


Impact
======

This allows getting information of the main request especially within
extbase controllers from :php:`$this->request`.

Developers of fluid view helpers can now retrieve the main PSR-7 request
in many contexts from :php:`$renderingContext->getRequest()`, in addition
to the extbase specific information specified by
:php:`TYPO3\CMS\Extbase\Mvc\Request\RequestInterface`.

Note that with future patches, the request assigned to view helper
:php:`RenderingContext` may NOT implement extbase
:php:`TYPO3\CMS\Extbase\Mvc\Request\RequestInterface` anymore, and
only PSR-7 :php:`ServerRequestInterface`. This will be the case when the
view helper is not called from within an extbase plugin, but when fluid
is started as "standalone view" in non-extbase based plugins: Often in
backend scenarios like toolbars, doc headers, non-extbase modules, etc.
Extensions should thus test for instance of extbase :php:`RequestInterface`
if they don't know the context and rely on extbase specific request data.


.. index:: PHP-API, ext:extbase
