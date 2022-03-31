.. include:: /Includes.rst.txt

=======================================================================================
Deprecation: #85553 - PageRepository language-related methods use null as default value
=======================================================================================

See :issue:`85553`

Description
===========

The second parameter of the following methods now have a different default value (:php:`null`) than
before (:php:`-1`), to detect if the parameter is omitted or passed in explicitly:

* :php:`TYPO3\CMS\Frontend\Page\PageRepository->getPageOverlay()`
* :php:`TYPO3\CMS\Frontend\Page\PageRepository->getPagesOverlay()`


Impact
======

Calling one of these methods with the second argument with :php:`-1` will trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

TYPO3 installations with custom extensions related to language handling.


Migration
=========

In the unlikely case of directly calling these methods with :php:`-1`, it is recommended to remove
the second (optional) parameter completely, which will work in TYPO3 v8, TYPO3 v9 and in TYPO3 v10.

.. index:: Frontend, PHP-API, NotScanned, ext:frontend
