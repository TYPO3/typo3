
.. include:: ../../Includes.txt

=====================================================================
Important: #69137 - Link Wizard popup width and height fields removed
=====================================================================

See :issue:`69137`

Description
===========

Opening links in popups with width/height definition is a very rare usecase nowadays.

For user convenience and to have less clutter in the UI, the width and height
fields have been removed.
The editor is not able to select a width and height anymore, it can still be entered manually though.

The RTE option `buttons.link.popupSelector.disabled` has no effect anymore.
