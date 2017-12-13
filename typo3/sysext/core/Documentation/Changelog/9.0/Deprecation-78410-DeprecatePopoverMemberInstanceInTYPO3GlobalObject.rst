.. include:: ../../Includes.txt

===============================================================================
Deprecation: #78410 - Deprecate popover member instance in TYPO3 global object.
===============================================================================

See :issue:`78410`

Description
===========

The member instance :javascript:`TYPO3.Popover` has been marked as deprecated.


Impact
======

Using the global instance will not throw any deprecation message.


Affected Installations
======================

Any backend JavaScript or TypeScript using :javascript:`TYPO3.Popover`.


Migration
=========

Usage in TypeScript:

.. code-block:: typescript

	import Popover = require('TYPO3/CMS/Backend/Popover');

To use popovers in a amd module, add it as a dependency and a corresponding argument to the anonymous function:

.. code-block:: javascript

	define('TYPO3\CMS\Extension\Module', ['jquery', 'TYPO3\CMS\Backend\Popover', 'bootstrap'], function($, Popover) {});

.. index:: Backend, JavaScript, NotScanned
