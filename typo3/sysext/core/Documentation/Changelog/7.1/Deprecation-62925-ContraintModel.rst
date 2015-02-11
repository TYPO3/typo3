====================================================================
Deprecation: #63912 - Deprecate unused methods from Constraint model
====================================================================

Description
===========

``TYPO3\CMS\Belog\Domain\Model\Constraint::setManualDateStart()`` has been marked as deprecated.
``TYPO3\CMS\Belog\Domain\Model\Constraint::getManualDateStart()`` has been marked as deprecated.
``TYPO3\CMS\Belog\Domain\Model\Constraint::setManualDateStop()`` has been marked as deprecated.
``TYPO3\CMS\Belog\Domain\Model\Constraint::getManualDateStop()`` has been marked as deprecated.


Impact
======

Using ``setManualDateStart()``, ``getManualDateStart()``, ``setManualDateStop()`` and ``getManualDateStop()`` of Constraint model class will trigger a deprecation log message.


Affected installations
======================

Instances which use custom form elements, which make use of ``setManualDateStart()``, ``getManualDateStart()``, ``setManualDateStop()`` and ``getManualDateStop()``.


Migration
=========

No migration is possible for all methods, since those methods were unused for a long time already and should not be needed at all.
