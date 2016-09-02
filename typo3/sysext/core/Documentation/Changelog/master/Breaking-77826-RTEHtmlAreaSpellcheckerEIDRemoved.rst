=======================================================
Breaking: #77826 - RTEHtmlArea Spellchecker eID removed
=======================================================

Description
===========

The RTEHtmlArea eID (rtehtmlarea_spellchecker) for using dynamic spellchecking was removed.

The RTE html area uses the Backend Routing API for Backend and Frontend Editing.


Impact
======

Calling the eID script will result in a 404 error.


Affected Installations
======================

Installations which use the eID `rtehtmlarea_spellchecker` in a custom extension.