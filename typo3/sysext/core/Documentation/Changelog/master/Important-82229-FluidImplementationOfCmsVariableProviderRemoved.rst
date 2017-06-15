.. include:: ../../Includes.txt

=======================================================================
Important: #82229 - Fluid implementation of CmsVariableProvider removed
=======================================================================

See :issue:`82229`

Description
===========

The PHP class within EXT:fluid named :php:`CmsVariableProvider` was removed. The custom functionality
is available in its parent class :php:`TYPO3Fluid\Fluid\Core\Variables\StandardVariableProvider` from
Fluid standalone can be implemented directly.

Instantiating the removed class will still work through the functionality of class aliases in PHP,
however, using the parent class is encouraged.

.. index:: Fluid, PHP-API, FullyScanned
