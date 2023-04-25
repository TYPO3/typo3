.. include:: /Includes.rst.txt

.. _important-100658-1681819486:

====================================================================================================
Important: #100658 - Drop use TSconfig options `createFoldersInEB` and `folderTree.hideCreateFolder`
====================================================================================================

See :issue:`100658`

Description
===========

The user TSconfig options :typoscript:`createFoldersInEB` and :typoscript:`folderTree.hideCreateFolder` were
used in the past to control the existence of the "Create folder" form in Element
Browser instances. With the migration of the "Create folder" view into a separate
modal used in EXT:filelist, which is based on Element Browser as well, those
options became useless and are therefore dropped.

.. index:: Backend, TSConfig, ext:backend
