.. include:: /Includes.rst.txt

===================================================================
Important: #95298 - Fluid ViewHelpers will be declared final in v12
===================================================================

See :issue:`95298`

Description
===========

This is a notice for an upcoming change in TYPO3 v12:

All Fluid ViewHelper classes delivered by Core extensions will be declared
:php:`final` in TYPO3 v12, third party extensions can no longer extend them
with own variants.

The Core takes this step to clarify that single ViewHelpers are not part of the
PHP API, their internal handling may change any time, which is not considered
breaking. Fluid delivers a series of abstract classes to provide base functionality
for common ViewHelper needs. Those can be used by third party ViewHelpers if
not marked :php:`@internal`. TYPO3 v12 will fine-tune these abstracts and may
extract specific ViewHelper code to abstracts if the code is generally useful
for extension developers with own view-helpers.

Using ViewHelpers provided by Core extensions in Fluid templates is of course
fine as long as they are not marked :php:`@internal`. Arguments to casual ViewHelpers
are considered API and are subject of the general Core deprecation strategy. In
general, the base extensions `EXT:fluid`, `EXT:core`, `EXT:frontend` and `EXT:backend`
deliver various general purpose ViewHelpers that can be used, while specific extensions
like `EXT:beuser` add :php:`@internal` ViewHelpers that should not be used in own templates.

Developers are encouraged to adapt own ViewHelpers towards this change with
TYPO3 v11 compatible extensions already, it will simplify compatibility with TYPO3 v12 later.

.. index:: Fluid, PHP-API, ext:fluid
