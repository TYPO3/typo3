..  include:: /Includes.rst.txt

..  _feature-110873-1790859108:

====================================================
Feature: #110873 - Paste files into the Media module
====================================================

See :issue:`110873`

Description
===========

Files can now be uploaded to the folder shown in the :guilabel:`Media` module
by pasting them from the clipboard, in addition to dragging them onto the
module or choosing them with the upload button.

This covers files copied in the file manager of the operating system as well as
image data, such as a screenshot taken to the clipboard. A pasted file runs
through the same upload as a dropped one, including the dialog asking what to do
with a file that already exists in the folder.

Pasting into a form field of the module, such as the search field, keeps its
usual behaviour and does not upload anything.

Impact
======

Files can be uploaded in the :guilabel:`Media` module with :kbd:`Cmd+V` or
:kbd:`Ctrl+V` without dragging them from another window.

..  index:: Backend, FAL, ext:filelist
