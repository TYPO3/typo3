.. include:: /Includes.rst.txt

.. _feature-99191-1669906308:

===========================================
Feature: #99191 - Create folders via modals
===========================================

See :issue:`99191`

Description
===========

Creation of new folders in the :guilabel:`File > Filelist` module has been
improved. Instead of a new window does the :guilabel:`Create Folder` button
in the docheader, as well as the corresponding option in the context menu,
now open a modal window, offering a form to create a folder. The modal window
also contains the folder tree to select the parent folder. To allow editors
creating folders sequentially, the modal is not automatically closed.

Impact
======

With the new modal window, backend users are able to create folders in an
improved way, since they do not lose focus of the current view anymore.
Additionally, the parent folder can easily be changed inside the modal window,
which allows to create folders for different levels without leaving the form.
After closing the modal window, the :guilabel:`File > Filelist` module does
always automatically reload to instantly display the latest changes.

.. index:: Backend, ext:filelist
