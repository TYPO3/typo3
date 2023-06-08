.. include:: /Includes.rst.txt

.. _deprecation-100639-1681740974:

===============================================
Deprecation: #100639 - Deprecate AbstractPlugin
===============================================

See :issue:`100639`

Description
===========

Abstract "pibase" class :php:`\TYPO3\CMS\Frontend\Plugin\AbstractPlugin`
has been marked @internal with
:ref:`changelog-Breaking-98281-MakeAbstractPluginInternal <breaking-98281-1662549900>` in TYPO3 v12.0
already and should not be used anymore.

It has now been fully deprecated with TYPO3 v12.4 and will be removed with TYPO3 v13.0.


Impact
======

Extending :php:`AbstractPlugin` will trigger a deprecation level log warning
since TYPO3 v12.4. The class will be removed with TYPO3 v13.0.


Affected installations
======================

Instances with frontend plugin extensions that extend
:php:`\TYPO3\CMS\Frontend\Plugin\AbstractPlugin` are affected.

The extension scanner will find usages with a strong match.


Migration
=========

Stop extending the class. A simple way to migrate is by copying needed methods
over to an own controller class. See
:ref:`changelog-Breaking-98281-MakeAbstractPluginInternal <breaking-98281-1662549900>`
for more details on this.


.. index:: Frontend, PHP-API, FullyScanned, ext:frontend
