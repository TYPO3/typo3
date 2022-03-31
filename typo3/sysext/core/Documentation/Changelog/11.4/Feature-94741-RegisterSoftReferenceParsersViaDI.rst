.. include:: /Includes.rst.txt

=======================================================
Feature: #94741 - Register SoftReference parsers via DI
=======================================================

See :issue:`94741`

Description
===========

Parsers for :ref:`soft references <t3coreapi:soft-references>` can now be
registered via dependency injection in the corresponding
:file:`Configuration/Services.(yaml|php)` file of your extension. This is done
by tagging your class with the new tag name :yaml:`softreference.parser` and
providing the parser key for the attribute :yaml:`parserKey`.

Example:

.. code-block:: yaml

    VENDOR\Extension\SoftReference\YourSoftReferenceParser:
      tags:
        - name: softreference.parser
          parserKey: your_key

In addition, parsers now have to implement
:php:`TYPO3\CMS\Core\DataHandling\SoftReference\SoftReferenceParserInterface`.
This interface describes the :php:`parse()` method, which is very similar to the
old method :php:`findRef()`. The difference is that :php:`$parserKey` (former
known as :php:`$spKey`) and :php:`$parameters` (former known as
:php:`$spParams`) can now be optionally set with the :php:`setParserKey()` method.
The key can be retrieved with the :php:`getParserKey()` method.

The return type has also been changed to
:php:`TYPO3\CMS\Core\DataHandling\SoftReference\SoftReferenceParserResult`.
This model holds the former result array key entries :php:`content` and
:php:`elements` as properties and has appropriate getter methods for them. It
should be created by its own factory method
:php:`SoftReferenceParserResult::create()`, which expects both above-mentioned
arguments to be provided. If the result is empty,
:php:`SoftReferenceParserResult::createWithoutMatches()` should be used instead.

Impact
======

Developers can register their user-defined soft reference parsers in their
:file:`Configuration/Services.(yaml|php)` file. In addition, parser have to
implement the new interface
:php:`TYPO3\CMS\Core\DataHandling\SoftReference\SoftReferenceParserInterface`.


Related
=======

*  :doc:`RegisterSoftReferenceParsersViaDI (Deprecation) <Deprecation-94741-RegisterSoftReferenceParsersViaDI>`
*  :doc:`SoftReferenceIndex (Deprecation) <Deprecation-94687-SoftReferenceIndex>`

.. index:: PHP-API, ext:core
