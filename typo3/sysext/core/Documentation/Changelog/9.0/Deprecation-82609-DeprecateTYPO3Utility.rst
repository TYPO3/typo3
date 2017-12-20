.. include:: ../../Includes.txt

=============================================
Deprecation: #82609 - Deprecate TYPO3.Utility
=============================================

See :issue:`82609`

Description
===========

The public property :javascript:`TYPO3.Utility` has been marked as deprecated. `Utility` may be used in AMD based modules by
importing :javascript:`TYPO3/CMS/Backend/Utility` instead.


Affected Installations
======================

All extensions using :javascript:`TYPO3.Utility` are affected.


Migration
=========

Import :javascript:`TYPO3/CMS/Backend/Utility` in your AMD module.

Example code:

.. code-block:: javascript

	define(['TYPO3/CMS/Backend/Utility'], function(Utility) {
		// use Utility here
	});

.. index:: JavaScript, Backend, NotScanned
