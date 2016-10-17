.. include:: ../../Includes.txt

=======================================================
Breaking: #77826 - RTEHtmlArea Spellchecker eID removed
=======================================================

See :issue:`77826`

Description
===========

The RTEHtmlArea eID (`rtehtmlarea_spellchecker`) for using dynamic spellchecking has been removed.

RTEhtmlarea uses the Backend Routing API for Backend and Frontend Editing.


Impact
======

Calling the eID script will result in a 404 error.


Affected Installations
======================

Installations which use the eID `rtehtmlarea_spellchecker` in a custom extension.

.. index:: Backend, Frontend, RTE
