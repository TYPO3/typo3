.. include:: ../../Includes.txt

===================================================
Breaking: #83289 - Core version 9.0 needs PHP 7.2.0
===================================================

See :issue:`83289`

Description
===========

The minimum PHP version required to run core version 9.0 has been raised from 7.0.0 to 7.2.0.


Impact
======

Version constructs of PHP 7.2 and library versions requiring this PHP platform can be used.
Hosting on platforms lower than PHP 7.2.0 is not supported.
The PHP entry points to TYPO3 will throw fatal errors if the PHP version constraint is not fulfilled.


Affected Installations
======================

Hosting a TYPO3 instance based on core version 9 may require an update of the PHP platform.


Migration
=========

Youngest TYPO3 core v7 and v8 releases support PHP 7.2. This allows upgrading the platform
in a first step and upgrading to TYPO3 v9 in a second step.

.. index:: PHP-API, NotScanned
