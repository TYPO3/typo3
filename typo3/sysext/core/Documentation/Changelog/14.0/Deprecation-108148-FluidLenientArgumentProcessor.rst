..  include:: /Includes.rst.txt

..  _deprecation-108148-1766235915:

=====================================================
Deprecation: #108148 - Fluid LenientArgumentProcessor
=====================================================

See :issue:`108148`

Description
===========

Fluid 5.0 deprecates :php:`TYPO3Fluid\Fluid\Core\ViewHelper\LenientArgumentProcessor`,
which will be removed with Fluid 6.0. :php:`TYPO3Fluid\Fluid\Core\ViewHelper\StrictArgumentProcessor`
is now used instead.


Impact
======

The impact of the switch to :php:`TYPO3Fluid\Fluid\Core\ViewHelper\StrictArgumentProcessor`
is documented in
`Breaking: #108148 - Strict Types in Fluid ViewHelpers <https://docs.typo3.org/permalink/changelog:breaking-108148-1763288349>`_.


Affected installations
======================

Installations that use the :php:`TYPO3Fluid\Fluid\Core\ViewHelper\LenientArgumentProcessor`
programmatically.


Migration
=========

The class can be copied to the project/extension if it's still required.

..  index:: Fluid, FullyScanned, ext:fluid
