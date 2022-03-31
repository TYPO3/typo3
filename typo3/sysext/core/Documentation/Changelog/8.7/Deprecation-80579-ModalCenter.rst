.. include:: /Includes.rst.txt

================================================================
Deprecation: #80579 - Modal.center has been marked as deprecated
================================================================

See :issue:`80579`

Description
===========

The method :js:`Modal.center` has been marked as deprecated. Alignment is now
handled via CSS and this method is now obsolete.


Impact
======

Calling :js:`Modal.center` will trigger a console warning in the browser.


Affected Installations
======================

All 3rd party extensions using :js:`Modal.center` are affected.


Migration
=========

Remove obsolete calls to :js:`Modal.center()`.


.. index:: JavaScript, Backend
