.. include:: /Includes.rst.txt

===========================================================
Deprecation: #78477 - Refactoring of FlashMessage rendering
===========================================================

See :issue:`78477`

Description
===========

The following methods and properties within :php:`FlashMessage::class` have been marked as deprecated:

* :php:`FlashMessage->classes`
* :php:`FlashMessage->icons`
* :php:`FlashMessage->getClass()`
* :php:`FlashMessage->getIconName()`

Impact
======

Using these properties and methods will stop working in TYPO3 v9.


Affected Installations
======================

All installations using the mentioned methods and properties above.


Migration
=========

Use the new :php:`FlashMessageRendererResolver::class`, for example:


.. code-block:: php

	GeneralUtility::makeInstance(FlashMessageRendererResolver::class)->resolve()->render()

.. index:: Backend, PHP-API
