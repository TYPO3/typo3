
.. include:: /Includes.rst.txt

======================================================================================
Breaking: #72424 - Removed deprecated TypoScriptFrontendController options and methods
======================================================================================

See :issue:`72424`

Description
===========

The following methods from `TypoScriptFrontendController` have been removed:

* `getPageRenderer()`
* `setExternalJumpUrl()`
* `jumpUrl()`
* `acquirePageGenerationLock()`
* `releasePageGenerationLock()`
* `doXHTML_cleaning()`
* `doLocalAnchorFix()`
* `checkFileInclude()`
* `prefixLocalAnchorsWithScript()`
* `getStorageSiterootPids()`

Additionally, the public properties `jumpurl`, `JSeventFuncCalls` and `anchorPrefix` have been removed. The
request parameter `jumpurl` is not evaluated anymore.

The TypoScript property `config.additionalHeaders` has been removed.


Impact
======

Calling any of the PHP methods directly will result in a fatal error. Accessing the properties will result in a PHP
warning. Setting the TypoScript property has no effect anymore.

Additionally, if EXT:felogin is misconfigured and lacks the `storagePid` property, an exception will be thrown.


Affected Installations
======================

Any installation using the TypoScript property above, or a TYPO3 instance having third-party extensions calling
the methods or properties directly.


Migration
=========

Use the TER extension `jumpurl` to implement the jumpurl functionality.

Use the `config.additionalHeaders` subproperties (see https://docs.typo3.org/typo3cms/TyposcriptReference/Setup/Config/Index.html#additionalheaders for details) to add the additional header lines.

.. index:: PHP-API, TypoScript, Frontend, ext:jumpurl
