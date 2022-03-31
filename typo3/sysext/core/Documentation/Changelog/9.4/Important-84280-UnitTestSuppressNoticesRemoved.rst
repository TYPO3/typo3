.. include:: /Includes.rst.txt

=====================================================
Important: #84280 - Unit test suppressNotices removed
=====================================================

See :issue:`84280`

Description
===========

The property :php:`$suppressNotices` available for unit tests extending class
:php:`UnitTestCase` has been removed. Unit tests that trigger :php:`E_NOTICE`
level errors will now fail.

The property has been introduced with TYPO3 v9.2 and has been removed with v9.4
after no core unit tests used that flag anymore.

If extensions use the typo3/testing-framework for testing, they now may have
to fix their tests or system under test to not throw notices, either.

.. index:: PHP-API, FullyScanned
