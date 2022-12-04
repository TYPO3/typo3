.. include:: /Includes.rst.txt

.. _feature-98957-1667131640:

======================================================================
Feature: #98957 - Respect write-protected settings.php in Install Tool
======================================================================

See :issue:`98957`

Description
===========

The :guilabel:`Admin Tools > Settings` backend module now informs a system
maintainer if the :file:`system/settings.php` file is write-protected.

This allows to make the settings file read-only after deployments.


Impact
======

An info box is rendered in the module and each submodule, informing the system
maintainer that the :file:`system/settings.php` file is write-protected.
In that case, all input fields are disabled and the submit buttons are not
available.

.. index:: LocalConfiguration, ext:install
