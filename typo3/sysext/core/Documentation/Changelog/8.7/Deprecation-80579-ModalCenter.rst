.. include:: ../../Includes.txt

================================================================
Deprecation: #80579 - Modal.center has been marked as deprecated
================================================================

See :issue:`80579`

Description
===========

The method :javascript:`Modal.center` has been marked as deprecated. Alignment is now
handled via CSS and this method is now obsolete.


Impact
======

Calling :javascript:`Modal.center` will trigger a console warning in the browser.


Affected Installations
======================

All 3rd party extensions using :javascript:`Modal.center` are affected.


Migration
=========

Remove obsolete calls to :javascript:`Modal.center()`.


.. index:: JavaScript, Backend
