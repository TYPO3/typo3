==================================================================================
Deprecation: #73442 - Modal.getSeverityClass has been moved to the Severity module
==================================================================================

Description
===========

The method :js:`Modal.getSeverityClass` has been moved to :js:`Severity.getCssClass`. :js:`Modal.getSeverityClass` has been marked as deprecated.


Impact
======

Calling :js:`Modal.getSeverityClass` will trigger a console warning in the browser.


Affected Installations
======================

All 3rd party extensions using :js:`Modal.getSeverityClass` are affected.


Migration
=========

Change the calls to :js:`Severity.getCssClass(severity)`.

.. index:: javascript
