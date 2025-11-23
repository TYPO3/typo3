..  include:: /Includes.rst.txt

..  _feature-106752-1752515067:

=========================================================================
Feature: #106752 - Add password hashing option to SaveToDatabase finisher
=========================================================================

See :issue:`106752`

Description
===========

A new option :yaml:`hashed` has been added to the
:php-short:`\TYPO3\CMS\Form\Finishers\SaveToDatabaseFinisher` of the system
extension :composer:`typo3/cms-form`.

When saving form data to a database table, setting :yaml:`hashed: true` for a
field causes the value to be hashed using the default frontend password hashing
mechanism before it is written to the database.

This improves security by preventing passwords from being stored in plain text.

Example usage in a form definition:

..  code-block:: yaml
    :caption: EXT:my_extension/Configuration/Form/ExampleForm.form.yaml

    - identifier: SaveToDatabase
      options:
        table: 'fe_users'
        elements:
          password:
            mapOnDatabaseColumn: 'password'
            hashed: true

Impact
======

Integrators can now ensure secure password storage when saving form data with
the :php-short:`\TYPO3\CMS\Form\Finishers\SaveToDatabaseFinisher`, without
implementing custom logic.

..  index:: ext:form
