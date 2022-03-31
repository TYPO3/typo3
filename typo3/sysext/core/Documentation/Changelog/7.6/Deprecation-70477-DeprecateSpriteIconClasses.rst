
.. include:: /Includes.rst.txt

==================================================
Deprecation: #70477 - Deprecate SpriteIcon classes
==================================================

See :issue:`70477`

Description
===========

The following classes have been marked as deprecated.

.. code-block:: php

	\TYPO3\CMS\Backend\Sprite\AbstractSpriteHandler
	\TYPO3\CMS\Backend\Sprite\SimpleSpriteHandler
	\TYPO3\CMS\Backend\Sprite\SpriteBuildingHandler


Impact
======

Any usage of these classes will trigger a deprecation log entry.


Affected Installations
======================

Extensions that use these PHP classes.


Migration
=========

Use the `IconRegistry` to register icons.


.. index:: PHP-API, Backend
