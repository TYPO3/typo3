.. include:: /Includes.rst.txt

.. _feature-99155-1669116236:

===========================================
Feature: #99155 - Add tile view to filelist
===========================================

See :issue:`99155`

Description
===========

The listing of resources in a table has some specific use
cases, but it is hard for the editor to get an overview of file
resources since the thumbnails are small.

To provide a better overview of assets in the :guilabel:`Filelist` module we
are introducing a tile view with bigger thumbnails and reduced
meta information. The user can now choose the desired display
mode of the assets depending on the current requirements.

The user can change the view mode in the view menu in the
module menu bar. By default the tile view is enabled for
new and existing users.

TYPO3 will remember the choice of the user.


Impact
======

The user can now change the display mode of resources
in the :guilabel:`Filelist` module.

.. index:: Backend, ext:filelist
