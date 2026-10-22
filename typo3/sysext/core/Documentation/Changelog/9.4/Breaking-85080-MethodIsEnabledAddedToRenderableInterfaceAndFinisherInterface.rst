.. include:: /Includes.rst.txt

.. _breaking-85080:

==========================================================================================
Breaking: #85080 - Method "isEnabled()" added to RenderableInterface and FinisherInterface
==========================================================================================

See :issue:`85080`

Description
===========

A new method :php:`isEnabled()` has been added to the :php-short:`\TYPO3\CMS\Form\Domain\Model\Renderable\RenderableInterface` as well as the :php-short:`\TYPO3\CMS\Form\Domain\Finishers\FinisherInterface`.


Impact
======

Third party code implementing these interfaces and not extending :php-short:`\TYPO3\CMS\Form\Domain\Model\Renderable\AbstractRenderable` or :php-short:`\TYPO3\CMS\Form\Domain\Finishers\AbstractFinisher` will
cause a fatal error if used in a form.


Affected Installations
======================

Instances with third party code implementing these interfaces and not extending :php-short:`\TYPO3\CMS\Form\Domain\Model\Renderable\AbstractRenderable` or :php-short:`\TYPO3\CMS\Form\Domain\Finishers\AbstractFinisher`.


Migration
=========

Third party code implementing these interfaces must be updated to implement the :php:`isEnabled()` method, preferably
by extending :php-short:`\TYPO3\CMS\Form\Domain\Model\Renderable\AbstractRenderable` (or one of its subclasses) or :php-short:`\TYPO3\CMS\Form\Domain\Finishers\AbstractFinisher`.

.. index:: ext:form, NotScanned
