
.. include:: /Includes.rst.txt

==================================
Feature: #73050 - Add a CSPRNG API
==================================

See :issue:`73050`

Description
===========

A new cryptographically secure pseudo-random number generator (CSPRNG) has been
introduced in TYPO3 core. It takes advantage of the new CSPRNG functions in PHP 7.


API overview
============

The API resides in the class :php:`\TYPO3\CMS\Core\Crypto\Random`. It provides several
methods. Here is a brief overview of the interface:

.. code-block:: php

    class Random {
        /**
         * Generates cryptographic secure pseudo-random bytes
         */
        public function generateRandomBytes($length);

        /**
         * Generates cryptographic secure pseudo-random integers
         */
        public function generateRandomInteger($min, $max);

        /**
         * Generates cryptographic secure pseudo-random hex string
         */
        public function generateRandomHexString($length);
    }


Example
-------

.. code-block:: php

    use \TYPO3\CMS\Core\Crypto\Random;
    use \TYPO3\CMS\Core\Utility\GeneralUtility;

    // Retrieving random bytes
    $someRandomString = GeneralUtility::makeInstance(Random::class)->generateRandomBytes(64);

    // Rolling the dice..
    $tossedValue = GeneralUtility::makeInstance(Random::class)->generateRandomInteger(1, 6);


Impact
======

None, you can start to use the CSPRNG in your code by now.

.. index:: PHP-API
