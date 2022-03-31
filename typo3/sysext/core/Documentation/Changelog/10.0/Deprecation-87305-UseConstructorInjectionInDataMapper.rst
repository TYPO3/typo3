.. include:: /Includes.rst.txt

=============================================================
Deprecation: #87305 - Use constructor injection in DataMapper
=============================================================

See :issue:`87305`

Description
===========

The 8th argument (:php:`\TYPO3\CMS\Extbase\Persistence\QueryInterface`) of method
:php:`\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper->__construct` has been marked as deprecated.


Impact
======

Instantiating objects along with that argument will trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

All installations that create instances of the class :php:`\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper` while providing the 8th argument.


Migration
=========

Instantiate the object without the 8th argument and use :php:`\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper->setQuery` if needed.

.. index:: PHP-API, FullyScanned, ext:extbase
