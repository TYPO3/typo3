..  include:: /Includes.rst.txt

..  _breaking-107777-1760887464:

================================================================
Breaking: #107777 - Use strict types in Extbase class `Argument`
================================================================

See :issue:`107777`

Description
===========

All properties, method arguments, and return types in
:php:`\TYPO3\CMS\Extbase\Mvc\Controller\Argument` are now strictly typed.

Impact
======

Classes extending :php-short:`\TYPO3\CMS\Extbase\Mvc\Controller\Argument` must now
ensure that all overridden properties, method arguments, and return types
declare strict types accordingly.

Affected installations
======================

TYPO3 installations with custom classes extending
:php-short:`\TYPO3\CMS\Extbase\Mvc\Controller\Argument`.

Migration
=========

Ensure all subclasses of
:php-short:`\TYPO3\CMS\Extbase\Mvc\Controller\Argument` use strict type
declarations for overridden properties, method parameters, and return types.

..  index:: Backend, Frontend, NotScanned, ext:extbase
