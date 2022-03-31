
.. include:: /Includes.rst.txt

=====================================================================================
Breaking: #57089 - Behaviour of page shortcut to "Parent of selected or current page"
=====================================================================================

See :issue:`57089`

Description
===========

In former versions of TYPO3 CMS the page shortcut type "Parent of selected or current page" had a misleading label
as the selected page was never taken into account, it always chose the parent of the current page.

This has been changed and the selected page is now considered, so the parent of the selected page will be used as the target page.

Impact
======

Assuming the supplied upgrade wizard was run, the behaviour of existing shortcuts will not change.

If you fail to run the upgrade wizard, the target of a shortcut is changed to the parent of the
selected page as the selected page is now respected.

Affected installations
======================

Any installation using shortcut pages with shortcut type "Parent of selected or current page"

Migration
=========

Run the supplied upgrade wizard in the Install Tool if it shows up.


.. index:: PHP-API, Backend
