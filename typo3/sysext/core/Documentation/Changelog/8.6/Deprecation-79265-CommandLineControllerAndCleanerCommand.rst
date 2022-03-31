.. include:: /Includes.rst.txt

===============================================================
Deprecation: #79265 - CommandLineController and Cleaner Command
===============================================================

See :issue:`79265`

Description
===========

The following PHP classes for using CLI commands without Extbase Command Controllers or native Symfony Commands which
were introduced in TYPO3 v4 have been marked as deprecated:

* `TYPO3\CMS\Core\Controller\CommandLineController`
* `TYPO3\CMS\Lowlevel\CleanerCommand`


Impact
======

Instantiating any of the PHP classes above will trigger a deprecation log entry.


Affected Installations
======================

TYPO3 instances with extensions that use the old command line controllers or cleaner commands.


Migration
=========

Use native Symfony Commands or Extbase Command Controller logic instead for creating CLI-based functionality.

.. index:: CLI, ext:lowlevel, ext:extbase
