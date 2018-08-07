.. include:: ../../Includes.txt

======================================================
Breaking: #79025 - Extract testing framework for TYPO3
======================================================

See :issue:`79025`

Description
===========

Since the :file:`.gitattributes` export change, a lot of base test classes for writing own tests are missing in distribution builds.
To get a sustainable future-proof solution, the TYPO3 core testing framework will be extracted to an own component.


Impact
======

All test classes that are considered as part of the TYPO3 core testing framework are moved to components/testing_framework and
will in the long run be released as an own package that can be required for dev environments.

Moving the classes results in changed namespaces. To ensure compatibility with earlier TYPO3 versions all classes that
were previously available in distribution (non-source) installations are additionally provided by their old namespace names
and will be provided for 8 LTS.


Affected Installations
======================

All installations using core testing components as base.


Migration
=========

Change the namespace from :php:`TYPO3\CMS\Core\Tests` to :php:`TYPO3\TestingFramework\Core` or in case of the xml fixtures the corresponding file path.

If you need to ensure compatibility with multiple TYPO3 versions, use the base test classes with their old names.

.. index:: PHP-API
