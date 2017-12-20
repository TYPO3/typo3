
.. include:: ../../Includes.txt

==================================================================================
Breaking: #66861 - Do not automatically append a "/" to the identifier of a folder
==================================================================================

See :issue:`66861`

Description
===========

The `Folder` object automatically appended a `/` to the identifier. But as the `Folder` object should not
manipulate the folder identifier this is removed.


Impact
======

Installations with custom FAL driver(s) could break.


Affected Installations
======================

Installations with a custom FAL driver that relies on the `/` being added by the Folder object.


Migration
=========

All drivers that depend on this `/` being added should be adjusted so the driver handles this.

.. index:: PHP-API, FAL