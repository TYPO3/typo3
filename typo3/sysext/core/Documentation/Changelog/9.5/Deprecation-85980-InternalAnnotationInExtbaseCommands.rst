.. include:: /Includes.rst.txt

==============================================================
Deprecation: #85980 - @internal annotation in extbase commands
==============================================================

See :issue:`85980`

Description
===========

The :php:`@internal` annotation has been marked as deprecated and will be removed from TYPO3 v10
without any replacement.

This is a regular phpDocumentor annotation that is used to denote that associated structural
elements are elements internal to the application or library. It has been misused by Extbase to tell
if a command is internal and thus should not be exposed through help texts, user documentation etc.

TYPO3 does no longer support the use of the :php:`@internal` annotation to influence the behaviour
of the code.

Impact
======

Using :php:`@internal` on methods of classes extending
:php:`TYPO3\CMS\Extbase\Mvc\Controller\CommandController` will trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

All installations that make use of Extbase commands whose methods are tagged with :php:`@internal`.


Migration
=========

Just remove the annotation from the affected controllers.

.. index:: ext:extbase, NotScanned
