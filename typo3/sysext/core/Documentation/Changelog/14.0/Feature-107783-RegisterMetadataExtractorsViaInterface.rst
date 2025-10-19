..  include:: /Includes.rst.txt

..  _feature-107783-1760944786:

=============================================================
Feature: #107783 - Register Metadata extractors via Interface
=============================================================

See :issue:`107783`

Description
===========

Metadata extractors are service classes that are automatically
executed whenever an asset / file is added to the FAL storage,
or FAL indexing is executed.

Registration of Metadata extractors will happen automatically when the required interface
:php:`TYPO3\CMS\Core\Resource\Index\ExtractorInterface` is implemented by the class,
utilizing autoconfigure tagging by the Symfony Dependence Injection framework.

No further registration is necessary.

Additionally, the class :php:`TYPO3\CMS\Core\Resource\Index\ExtractorRegistry` now
uses strong type declarations, which should not affect public consumers. The interface
remains unchanged in type declarations.

Impact
======

Instances of :php:`TYPO3\CMS\Core\Resource\Index\ExtractorInterface` will be detected and registered automatically.

..  index:: FAL, PHP-API, ext:core
