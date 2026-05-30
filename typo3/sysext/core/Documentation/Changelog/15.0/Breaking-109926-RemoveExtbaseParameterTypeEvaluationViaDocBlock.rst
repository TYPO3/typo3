.. include:: /Includes.rst.txt

.. _breaking-109926-1780171221:

==========================================================================
Breaking: #109926 - Removed extbase parameter type evaluation via DocBlock
==========================================================================

See :issue:`109926`

Description
===========

Extbase resolved the target type of a controller action argument (and other
reflected method parameters) from the method DocBlock when no native PHP type
declaration was given:

.. code-block:: php

   /**
    * @param \MyVendor\MyExtension\Domain\Model\MyModel $item
    */
   public function showAction($item): ResponseInterface

This fallback was deprecated with :ref:`#94115 <deprecation-94115>` in TYPO3 v11
in favor of native PHP type declarations. It has now been removed.

As part of this removal, the internal helper class
:php:`\TYPO3\CMS\Extbase\Reflection\DocBlock\Tags\Null_`, which only existed to
silence DocBlock parsing of this argument resolution, has been removed.

Impact
======

The type of a method parameter is now solely determined from its native PHP type
declaration. A parameter that relies on a :php:`@param` DocBlock tag without a
native type declaration no longer receives a type.

For controller actions this means an
:php:`\TYPO3\CMS\Extbase\Mvc\Exception\InvalidArgumentTypeException` is thrown
when the action is dispatched. In combination with a :php:`#[Validate]`
attribute, an
:php:`\TYPO3\CMS\Extbase\Validation\Exception\InvalidTypeHintException` is thrown
while the class schema is built.

Affected installations
======================

Extbase extensions with controller actions or other reflected methods that still
declare argument types via :php:`@param` DocBlock tags instead of native PHP type
declarations. Such code has emitted a deprecation since TYPO3 v11 and, for the
:php:`#[Validate]` case, already raised a runtime exception since v12.

Migration
=========

Use native PHP type declarations, available since TYPO3 v10:

.. code-block:: php

   public function showAction(\MyVendor\MyExtension\Domain\Model\MyModel $item): ResponseInterface

.. index:: PHP-API, NotScanned, ext:extbase
