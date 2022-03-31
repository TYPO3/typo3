.. include:: /Includes.rst.txt

============================================
Feature: #86076 - New API for UpgradeWizards
============================================

See :issue:`86076`

Description
===========

Up until now the UpgradeWizards were based on an abstract class :php:`AbstractUpdate`.
An interface based API has been introduced. This API is currently internal and will be refined by
using it in the core update wizards. Once it is stabilized it will be made public and official.

Currently the API contains of

- :php:`UpgradeWizardInterface` - main interface for UpgradeWizards
- :php:`RepeatableInterface` - semantic interface to denote wizards that can be repeated
- :php:`ChattyInterface` - interface for wizards generating output
- :php:`ConfirmableInterface` - interface for wizards that need user confirmation


Impact
======

The new interface classes are available in the core and will be used in the core update
wizards as a next step. They should not yet be used by third parties.

.. index:: PHP-API, ext:install
