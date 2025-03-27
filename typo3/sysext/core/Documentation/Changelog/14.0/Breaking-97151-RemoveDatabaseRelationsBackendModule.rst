..  include:: /Includes.rst.txt

..  _breaking-97151-1744183267:

=============================================================
Breaking: #97151 - Remove "Database Relations" backend module
=============================================================

See :issue:`97151`

Description
===========

The backend sub-module "Database Relations" within "DB Check" aims to provide
information about potentially broken database relations.
The information it gives is really sparse and barely helpful, also the whole
module and its code received no meaningful updates in the past.

Due to this, the module has been removed.


Impact
======

The module has been removed. Links and stored bookmarks will not work anymore.


Affected installations
======================

All TYPO3 installations are affected.


Migration
=========

There is no migration available.

..  index:: Backend, NotScanned, ext:lowlevel
