.. include:: /Includes.rst.txt

.. _deprecation-102763-1706358913:

==========================================
Deprecation: #102763 - Extbase HashService
==========================================

See :issue:`102763`

Description
===========

Internal class :php:`\TYPO3\CMS\Extbase\Security\Cryptography\HashService`
is deprecated in favor of :php:`\TYPO3\CMS\Core\Crypto\HashService`,
which requires an additional secret to prevent re-using generated hashes in
different contexts.


Impact
======

Using class :php:`\TYPO3\CMS\Extbase\Security\Cryptography\HashService` will
trigger a PHP deprecation warning.


Affected installations
======================

TYPO3 installations with custom extensions using
:php:`\TYPO3\CMS\Extbase\Security\Cryptography\HashService`.


Migration
=========

Class :php:`\TYPO3\CMS\Core\Crypto\HashService` must be used to migrate.

Before
------

.. code-block:: php

    $hashService = new \TYPO3\CMS\Extbase\Security\Cryptography\HashService();

    $generatedHash = $hashService->generateHmac('123');
    $isValidHash = $hashService->validateHmac('123', $generatedHash);

    $stringWithAppendedHash = $hashService->appendHmac('123');
    $validatedStringWithHashRemoved = $hashService->validateAndStripHmac($stringWithAppendedHash);

After
-----

.. code-block:: php

    $hashService = new \TYPO3\CMS\Core\Crypto\HashService();

    $generatedHash = $hashService->hmac('123', 'myAdditionalSecret');
    $isValidHash = $hashService->validateHmac('123', 'myAdditionalSecret', $generatedHash);

    $stringWithAppendedHash = $hashService->appendHmac('123', 'myAdditionalSecret');
    $validatedStringWithHashRemoved = $hashService->validateAndStripHmac($stringWithAppendedHash, 'myAdditionalSecret');

Note, :php:`$additionalSecret` string must be unique per
context, so hashes for the same input are different depending on scope.

.. index:: PHP-API, FullyScanned, ext:extbase
