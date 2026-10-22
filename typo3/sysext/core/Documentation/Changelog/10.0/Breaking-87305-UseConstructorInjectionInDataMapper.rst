.. include:: /Includes.rst.txt

.. _breaking-87305:

==========================================================
Breaking: #87305 - Use constructor injection in DataMapper
==========================================================

See :issue:`87305`

Description
===========

Class :php:`\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper` does no longer use setter injection. Instead, constructor injection is used.


Impact
======

The method signature of the constructor changed. This means:

- The amount of constructor arguments increased
- The order of arguments possibly changed


Affected Installations
======================

All installations that create instances of the class :php:`\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper` using :php-short:`\TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance` or :php-short:`\TYPO3\CMS\Extbase\Object\ObjectManager->get`.


Migration
=========

If possible, do not create instances yourself. Avoid :php-short:`\TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance` and :php-short:`\TYPO3\CMS\Extbase\Object\ObjectManager->get`. Instead use dependency injection, preferably constructor injection:

.. code-block:: php

   public function __constructor(\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper $object)
   {
       $this->property = $object;
   }

If dependency injection is not possible, check the dependencies and instantiate objects via the object manager:

.. code-block:: php

   $object = $objectManager->get(
       \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper::class,
       $objectManager->get(\TYPO3\CMS\Extbase\Reflection\ReflectionService::class),
       // ...
   );

.. index:: PHP-API, FullyScanned, ext:extbase
