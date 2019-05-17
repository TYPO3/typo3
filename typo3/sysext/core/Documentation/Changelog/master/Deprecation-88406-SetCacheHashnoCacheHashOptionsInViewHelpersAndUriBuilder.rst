.. include:: ../../Includes.txt

====================================================================================
Deprecation: #88406 - setCacheHash/noCacheHash options in ViewHelpers and UriBuilder
====================================================================================

See :issue:`88406`

Description
===========

Various Fluid ViewHelpers regarding linking have arguments similar to which are not evaluated anymore:
- useCacheHash
- noCacheHash

Extbase's UriBuilder has the following options that have no effect anymore since the Site
Handling concept automatically detects when to a cHash argument is necessary.

- UriBuilder->setUseCacheHash()
- UriBuilder->getUseCacheHash()

Impact
======

Calling the UriBuilder methods will trigger a deprecation message.

Using the arguments noCacheHash/useCacheHash in the following ViewHelpers will
trigger a deprecation message:
- f:form
- f:link.action
- f:link.page
- f:link.typolink
- f:uri.action
- f:uri.page
- f:uri.typolink
- f:widget.link
- f:widget.uri

If the underlying TypoLink logic is accessed directly, it will trigger a deprecation message
if `.useCacheHash` is set - without any effect either.


Affected Installations
======================

Any TYPO3 installation with custom templates setting this argument in Fluid or extensions
using Extbase's UriBuilder in a custom fashion.


Migration
=========

Remove any usages within the Fluid templates or Extension code.

.. index:: Fluid, PHP-API, TypoScript, PartiallyScanned
