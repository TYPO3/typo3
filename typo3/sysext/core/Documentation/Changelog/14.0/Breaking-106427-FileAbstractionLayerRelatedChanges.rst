..  include:: /Includes.rst.txt

..  _breaking-106427-1742911405:

==========================================================
Breaking: #106427 - File Abstraction Layer related changes
==========================================================

See :issue:`106427`

Description
===========

In TYPO3 v14, the PHP code API for the File Abstraction Layer (FAL) has undergone some major
changes, which might affect extension authors:

- Most PHP code from FAL is now strongly typed by PHP native typing system


Impact
======

Calling the PHP classes and methods from File Abstraction Layer directly might result
in fatal PHP errors due to specific types required as method arguments.


Affected installations
======================

TYPO3 installations with third-party extensions that have used FAL API in a non-documented
way.


Migration
=========

Ensure to hand in or expect proper PHP types when using or extending FAL API.

..  index:: FAL, PHP-API, NotScanned, ext:core