
.. include:: /Includes.rst.txt

===========================================================
Feature: #69119 - Add a basic search to the filelist module
===========================================================

See :issue:`69119`

Description
===========

A basic recursive file search by file name has been added to be able to search
for files in the file list module like it was possible with EXT:dam.

The search happens recursively from the currently chosen folder in the folder
tree. This way it is possible to search whole mount points or just single folders
with a lot of files.

The search results will be displayed similar to the regular file list although
some features of the regular list view are missing. There is no possibility to
order the search results yet. Also the buttons for localization and clipboard
commands are missing in this first implementation. Regular file command links
like editing, renaming and deleting are already implemented. By default the
search results are ordered by the file identifier, i.e. the file path ascending
from A-Z.


.. index:: Backend, FAL
