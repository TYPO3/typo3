.. include:: /Includes.rst.txt

.. _feature-98528-1674126393:

===========================================================
Feature: #98528 - New file location for ENABLE_INSTALL_TOOL
===========================================================

See :issue:`98528`

Description
===========

To access the standalone Install Tool, the file :php:`typo3conf/ENABLE_INSTALL_TOOL` needs to be created. With TYPO3 v12, the location of this file has been changed.

For composer-based installations the following file paths are checked:
* :file:`var/transient/ENABLE_INSTALL_TOOL`
* :file:`config/ENABLE_INSTALL_TOOL`

For non-composer-based installations the following file paths are checked:
* :file:`typo3temp/var/transient/ENABLE_INSTALL_TOOL`
* :file:`typo3conf/ENABLE_INSTALL_TOOL`

Using the previous known path :php:`typo3conf/ENABLE_INSTALL_TOOL`is still possible.


Impact
======

Especially for composer-based installation this change allows to completely drop the usage of the directory :php:`typo3conf/`.

Don't forget to add the new paths to your :php:`.gitignore`file to avoid deploying this file to production environments.

.. index:: Backend, ext:install
