.. include:: ../../Includes.txt

=============================================
Breaking: #82572 - RDCT functionality removed
=============================================

See :issue:`82572`

Description
===========

The short-link / redirect functionality based on the GET parameter `RDCT` of TYPO3 Frontend requests
has been removed from TYPO3.

Along, all functionality related to evaluating `RDCT` is not evaluated anymore.

The following PHP methods have been removed:

* TypoScriptFrontendController->sendRedirect()
* TypoScriptFrontendController->updateMD5paramsRecord()
* GeneralUtility::makeRedirectUrl()

The eighth property of the constructor of TypoScriptFrontendController is not evaluated anymore,
also the public property `TSFE->RDCT` is removed as it is not set anymore.

The corresponding database table `cache_md5params` has been dropped.

Substitution logic can be found in the TER extension `rdct`.


Impact
======

When calling TYPO3 Frontend via `index.php&RDCT=myhash` the RDCT GET parameter is not evaluated
anymore.

Calling :php:`$TSFE->sendRedirect()`, :php:`$TSFE->updateMD5paramsRecord()` and
:php:`GeneralUtility::makeRedirectUrl()` will result in a fatal PHP error.

Accessing the now non-existent property :php:`$TSFE->RDCT` will trigger a PHP notice, as well
as setting up a new instance of `TypoScriptFrontendController` with a eighth parameter.

Accessing the database table `cache_md5params` will also lead to unexpected results as this table
does not exist in new installations anymore.


Affected Installations
======================

Any TYPO3 instance handling data via the `cache_md5params` database table or creating short links
via `&RDCT` hashes.


Migration
=========

The TER extension `rdct` contains all previous functionality handled via a simple hook. An upgrade
wizard within the Install Tool will check if the database table is filled and downloads the extension
from TER.

It is recommended to use a third-party short-url or redirect extension which provides a richer feature
set.

.. index:: Frontend, PHP-API, PartiallyScanned