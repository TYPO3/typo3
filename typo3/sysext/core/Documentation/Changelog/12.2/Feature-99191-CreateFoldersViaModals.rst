.. include:: /Includes.rst.txt

.. _feature-99191-1669906308:

===========================================
Feature: #99191 - Create folders via modals
===========================================

See :issue:`99191`

Description
===========

The creation of new folders in the :guilabel:`File > Filelist` module has been
improved. Instead of a new window, the :guilabel:`Create Folder` button
now opens a modal window to create a folder.
Both the button in the docheader and the corresponding option in the context
menu are affected.

The modal window also contains the folder tree to select the parent folder.
To allow editors creating folders sequentially, the modal is not
automatically closed.

Impact
======

With the new modal window, backend users are able to create folders in an
improved way: They do not lose focus of the current view anymore.
Additionally, the parent folder can easily be changed inside the modal window,
which allows to create folders for different levels without leaving the form.
After closing the modal window, the :guilabel:`File > Filelist` module
automatically reloads to instantly display the latest changes.

.. index:: Backend, ext:filelist
