..  include:: /Includes.rst.txt

..  _feature-106752-1752515067:

=========================================================================
Feature: #106752 - Add password hashing option to SaveToDatabase finisher
=========================================================================

See :issue:`106752`

Description
===========

This change introduces a new hashed option for the SaveToDatabase finisher in EXT:form.
When saving data to a table, setting `hashed: true` on a field causes the value to be hashed using
the default FE password hashing mechanism before storage.

This ensures secure handling of passwords and avoids saving them in plain text.

Example usage in form definition:

..  code-block:: yaml
    - identifier: SaveToDatabase
      options:
        table: 'fe_users'
        elements:
          password:
            mapOnDatabaseColumn: 'password'
            hashed: true


Impact
======

Integrators can now store passwords securely out of the box with EXT:form.

..  index:: ext:form