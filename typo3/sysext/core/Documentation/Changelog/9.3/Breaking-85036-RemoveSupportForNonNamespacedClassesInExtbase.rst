.. include:: ../../Includes.txt

========================================================================
Breaking: #85036 - Removed support for non-namespaced classes in Extbase
========================================================================

See :issue:`85036`

Description
===========

Non-namespaced classes like :php:`Tx_Extension_Controller_FooController` are not supported any more
and all magic based on class names no longer works with classes like these:

* Translating model name to repository name (and vice versa)
* Translating model name to validator name
* Guessing the extension name
* Guessing the controller name by looking at a command name
* Translating model name to (database) table name
* Recognizing child property types in object storage annotations

Impact
======

All this magic no longer works with non-namespaced classes.


Affected Installations
======================

All installations that still use non-namespaced classes in Extbase.


Migration
=========

Use namespaced class names for Extbase.

.. index:: ext:extbase, NotScanned
