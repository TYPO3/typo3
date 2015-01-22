====================================================================
Deprecation: #63912 - Deprecate unused methods from Constraint model
====================================================================

Description
===========

:code:`TYPO3\CMS\Belog\Domain\Model\Constraint::setManualDateStart()` has been deprecated.
:code:`TYPO3\CMS\Belog\Domain\Model\Constraint::getManualDateStart()` has been deprecated.
:code:`TYPO3\CMS\Belog\Domain\Model\Constraint::setManualDateStop()` has been deprecated.
:code:`TYPO3\CMS\Belog\Domain\Model\Constraint::getManualDateStop()` has been deprecated.


Impact
======

Using :code:`setManualDateStart()`, :code:`getManualDateStart()`, :code:`setManualDateStop()` and :code:`getManualDateStop()` of Contraint model class will trigger a deprecation log message.


Affected installations
======================

Instances which use custom form elements, which make use of :code:`setManualDateStart()`, :code:`getManualDateStart()`, :code:`setManualDateStop()` and :code:`getManualDateStop()`.


Migration
=========

For all methods no migration is possible, those methods were unused for a long time already and should not be needed at all.
