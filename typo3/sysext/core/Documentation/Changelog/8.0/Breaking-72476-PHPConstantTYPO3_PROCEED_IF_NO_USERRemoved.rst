
.. include:: /Includes.rst.txt

================================================================
Breaking: #72476 - PHP Constant TYPO3_PROCEED_IF_NO_USER removed
================================================================

See :issue:`72476`

Description
===========

The PHP constant `TYPO3_PROCEED_IF_NO_USER` has been removed.


Impact
======

Any checks on this constant will result in a fatal PHP error.

Any definition in custom entry-scripts of extensions will have no effect anymore.


Affected Installations
======================

Installations with custom entry-points for the TYPO3 Backend.


Migration
=========

Use a custom RequestHandler, the Backend Routing, AJAX Registration or the Module Configuration for skipping the user authentication when necessary.

.. index:: PHP-API
