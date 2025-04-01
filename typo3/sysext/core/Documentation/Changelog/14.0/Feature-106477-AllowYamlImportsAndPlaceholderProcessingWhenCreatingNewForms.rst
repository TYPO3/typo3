..  include:: /Includes.rst.txt

..  _feature-106477-1753393923:

========================================================================================
Feature: #106477 - Allow YAML imports and placeholder processing when creating new forms
========================================================================================

See :issue:`106477`

Description
===========

The :php-short:`\TYPO3\CMS\Form\Controller\FormManagerController` now uses
the :php:`YamlFileLoader` when creating new forms from templates. This change
enables the processing of placeholders within template files, such as
environment variables in the format :yaml:`%env(ENV_NAME)%`, as well as
imports of other YAML files.

This enhancement allows for more flexible form templates that can adapt to
different environments through environment variable substitution and include
other YAML files.

Impact
======

Form templates can now contain environment variable placeholders using the
:yaml:`%env(ENV_NAME)%` syntax and import other YAML files, which will be
resolved when creating new forms from these templates.

..  index:: Backend, ext:form
