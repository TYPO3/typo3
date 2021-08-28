.. include:: ../../Includes.txt

==========================================
Deprecation: #94991 - Extbase AbstractView
==========================================

See :issue:`94991`

Description
===========

To simplify and streamline fluid view related class inheritance,
the exbase class :php:`TYPO3\CMS\Extbase\Mvc\View\AbstractView`
has been marked as deprecated and will be removed in v12.


Impact
======

Extending the class should be avoided. Consuming classes should
directly implement :php:`TYPO3\CMS\Extbase\Mvc\View\ViewInterface`
instead.


Affected Installations
======================

Instances with own extbase view classes that extend :php:`AbstractView`
are affected, but this is rather uncommon. The extension scanner will
find class usages as a strong match.


Migration
=========

Affected extbase view classes should implement :php:`ViewInterface` instead
and not extend :php:`AbstractView` anymore. The most simple solution is to
copy the interface implementation from the deprecated :php:`AbstractView` class.

.. index:: Fluid, PHP-API, FullyScanned, ext:extbase
