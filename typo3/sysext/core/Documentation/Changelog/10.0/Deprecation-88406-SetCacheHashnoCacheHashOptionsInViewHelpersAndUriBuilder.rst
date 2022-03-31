.. include:: /Includes.rst.txt

====================================================================================
Deprecation: #88406 - setCacheHash/noCacheHash options in ViewHelpers and UriBuilder
====================================================================================

See :issue:`88406`

Description
===========

Various Fluid ViewHelpers regarding linking have arguments similar to:

* :php:`useCacheHash`
* :php:`noCacheHash`

which are not evaluated anymore.

Extbase's UriBuilder has the following options that have no effect anymore since the Site
Handling concept automatically detects when to a cHash argument is necessary:

* :php:`TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder->setUseCacheHash()`
* :php:`TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder->getUseCacheHash()`

Impact
======

Calling the UriBuilder methods will trigger a PHP :php:`E_USER_DEPRECATED` error.

Using the arguments :php:`noCacheHash`/:php:`useCacheHash` in the following ViewHelpers will
trigger a PHP :php:`E_USER_DEPRECATED` error:

* :html:`f:form`
* :html:`f:link.action`
* :html:`f:link.page`
* :html:`f:link.typolink`
* :html:`f:uri.action`
* :html:`f:uri.page`
* :html:`f:uri.typolink`
* :html:`f:widget.link`
* :html:`f:widget.uri`

If the underlying TypoLink logic is accessed directly, it will trigger a PHP :php:`E_USER_DEPRECATED` error
if :typoscript:`.useCacheHash` is set - without any effect either.


Affected Installations
======================

Any TYPO3 installation with custom templates setting this argument in Fluid or extensions
using Extbase's UriBuilder in a custom fashion.


Migration
=========

Remove any usages within the Fluid templates or Extension code.

.. index:: Fluid, PHP-API, TypoScript, PartiallyScanned
