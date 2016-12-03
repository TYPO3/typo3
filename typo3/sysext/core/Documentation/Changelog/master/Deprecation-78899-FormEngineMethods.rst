.. include:: ../../Includes.txt

========================================
Deprecation: #78899 - FormEngine Methods
========================================

See :issue:`78899`

Description
===========

The following methods have been deprecated:

* :code:`TYPO3\CMS\Core\Database\RelationHandler->readyForInterface()`
* :code:`TYPO3\CMS\Backend\Form\FormDataProvider->sanitizeMaxItems()`


Impact
======

Using above methods will throw a deprecation warning.


Affected Installations
======================

Extensions using above methods.


Migration
=========

:code:`sanitizeMaxItems()` has been merged into calling methods using a default value
and sanitizing with :code:`MathUtility::forceIntegerInRange()`.
:code:`readyForInterface()` has been substituted with the easier to parse
method :code:`getResolvedItemArray()`.

Extensions using above methods should consider switching to those variants, too.


.. index:: Backend, PHP-API