.. include:: /Includes.rst.txt

===========================================================
Deprecation: #86184 - Protected methods in ReportController
===========================================================

See :issue:`86184`

Description
===========

The following methods of class :php:`TYPO3\CMS\Reports\Controller\ReportController`
changed their visibility from public to protected and should not be called any longer:

* :php:`indexAction()`
* :php:`detailAction()`

Impact
======

Calling one of the above methods from an external object will trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

Both methods are called internally only. Extensions extending the reports module
using the normal reports API are not affected by this.


Migration
=========

No migration possible.

.. index:: Backend, PHP-API, NotScanned, ext:reports
