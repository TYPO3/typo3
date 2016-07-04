============================================================================
Deprecation: #75904 - Category Model has icon property but no database field
============================================================================

Description
===========

Methods :php:``\TYPO3\CMS\Extbase\Domain\Model\Category::getIcon`` and
:php:``\TYPO3\CMS\Extbase\Domain\Model\Category::setIcon`` have been marked as deprecated.


Impact
======

Using the methods will trigger a deprecation log entry.


Affected Installations
======================

Instances with custom extensions that use these methods.


Migration
=========

Implement the methods by yourself.
