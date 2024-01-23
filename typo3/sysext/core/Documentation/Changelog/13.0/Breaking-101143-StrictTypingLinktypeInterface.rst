.. include:: /Includes.rst.txt

.. _breaking-LinktypeInterface-1687413563:

======================================================
Breaking: #101143 - Strict typing in LinktypeInterface
======================================================

See :issue:`101143`

Description
===========

All methods in the interface :php:`\TYPO3\CMS\Linkvalidator\Linktype\LinktypeInterface`
are now strictly typed.

Impact
======

Classes implementing the interface must now ensure all methods are strictly typed.

Affected installations
======================

Custom classes implementing :php:`\TYPO3\CMS\Linkvalidator\Linktype\LinktypeInterface`

Migration
=========

Ensure that classes that implement :php:`\TYPO3\CMS\Linkvalidator\Linktype\LinktypeInterface`
have the following signatures:

.. code-block:: php

    public function checkLink(string $url, array $softRefEntry, LinkAnalyzer $reference): bool;
    public function fetchType(array $value, string $type, string $key): string;
    public function getErrorParams(): array;
    public function getBrokenUrl(array $row): string;
    public function getErrorMessage(array $errorParams): string;

.. index:: Backend, NotScanned, ext:linkvalidator
