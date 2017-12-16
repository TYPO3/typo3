
.. include:: ../../Includes.txt

==================================================================================
Deprecation: #73442 - Modal.getSeverityClass has been moved to the Severity module
==================================================================================

See :issue:`73442`

Description
===========

The method :javascript:`Modal.getSeverityClass` has been moved to :javascript:`Severity.getCssClass`. :javascript:`Modal.getSeverityClass` has been marked as deprecated.


Impact
======

Calling :javascript:`Modal.getSeverityClass` will trigger a console warning in the browser.


Affected Installations
======================

All 3rd party extensions using :javascript:`Modal.getSeverityClass` are affected.


Migration
=========

Change the calls to :javascript:`Severity.getCssClass(severity)`.

.. index:: JavaScript, Backend
