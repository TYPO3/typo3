
.. include:: ../../Includes.txt

===================================================================
Breaking: #72400 - Removed deprecated IconUtility and SpriteManager
===================================================================

See :issue:`72400`

Description
===========

Removed deprecated IconUtility class completely.
All SpriteManager related code has been removed from the core.

The Install Tool option `BE/spriteIconGenerator_handler` has no effect anymore.

`Bootstrap::initializeSpriteManager()` has been removed.


Impact
======

Using the static class IconUtility or the SpriteManager will result in a fatal error.


Affected Installations
======================

Instances which use custom calls to one of the above mentioned classes.


Migration
=========

Use the new introduced IconAPI that is available since 7LTS.

.. index:: PHP-API, Backend, Frontend
