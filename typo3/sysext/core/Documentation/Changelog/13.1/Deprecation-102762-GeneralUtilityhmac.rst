.. include:: /Includes.rst.txt

.. _deprecation-102762-1710402828:

=======================================================
Deprecation: #102762 - Deprecate GeneralUtility::hmac()
=======================================================

See :issue:`102762`

Description
===========

The method :php:`\TYPO3\CMS\Core\Utility\GeneralUtility::hmac()`
has been deprecated in TYPO3 v13 and will be removed with v14 in
favor of :ref:`feature-102761-1704532036`.

Impact
======

Usage of the method will raise a deprecation level log entry in
TYPO3 v13 and a fatal error in TYPO3 v14.


Affected installations
======================

All third-party extensions using :php:`\TYPO3\CMS\Core\Utility\GeneralUtility::hmac()`.


Migration
=========

All usages of :php:`\TYPO3\CMS\Core\Utility\GeneralUtility::hmac()`
must be migrated to use the :php:`hmac()` method in the class
:php:`\TYPO3\CMS\Core\Crypto\HashService`.

Before
------

..  code-block:: php

    //use TYPO3\CMS\Core\Utility\GeneralUtility;

    $hmac = GeneralUtility::hmac('some-input', 'some-secret');

After
-----

..  code-block:: php
    :caption: Using :php:`GeneralUtility::makeInstance()`

    //use TYPO3\CMS\Core\Crypto\HashService;
    //use TYPO3\CMS\Core\Utility\GeneralUtility;

    $hashService = GeneralUtility::makeInstance(HashService::class);
    $hmac = $hashService->hmac('some-input', 'some-secret');

..  code-block:: php
    :caption: Using dependency injection

    namespace MyVendor\MyExt\Services;

    use TYPO3\CMS\Core\Crypto\HashService;
    use TYPO3\CMS\Core\Utility\GeneralUtility;

    final readonly class MyService
    {
        public function __construct(
            private HashService $hashService,
        ) {}

        public function someMethod(): void
        {
            $hmac = $this->hashService->hmac('some-input', 'some-secret');
        }
    }

If possible, use dependency injection to inject :php:`HashService` into your class.

.. index:: Backend, FullyScanned, ext:core
