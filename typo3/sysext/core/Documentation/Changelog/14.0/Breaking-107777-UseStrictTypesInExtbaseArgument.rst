..  include:: /Includes.rst.txt

..  _breaking-107777-1760887464:

========================================================
Breaking: #107777 - Use strict types in Extbase Argument
========================================================

See :issue:`107777`

Description
===========

All properties, function arguments and returns types are now strictly typed.


Impact
======

Classes extending :php:`TYPO3\CMS\Extbase\Mvc\Controller\Argument` must
now ensure, that overwritten properties and methods are all are strictly typed.


Affected installations
======================

Custom classes extending :php:`TYPO3\CMS\Extbase\Mvc\Controller\Argument`


Migration
=========

Ensure classes that extend :php:`TYPO3\CMS\Extbase\Mvc\Controller\Argument`
use strict types for overwritten properties, function arguments and return types.

..  index:: Backend, Frontend, NotScanned, ext:extbase
