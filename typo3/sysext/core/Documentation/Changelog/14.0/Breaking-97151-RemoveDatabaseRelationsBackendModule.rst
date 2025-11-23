..  include:: /Includes.rst.txt

..  _breaking-97151-1744183267:

=============================================================
Breaking: #97151 - Remove "Database Relations" backend module
=============================================================

See :issue:`97151`

Description
===========

The backend submodule **Database Relations** within **DB Check** provided
information about potentially broken database relations. However, the
information it displayed was very limited and barely helpful. In addition, the
entire module and its code have not received any meaningful updates in recent
years.

Due to this, the module has been removed.

Impact
======

The module has been removed. Existing links and stored bookmarks will no longer
work.

Affected installations
======================

All TYPO3 installations are affected.

Migration
=========

There is no migration available.

..  index:: Backend, NotScanned, ext:lowlevel
