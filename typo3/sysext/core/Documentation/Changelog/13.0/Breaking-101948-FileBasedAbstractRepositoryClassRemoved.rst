.. include:: /Includes.rst.txt

.. _breaking-101948-1695118827:

===============================================================
Breaking: #101948 - File-based AbstractRepository class removed
===============================================================

See :issue:`101948`

Description
===========

When the base architecture of File Abstraction Layer (FAL) was introduced in
TYPO3 v6.0, various functionality was based on concepts based on Extbase's
architecture. Some concepts never flourished. One of them being the
:php:`\TYPO3\CMS\Core\Resource\AbstractRepository` class from FAL.

This PHP class served as a basis for 2 PHP classes,
:php:`\TYPO3\CMS\Core\Resource\FileRepository` and
:php:`\TYPO3\CMS\Core\Resource\ProcessedFileRepository`.

Nowadays, it is obvious that some decisions in this area were not useful:

1. The coupling to Extbase's repository architecture does not work out, as the
manual database queries that return objects should not be bound to Extbase's
QueryRestrictions.

These never worked and were never implemented in the mentioned repository
classes from FAL.

It becomes abundantly clear that the concepts do not match by looking at the
:php:`AbstractRepository` class which even had exceptions for methods that were
not compatible with Extbase.

2. The concept of inheritance did not work out for Dependency Injection
introduced in TYPO3 v10, and with PHP 8.x which reveals various typing problems
that arose around :php:`AbstractRepository`.

:php:`AbstractRepository` is thus removed, and the implementing classes do not
extend from this class anymore, as they only include the methods required for
their purpose, and are now completely strictly typed.

Additionaly :php:`FileRepository` has been cleaned up by removing
:php:`findFileReferenceByUid()` as it is only a wrapper to 
:php:`ResourceFactory::getFileReferenceObject()`


Impact
======

Code that uses the three classes in a third-party extension might fail as the
implementing PHP repositories :php:`FileRepository` and
:php:`ProcessedFileRepository` have only necessary methods available.

PHP extensions that derive from the :php:`AbstractRepository` will stop working.

Code that used :php:`FileRepository::findFileReferenceByUid()` will break.


Affected installations
======================

As all three PHP classes are low-level in the FAL API, the impact for regular
installations will be rather low. Third-party extensions that extend from the
:php:`AbstractRepository` of FAL, which is a wild use-case will stop working. It is
safe to say, that only edge-case extensions that worked with the FAL API might
be affected, but regular installations will see no difference.


Migration
=========

Only extension authors working with the low-level API of File Abstraction Layer
would need to adapt their code to be type-safe. Extensions that extend from the
:php:`AbstractRepository` class of FAL should implement the necessary methods
themselves and remove the dependency from :php:`AbstractRepository`.

It is highly recommended to not use any of these classes, but rather stick
to high-level API of FAL, such as :php:`ResourceFactory`, :php:`File`
or :php:`ResourceStorage`.

Replace calls to :php:`FileRepository::findFileReferenceByUid()` in this manner:

.. code-block:: php

    $fileRepository = GeneralUtility::makeInstance(FileRepository::class);
    $reference = $fileRepository->findFileReferenceByUid($referenceUid);

With code like that:

.. code-block:: php

   $resourceFactory = GeneralUtility::makeInstance(ResourceFactory::class);
   $reference = $resourceFactory->getFileReferenceObject($referenceUid);

.. index:: FAL, PHP-API, PartiallyScanned, ext:core
