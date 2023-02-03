.. include:: /Includes.rst.txt

.. _feature-98528-1674126393:

===========================================================
Feature: #98528 - New file location for ENABLE_INSTALL_TOOL
===========================================================

See :issue:`98528`

Description
===========

To access the standalone :guilabel:`Install Tool`, the file
:file:`typo3conf/ENABLE_INSTALL_TOOL` needed to be created.
With TYPO3 v12, the location of this file has been changed.

For Composer-based installations the following file paths are checked:

*   :file:`var/transient/ENABLE_INSTALL_TOOL`
*   :file:`config/ENABLE_INSTALL_TOOL`

For legacy installations the following file paths are checked:

*   :file:`typo3temp/var/transient/ENABLE_INSTALL_TOOL`
*   :file:`typo3conf/ENABLE_INSTALL_TOOL`

Using the previous known path :file:`typo3conf/ENABLE_INSTALL_TOOL` is
still possible.


Impact
======

Especially for Composer-based installation this change allows to completely
drop the usage of the :file:`typo3conf/` directory.

Add the new paths to your :php:`.gitignore` file to avoid deploying this file to
production environments.

.. index:: Backend, ext:install
