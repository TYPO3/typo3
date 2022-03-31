.. include:: /Includes.rst.txt

===================================================================
Feature: #94428 - Extbase Request implements ServerRequestInterface
===================================================================

See :issue:`94428`

Description
===========

The Extbase :php:`TYPO3\CMS\Extbase\Mvc\Request` now implements
the PSR-7 :php:`ServerRequestInterface` and thus holds all request
related information of the main Core request in addition to the
plugin namespace specific Extbase arguments.


Impact
======

This allows getting information of the main request especially within
Extbase controllers from :php:`$this->request`.

Developers of Fluid ViewHelpers can now retrieve the main PSR-7 request
in many contexts from :php:`$renderingContext->getRequest()`, in addition
to the Extbase specific information specified by
:php:`TYPO3\CMS\Extbase\Mvc\Request\RequestInterface`.

Note that with future patches, the request assigned to ViewHelper
:php:`RenderingContext` may NOT implement Extbase
:php:`TYPO3\CMS\Extbase\Mvc\Request\RequestInterface` anymore, and
only PSR-7 :php:`ServerRequestInterface`. This will be the case when the
ViewHelper is not called from within an Extbase plugin, but when Fluid
is started as "standalone view" in non-extbase based plugins: Often in
backend scenarios like toolbars, doc headers, non-extbase modules, etc.
Extensions should thus test for instance of Extbase :php:`RequestInterface`
if they don't know the context and rely on Extbase specific request data.


.. index:: PHP-API, ext:extbase
