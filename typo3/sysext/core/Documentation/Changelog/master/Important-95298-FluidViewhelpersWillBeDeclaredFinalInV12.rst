.. include:: ../../Includes.txt

===================================================================
Important: #95298 - Fluid Viewhelpers will be declared final in v12
===================================================================

See :issue:`95298`

Description
===========

This is a notice for an upcoming change in TYPO3 v12:

All Fluid Viewhelper classes delivered by core extensions will be declared
:php:`final` in TYPO3 v12, third party extensions can no longer extend them
with own variants.

The core takes this step to clarify that single Viewhelpers are not part of the
PHP API, their internal handling may change any time, which is not considered
breaking. Fluid delivers a series of abstract classes to provide base functionality
for common Viewhelper needs. Those can be used by third party viewhelpers if
not marked :php:`@internal`. TYPO3 v12 will fine-tune these abstracts and may
extract specific Viewhelper code to abstracts if the code is generally useful
for extension developers with own View Helpers.

Using Viewhelpers provided by core extensions in Fluid templates is of course
fine as long as they are not marked :php:`@internal`. Arguments to casual Viewhelpers
are considered API and are subject of the general core deprecation strategy. In
general, the base extensions `EXT:fluid`, `EXT:core`, `EXT:frontend` and `EXT:backend`
deliver various general purpose Viewhelpers that can be used, while specific extensions
like `EXT:beuser` add :php:`@internal` Viewhelpers that should not be used in own templates.

Developers are encouraged to adapt own Viewhelpers towards this change with
v11 compatible extensions already, it will simplify compatibility with v12 later.

.. index:: Fluid, PHP-API, ext:fluid
