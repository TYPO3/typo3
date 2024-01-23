.. include:: /Includes.rst.txt

.. _breaking-101133-1687875354:

===========================================================
Breaking: #101133 - IconFactory->getIcon() signature change
===========================================================

See :issue:`101133`

Description
===========

The public method :php:`getIcon()` in :php:`\TYPO3\CMS\Core\Imaging\IconFactory`
has changed its 4th parameter, in order to prepare the removal
of class :php:`\TYPO3\CMS\Core\Type\Icon\IconState`.

Impact
======

Custom extensions extending the :php:`getIcon()` method of class
:php:`\TYPO3\CMS\Core\Imaging\IconFactory` not having the same signature
will fail with a PHP fatal error.

Affected installations
======================

Custom extensions extending the :php:`getIcon()` method from class
:php:`\TYPO3\CMS\Core\Imaging\IconFactory`.

Migration
=========

Adapt the 4th parameter of :php:`getIcon()` to be of type
:php:`\TYPO3\CMS\Core\Type\Icon\IconState|IconState $state = null`

In addition, adapt the code in the body of the method.

.. index:: Backend, NotScanned, ext:core
