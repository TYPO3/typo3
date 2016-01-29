.. include:: ../../Includes.txt

==================================================================================
Breaking: #73016 - Renaming of Clipboard->printContentFromTab to getContentFromTab
==================================================================================

See :issue:`73016`

Description
===========

During the fluidification of the clipboard, it became obvious that the method name printContentFromTab doesn't describe the function of the method correctly anymore. So it has been renamed into getContentFromTab.


Impact
======

This is a public method, so it could be the case that some unknown extension calls the old function. But as no TER extension or the core itself calls the method, no deprecation was needed.


Affected Installations
======================

Every extension that calls Clipboard->printContentFromTab().


Migration
=========

Change the call from Clipboard->printContentFromTab to Clipboard->getContentFromTab.

.. index:: Backend