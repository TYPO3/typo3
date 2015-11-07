===================================================================
Deprecation: #71249 - Deprecate render method of FlashMessage class
===================================================================

Description
===========

Method ``TYPO3\CMS\Core\Messaging\FlashMessage::render()`` has been deprecated.


Affected Installations
======================

Instances with custom backend modules that use this method.


Migration
=========

Use custom render code, the ``<f:flashMessage />`` ViewHelper or the ``ModuleTemplate`` for backend modules to render Flash Messages.
It is suggested not to include HTML in flash messages. Flash messages should be short notifications on usr interactions.
If you need more elaborate or persistent messages, use ``<f:be.infobox />`` view helper or HTML similar to that.