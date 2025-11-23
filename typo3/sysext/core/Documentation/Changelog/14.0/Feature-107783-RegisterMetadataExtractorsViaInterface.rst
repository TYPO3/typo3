..  include:: /Includes.rst.txt

..  _feature-107783-1760944786:

=============================================================
Feature: #107783 - Register metadata extractors via interface
=============================================================

See :issue:`107783`

Description
===========

Metadata extractors are service classes that are automatically executed
whenever an asset or file is added to the :abbr:`FAL (File Abstraction Layer)`
storage, or FAL indexing is executed.

Registration of metadata extractors now happens automatically when the required
interface :php-short:`\TYPO3\CMS\Core\Resource\Index\ExtractorInterface` is
implemented by the class, utilizing autoconfigure tagging provided by the
Symfony Dependency Injection framework.

No further manual registration is required.

Additionally, the class
:php-short:`\TYPO3\CMS\Core\Resource\Index\ExtractorRegistry` now uses strong
type declarations, which should not affect public consumers. The interface
remains unchanged in its type declarations.

Impact
======

Instances of
:php-short:`\TYPO3\CMS\Core\Resource\Index\ExtractorInterface` are now detected
and registered automatically.

..  index:: FAL, PHP-API, ext:core
