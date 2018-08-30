.. include:: ../../Includes.txt

==========================================================================================
Deprecation: #86046 - Additional arguments in several TypoScriptFrontendController methods
==========================================================================================

See :issue:`86046`

Description
===========

The following public methods within :php:`TypoScriptFrontendController` now expect an argument:
- :php:`makeCacheHash(ServerRequestInterface $request)`
- :php:`calculateLinkVars(array $queryParams)`
- :php:`preparePageContentGeneration(ServerRequestInterface $request)`

This is necessary to avoid using the PHP global variables $_GET/$_POST.

In addition, to be backwards-compatible with extensions previously using
:php:`GeneralUtility::_GETset()`, this method now also updates the global PSR-7 request
for the time being, although this method will vanish in the future.

TYPO3 aims to not access global state in the future, in order to do proper "sub requests".


Impact
======

Calling any of the methods above without a first method argument will trigger an according
deprecation message.


Affected Installations
======================

TYPO3 installations with extensions using these methods previously.


Migration
=========

Inject either QueryParameters from a given PSR-7 request object or the object itself,
by looking at the according method signature.

.. index:: Frontend, FullyScanned, ext:frontend