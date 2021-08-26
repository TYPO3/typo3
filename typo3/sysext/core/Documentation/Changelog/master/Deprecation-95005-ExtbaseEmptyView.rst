.. include:: ../../Includes.txt

=======================================
Deprecation: #95005 - Extbase EmptyView
=======================================

See :issue:`95005`

Description
===========

To further clean up and streamline fluid view related functionality,
extbase related view class :php:`TYPO3\CMS\Extbase\Mvc\View\EmptyView`
has been deprecated.


Impact
======

Using :php:`EmptyView` has been deprecated and logs a deprecation
level error upon use.


Affected Installations
======================

The class has been unused within TYPO3 core since its introduction in 4.5.
It is rather unlikely instances have extensions using the class. The extension
scanner finds usages with a strong match.


Migration
=========

If rendering "nothing" by a view instance is needed for whatever reason, the
same result can be achieved with a :php:`TYPO3\CMS\Fluid\View\StandaloneView`
view instance by setting :php:`$view->setTemplateSource('')` and calling
:php:`$view->render()`. But it's of course quicker to simply not render
anything at all.

.. index:: PHP-API, FullyScanned, ext:extbase
