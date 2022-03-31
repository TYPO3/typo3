.. include:: /Includes.rst.txt

=========================================================================
Feature: #88807 - AdminPanel RequestEnricherInterface has been introduced
=========================================================================

See :issue:`88807`

Description
===========

The AdminPanel initialisation process has been refactored and an interface called
:php:`\TYPO3\CMS\Adminpanel\ModuleApi\RequestEnricherInterface` has been introduced.


Impact
======

With the :php:`\TYPO3\CMS\Adminpanel\ModuleApi\RequestEnricherInterface`, adminpanel modules gain the
ability to manipulate the request object during TYPO3's processing of the PSR-15 middlewares.
All modules implementing the interface need a method :php:`enrich($request)` and may return an altered
:php:`$request` in their processing. At the end of the processing, the `$request` has to be returned
and will in turn be used in further PSR-15 middleware stack processing.

.. index:: Frontend, PHP-API, ext:adminpanel
