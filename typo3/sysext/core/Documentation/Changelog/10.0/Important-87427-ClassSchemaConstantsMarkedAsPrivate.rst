.. include:: /Includes.rst.txt

.. _important-87427:

===========================================================
Important: #87427 - ClassSchema constants marked as private
===========================================================

See :issue:`87427`

Description
===========

The constants

* :php-short:`\TYPO3\CMS\Extbase\Reflection\ClassSchema::MODELTYPE_ENTITY` and
* :php-short:`\TYPO3\CMS\Extbase\Reflection\ClassSchema::MODELTYPE_VALUEOBJECT`

have been marked as private, as they are used in :php:`\TYPO3\CMS\Extbase\Reflection\ClassSchema` only.

Since this class is marked as internal explicitly, nobody should be affected by this change.

.. index:: PHP-API, ext:extbase
