
.. include:: /Includes.rst.txt

================================================
Feature: #67765 - Introduce TypoLinkCodecService
================================================

See :issue:`67765`

Description
===========

The new `TypoLinkCodecService` class helps to simplify encoding and decoding of TypoLink strings.

A given TypoLink string can be passed to the `decode` method, which will return an associative array with the decoded parts.
The `encode` method takes care of assembling a valid TypoLink string for an array of TypoLink parts.

The encoding uses proper quoting and escaping, which allows safe usage of characters like `"\<space>`.


.. index:: PHP-API, Frontend, Backend
