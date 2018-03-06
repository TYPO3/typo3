.. include:: ../../Includes.txt

============================================================
Deprecation: #79658 - PageRepository shouldFieldBeOverlaid()
============================================================

See :issue:`79658`

Description
===========

The following method has been deprecated:

* :code:`TYPO3\CMS\Frontend\Page\PageRepository->shouldFieldBeOverlaid()`


Impact
======

Localized record fields are always "overlaid", the method returns true in all cases.


Affected Installations
======================

Instances with extensions calling this method


Migration
=========

The deprecated method returns TRUE in all cases, the call can be omitted.

.. index:: Frontend, PHP-API
