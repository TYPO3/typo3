.. include:: ../../Includes.txt

========================================================
Breaking: #78477 - Refactoring of FlashMessage rendering
========================================================

See :issue:`78477`

Description
===========

The following method has been removed:

* :php:`FlashMessage->getMessageAsMarkup()`

Impact
======

Using this method will stop working immediately.


Affected Installations
======================

All installations using the mentioned method above.


Migration
=========

Use the new :php:`FlashMessageRendererResolver::class`, for example:

.. code-block:: php

	GeneralUtility::makeInstance(FlashMessageRendererResolver::class)->resolve()->render()

.. index:: Backend, PHP-API
