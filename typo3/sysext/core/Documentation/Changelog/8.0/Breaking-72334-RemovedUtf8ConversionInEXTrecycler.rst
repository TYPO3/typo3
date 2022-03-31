
.. include:: /Includes.rst.txt

==========================================================
Breaking: #72334 - Removed utf8 conversion in EXT:recycler
==========================================================

See :issue:`72334`

Description
===========

The recycler module previously handled conversions of labels to and from UTF-8 in order to send proper UTF-8
encoded data via JavaScript. The TYPO3 backend is running with UTF-8 since TYPO3 4.5.

The logic and the according functions have been removed as they are not needed anymore.


Impact
======

The following methods have been removed:


.. code-block:: php

	RecyclerUtility::getUtf8String()
	RecyclerUtility::isNotUtf8Charset()
	RecyclerUtility::getCurrentCharset()


Affected Installations
======================

Any TYPO3 instance directly accessing any of the mentioned `RecyclerUtility`
methods above via a custom extension.


Migration
=========

Remove the usages to these methods, and use the strings directly.

.. index:: PHP-API, ext:recycler
