.. include:: /Includes.rst.txt

.. _important-97411:

==========================================================================
Important: #97411 - Align SystemEnvironment checks to changed requirements
==========================================================================

See :issue:`97411`

Description
===========

With :issue:`96553` the minimum supported PHP version and supported database
products and versions have been changed. SystemEnvironment checks and reports
have been aligned to reflect these changed requirements.

* check for MySQL version 8.0.0 or newer
* check for MariaDB version 10.3.0 or newer
* check for PostgreSQL version 10.0 or newer
* removed Microsoft SQL Server checks and reports
* adjusted PHP Version check

.. index:: Database, ext:install
