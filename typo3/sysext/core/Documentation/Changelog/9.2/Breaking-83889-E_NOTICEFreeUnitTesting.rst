.. include:: ../../Includes.txt

=============================================
Breaking: #83889 - E_NOTICE free unit testing
=============================================

See :issue:`83889`

Description
===========

Writing unit tests and executing them using the `typo3/testing-framework`
now requires the system under test to no longer raise PHP :php:`E_NOTICE`
level errors, or the test fails.


Impact
======

This is a first step towards a PHP notice free TYPO3 core.


Affected Installations
======================

Extensions that use the TYPO3 v9 compatible `typo3/testing-framework`
package in a version >= 3.0.0 may see failing unit tests if the tested
class raises `E_NOTICE` errors.


Migration
=========

The best solution is to fix the unit test and/or the system under test
to no longer raise `E_NOTICE` level PHP errors.

In a transition phase, a single unit test case file can set a
property to still suppress E_NOTICE warnings:

.. code-block:: php

    class FooTest extends \TYPO3\TestingFramework\Core\Unit\UnitTestCase
    {
        /**
         * Subject is not notice free, disable E_NOTICES
         */
        protected static $suppressNotices = true;
    }

Note that this property is deprecated and will be removed from
:php:`UnitTestCase` as soon as TYPO3 core does not need it
anymore.

.. index:: PHP-API, FullyScanned
