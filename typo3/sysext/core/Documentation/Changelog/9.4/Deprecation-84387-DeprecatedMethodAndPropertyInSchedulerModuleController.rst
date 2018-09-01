.. include:: ../../Includes.txt

=================================================================================
Deprecation: #84387 - Deprecated method and property in SchedulerModuleController
=================================================================================

See :issue:`84387`

Description
===========

The property :php:`$CMD` and the method :php:`addMessage()` in the :php:`SchedulerModuleController`
have been marked as deprecated and will be removed in TYPO3 v10.


Impact
======

Accessing the property or calling the method will trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

Third party code which accesses the property directly or calls the method.


Migration
=========

Instead of accessing the property :php:`SchedulerModuleController::$CMD`, the method :php:`getCurrentAction()`
must be used which returns an instance of the :php:`TYPO3\CMS\Scheduler\Task\Enumeration\Action` enumeration.

Instead of calling the method :php:`SchedulerModuleController::addMessage()`, in your additional field providers
you can now extend :php:`TYPO3\CMS\Scheduler\AbstractAdditionalFieldProvider` which provides a method :php:`addMessage()`
with the same API like before.

.. index:: FullyScanned, Backend, PHP-API
