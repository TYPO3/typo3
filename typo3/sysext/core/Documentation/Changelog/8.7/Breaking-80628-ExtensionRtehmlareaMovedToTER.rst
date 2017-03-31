.. include:: ../../Includes.txt

====================================================
Breaking: #80628 - Extension rtehmlarea moved to TER
====================================================

See :issue:`80628`

Description
===========

The legacy extension ``EXT:rtehtmlarea`` has been removed from the TYPO3 CMS core
and is only available as TER extension.


Impact
======

The new extension ``EXT:rte_ckeditor`` is loaded by default, if you need features
of the old rtehmlarea extension, you have to install ``EXT:rtehtmlarea`` from TER.
An upgrade wizard can do this for you in the upgrade process of the install tool.
If you have allowed images in RTE, you should install the rtehtmlarea extension,
the ckeditor extension does not support images in RTE.


Affected Installations
======================

Most installations are not affected. Instances are only affected if a loaded
extension has a dependency to ``EXT:rtehtmlarea`` extension, or if the instance
has used special plugins.


Migration
=========

Use the upgrade wizard provided by the install tool to fetch and load the extensions
from TER if you really need it.

.. index:: Backend, RTE
