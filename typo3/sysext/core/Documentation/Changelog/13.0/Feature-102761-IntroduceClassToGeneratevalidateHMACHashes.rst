.. include:: /Includes.rst.txt

.. _feature-102761-1704532036:

===================================================================
Feature: #102761 - Introduce class to generate/validate HMAC hashes
===================================================================

See :issue:`102761`

Description
===========

A new class  :php:`\TYPO3\CMS\Core\Crypto\HashService` has been introduced to
enhance the security and flexibility in generating Hash-based Message
Authentication Codes (HMACs). This class combines the functionality of
:php:`GeneralUtility::hmac()` and Extbase's :php:`HashService`, while
enforcing the use of an additional, mandatory secret for HMAC generation and
HMAC string validation.


Impact
======

Using the new class  :php:`\TYPO3\CMS\Core\Crypto\HashService`, it is now
possible to mitigate the risk of HMAC reuse in unauthorized scenarios for the
same input.

.. index:: ext:core
