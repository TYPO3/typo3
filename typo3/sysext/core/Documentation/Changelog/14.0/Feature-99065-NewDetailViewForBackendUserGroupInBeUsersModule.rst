.. include:: /Includes.rst.txt

.. _feature-99065-1755113833:

===============================================================================
Feature: #99065 - Detail view for backend user groups in 'Backend Users' module
===============================================================================

See :issue:`99065`

Description
===========

The :guilabel:`Backend Users` module has been extended with a new detail view
for backend user groups, complementing the existing detail view for individual
backend users. This comprehensive view provides administrators with complete
visibility into backend user group configurations and their calculated properties.

The detail view displays:

- Basic information about the backend user group (title, description, etc.)
- All assigned subgroups and inheritance chain
- Complete overview of permissions and access rights from the group and all inherited subgroups
- Calculated and processed TSconfig settings showing the final effective configuration
- Database mount points and file mount access permissions
- Module access permissions and workspace restrictions

Impact
======

TYPO3 administrators can now efficiently analyze backend user group configurations
without manually tracing through complex inheritance structures. This enhanced
visibility simplifies permission troubleshooting, security auditing, and group
management by providing a consolidated view of all calculated permissions and
settings in one place, similar to the existing backend user detail functionality.

.. index:: Backend, ext:beuser
