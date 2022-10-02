.. include:: /Includes.rst.txt

.. _important-97462:

=================================================
Important: #97462 - Removed MSSQL supportive code
=================================================

See :issue:`97462`

Description
===========

Support for Microsoft SQL Server (MSSQL) has been dropped as supported database
system with :issue:`96553`.

Therefore supportive code for MSSQL has been removed from Core, additionally to
:doc:`Align SystemEnvironment checks to changed requirements <../12.0/Important-97411-AlignSystemEnvironmentChecksToChangedRequirements>`.

* special handling for MSSQL platform/driver has been remove on several places
* removed internal doctrine/dbal facade classes

.. note::

    MSSQL could not be selected during installation, but be configured manually
    through the database configuration. This does not work anymore, and regarding
    the removal of special handling code it would not be reliable. Migrate to
    one of the supported database system and version before upgrading to TYPO3 v12.

.. index:: Database, ext:core
