.. include:: /Includes.rst.txt

==================================================================================
Breaking: #73016 - Renaming of Clipboard->printContentFromTab to getContentFromTab
==================================================================================

See :issue:`73016`

Description
===========

During the fluidification of the clipboard, it became obvious that the method
:php:`printContentFromTab()` doesn't describe the method correctly anymore. So it has been
renamed to :php:`getContentFromTab()`.


Impact
======

This is a public method, so it could be that some unknown extension calls the old
function. But as no TER extension or the core itself calls the method, no deprecation
is needed.


Affected Installations
======================

Every extension that calls :php:`Clipboard->printContentFromTab()`.


Migration
=========

Change the call from :php:`Clipboard->printContentFromTab()` to :php:`Clipboard->getContentFromTab()`.

.. index:: Backend, PHP-API
