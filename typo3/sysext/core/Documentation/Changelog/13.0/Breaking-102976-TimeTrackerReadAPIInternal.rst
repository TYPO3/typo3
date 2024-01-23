.. include:: /Includes.rst.txt

.. _breaking-102976-1706528522:

=================================================
Breaking: #102976 - TimeTracker read API internal
=================================================

See :issue:`102976`

Description
===========

Class :php:`\TYPO3\CMS\Core\TimeTracker` is used in the TYPO3 frontend rendering.
It allows tracking time consumed by single code sections. The admin panel uses
gathered data and renders a "time elapsed" overview from it.

All methods and properties that enable or disable tracking details and return
the gathered data have been marked :php:`@internal` and partially moved to
EXT:adminpanel.

Extensions should only write data to :php:`TimeTracker`, methods that are
considered API are these:

* :php:`TimeTracker->push()` (second argument may vanish)
* :php:`TimeTracker->pull()`
* :php:`TimeTracker->setTSlogMessage()`


Impact
======

Extensions using methods other than the ones listed above may raise PHP fatal
errors or different result structures when the underlying code is further
refactored.


Affected installations
======================

Most extensions in the wild use only the above listed methods. There is little
reason to use other methods, except for extension that mimic or extend
functionality of EXT:adminpanel. Instances with such extensions need to follow
changes of class :php:`TimeTracker`.


Migration
=========

No direct migration possible.


.. index:: Frontend, PHP-API, NotScanned, ext:frontend
