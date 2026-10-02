..  include:: /Includes.rst.txt

..  _feature-110873-1790859108:

==================================================================
Feature: #110873 - Upload files by pasting them from the clipboard
==================================================================

See :issue:`110873` and :issue:`110881`

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

Files can also be pasted into a file field of a record, such as the
:guilabel:`Images` field of a content element, both in the editing form and in
the contextual record edit. The files are uploaded to the file field the editor
clicked into or focused last. Without such an interaction, they are uploaded to
the file field if only one is visible. If several file fields are visible, the
editor selects the field to add the files to in a modal. The allowed file
extensions of the field apply, and the uploaded files are added to the field.

While files are dragged over a record, each of its file fields is covered by
its dropzone, as in the :guilabel:`Media` module, so the files can be dropped
anywhere onto the field instead of onto a small bar below it.

Impact
======

Files can be uploaded in the :guilabel:`Media` module and added to file fields
of records with :kbd:`Cmd+V` or :kbd:`Ctrl+V` without dragging them from another
window.

..  index:: Backend, FAL, ext:backend, ext:filelist
