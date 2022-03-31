.. include:: /Includes.rst.txt

========================================================================
Feature: #89010 - Introduce Site Configuration for Distribution Packages
========================================================================

See :issue:`89010`

Description
===========

Distributions or site packages are designed to deliver a full blown TYPO3 instance with all necessary data and assets
to have a functional installation after the package has been activated.
The import of a distribution can now ship the config file (or many, if this is required).

Similar to assets, that are moved to :file:`fileadmin` ready for use, site configurations are moved into the config folder.

Impact
======

Distributions can now ship their own site configuration files.

Example:
--------

Into the distribution package :file:`Initialisation/Site` folder, put a folder with the site identifier as name, containing the
:file:`config.yaml`.
Each folder will be moved into the target position upon extension activation.

If a folder with the same name already exists, the file will *not* be overridden. In this case no change is made to the existing configuration.


.. index:: PHP-API, ext:core
