.. include:: ../../Includes.txt

==========================================================================================
Deprecation: #86046 - Additional arguments in several TypoScriptFrontendController methods
==========================================================================================

See :issue:`86046`

Description
===========

The following public methods within :php:`TypoScriptFrontendController` now expect an argument:

* :php:`calculateLinkVars(array $queryParams)`
* :php:`preparePageContentGeneration(ServerRequestInterface $request)`

This is necessary to avoid usage of the PHP global variables $_GET/$_POST.

In addition, to be backwards-compatible with extensions previously using
:php:`GeneralUtility::_GETset()`, this method now also updates the global PSR-7 request
for the time being, although this method will be removed in the future.

TYPO3 aims to not access global state in the future, in order to do proper "sub requests".


Impact
======

Calling any of the methods mentioned above without a method argument will trigger an according
PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

TYPO3 installations with extensions using these methods.


Migration
=========

Inject either QueryParameters from a given PSR-7 request object or the object itself,
by looking at the according method signature.

.. index:: Frontend, FullyScanned, ext:frontend
