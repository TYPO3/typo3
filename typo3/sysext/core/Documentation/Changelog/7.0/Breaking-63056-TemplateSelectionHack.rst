
.. include:: ../../Includes.txt

=================================================
Breaking: #63056 - Remove Template Selection Hack
=================================================

See :issue:`63056`

Description
===========

There has been an ancient hack for the former "freesite" extension, which enabled selection
of the template via GET variable.
This technique is outdated and has been removed.

Impact
======

Any extension using this hack will not be able to select the template this way anymore.

Affected installations
======================

Installations with third party extensions using the hack.

Migration
=========

No migration path intended.
