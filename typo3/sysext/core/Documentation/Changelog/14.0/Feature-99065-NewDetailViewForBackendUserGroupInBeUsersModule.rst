..  include:: /Includes.rst.txt

..  _feature-99065-1755113833:

=======================================================================
Feature: #99065 - Detail view for backend user groups in 'Users' module
=======================================================================

See :issue:`99065`

Description
===========

The :guilabel:`Administration > Users` module has been extended with a new detail view
for backend user groups, complementing the existing detail view for individual
backend users. This comprehensive view provides administrators with complete
visibility into backend user group configurations and their calculated
properties.

The detail view displays:

-   Basic information about the backend user group (title, description, and so on)
-   All assigned subgroups and the full inheritance chain
-   A complete overview of permissions and access rights from the group and
    all inherited subgroups
-   Calculated and processed TSconfig settings showing the final effective
    configuration
-   Database mount points and file mount access permissions
-   Module access permissions and workspace restrictions

..  note::
    The "Administration > Users" module was called "System > Backend Users"
    before TYPO3 v14, see also
    `Feature: #107628 - Improved backend module naming and structure <https://docs.typo3.org/permalink/changelog:feature-107628-1729026000>`_.

Impact
======

TYPO3 administrators can now efficiently analyze backend user group
configurations without manually tracing through complex inheritance structures.
This enhanced visibility simplifies permission troubleshooting, security
auditing, and group management by providing a consolidated view of all
calculated permissions and settings in one place, similar to the existing
backend user detail functionality.

..  index:: Backend, ext:beuser
