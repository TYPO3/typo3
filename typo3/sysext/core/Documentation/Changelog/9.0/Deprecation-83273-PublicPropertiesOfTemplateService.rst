.. include:: /Includes.rst.txt

==========================================================
Deprecation: #83273 - Public properties of TemplateService
==========================================================

See :issue:`83273`

Description
===========

The following properties within the PHP class :php:`TYPO3\CMS\Core\TypoScript\TemplateService`
have been marked as deprecated, as they were moved from public access to protected access:

* :php:`matchAll`
* :php:`whereClause`
* :php:`debug`
* :php:`allowedPaths`
* :php:`simulationHiddenOrTime`
* :php:`nextLevel`
* :php:`rootId`
* :php:`absoluteRootLine`
* :php:`outermostRootlineIndexWithTemplate`
* :php:`rowSum`
* :php:`sitetitle`
* :php:`sectionsMatch`
* :php:`frames`
* :php:`MPmap`

They should only be accessed from within the PHP class itself.


Impact
======

Accessing any of the properties directly within PHP will trigger a deprecation warning.


Affected Installations
======================

Extensions accessing one of the previously public properties directly.


Migration
=========

Remove the PHP calls and either extend the PHP class to your own needs or avoid accessing these properties.

.. index:: Frontend, PHP-API, FullyScanned
