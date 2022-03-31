.. include:: /Includes.rst.txt

=====================================================
Feature: #82441 - Inject logger when creating objects
=====================================================

See :issue:`82441`

Description
===========

Classes that implement :php:`\Psr\Log\LoggerAwareInterface` automatically
get a logger instance injected when a class instance is created via
:php:`GeneralUtility::makeInstance()` and :php:`ObjectManger::get()`.

For developer convenience the :php:`\Psr\Log\LoggerAwareTrait` can be used.
The trait adds a public :php:`setLogger()` and a protected :php:`$logger` property
to the class, no further code is needed to successfully implement the interface.

A minimal example looks like this (example from a test case fixture):

.. code-block:: php

    <?php
    declare(strict_types = 1);
    namespace TYPO3\CMS\Core\Tests\Unit\Utility\Fixtures;

    use Psr\Log\LoggerAwareInterface;
    use Psr\Log\LoggerAwareTrait;

    class GeneralUtilityMakeInstanceInjectLoggerFixture implements LoggerAwareInterface
    {
        use LoggerAwareTrait;
    }

.. index:: PHP-API
