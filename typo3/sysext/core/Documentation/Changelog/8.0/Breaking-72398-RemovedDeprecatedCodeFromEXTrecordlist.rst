
.. include:: ../../Includes.txt

==============================================================
Breaking: #72398 - Removed deprecated code from EXT:recordlist
==============================================================

See :issue:`72398`

Description
===========

The following deprecated methods have been removed:

* `RecordList::printContent()`
* `ElementBrowserFramesetController::printContent()`

The following deprecated data members have been removed:

* `RecordList::$MCONF`

Support for multiple UIDs in the URL parameter `act` in `AbstractLinkBrowserController::initVariables()` has been removed.


Impact
======

Using the methods or variables above directly in any third party extension will result in a fatal error.


Affected Installations
======================

Instances which use custom calls to RecordList, AbstractLinkBrowserController, ElementBrowserFramesetController via the methods above, or use one of the variables mentioned above.


Migration
=========

`$MCONF` no replacement for this
`RecordList::printContent()` use `RecordList::mainAction()` instead
`AbstractLinkBrowserController::initVariables()` no replacement for using multiple UIDs
`ElementBrowserFramesetController::printContent()` use `ElementBrowserFramesetController::mainAction()` instead

.. index:: PHP-API, ext:recordlist, Backend
