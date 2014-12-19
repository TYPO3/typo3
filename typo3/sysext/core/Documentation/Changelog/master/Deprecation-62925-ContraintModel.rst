====================================================================
Deprecation: #63912 - Deprecate unused methods from Constraint model
====================================================================

Description
===========

:php:`TYPO3\CMS\Belog\Domain\Model\Constraint::setManualDateStart()` has been deprecated.
:php:`TYPO3\CMS\Belog\Domain\Model\Constraint::getManualDateStart()` has been deprecated.
:php:`TYPO3\CMS\Belog\Domain\Model\Constraint::setManualDateStop()` has been deprecated.
:php:`TYPO3\CMS\Belog\Domain\Model\Constraint::getManualDateStop()` has been deprecated.


Impact
======

Using :php:`setManualDateStart()`, :php:`getManualDateStart()`, :php:`setManualDateStop()` and :php:`getManualDateStop()` of Contraint model class will trigger a deprecation log message.


Affected installations
======================

Instances which use custom form elements, which make use of :php:`setManualDateStart()`, :php:`getManualDateStart()`, :php:`setManualDateStop()` and :php:`getManualDateStop()`.


Migration
=========

For all methods no migration is possible, those methods were unused for a long time already and should not be needed at all.
