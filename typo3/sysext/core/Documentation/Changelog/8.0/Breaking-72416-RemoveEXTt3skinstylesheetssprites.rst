=========================================================
Breaking: #72416 - Remove EXT:t3skin/stylesheets/sprites/
=========================================================

Description
===========

The sprites and related icons from EXT:t3skin/stylesheets/sprites/ have been removed.


Impact
======

References to the sprites or images of EXT:t3skin/ will throw a 404 not found.


Affected Installations
======================

Installations or extensions which have references to icons in EXT:t3skin/images/icons/* or EXT:t3skin/stylesheets/sprites/.


Migration
=========

No migration, remove all references and use the IconFactory for all icon related stuff.

.. index:: php, icons
