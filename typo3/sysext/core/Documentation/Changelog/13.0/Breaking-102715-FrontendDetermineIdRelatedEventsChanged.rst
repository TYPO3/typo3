.. include:: /Includes.rst.txt

.. _breaking-102715-1703254781:

===================================================================
Breaking: #102715 - Frontend "determineId()" related events changed
===================================================================

See :issue:`102715`

Description
===========

With the continued refactoring of :php:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController`,
the following events have been adapted:

* :php:`\TYPO3\CMS\Frontend\Event\BeforePageIsResolvedEvent`
* :php:`\TYPO3\CMS\Frontend\Event\AfterPageWithRootLineIsResolvedEvent`
* :php:`\TYPO3\CMS\Frontend\Event\AfterPageAndLanguageIsResolvedEvent`

The three events no longer retrieve an instance of :php:`TypoScriptFrontendController`, the
getter methods :php:`getController()` have been removed: The controller is instantiated
*after* the events have been dispatched, event listeners can no longer work with this
object.

Instead, the events now contain an instance of the new :ab:DTO (Data Transfer Object)`
:php:`\TYPO3\CMS\Frontend\Page\PageInformation`, which can be retrieved and
manipulated by event listeners if necessary.

Impact
======

Calling :php:`getController()` by consumers of above events will raise a fatal
PHP error.

Also note the events may not be dispatched anymore when the middleware
:php:`\TYPO3\CMS\Frontend\Middleware\TypoScriptFrontendInitialization` creates
early responses.


Affected installations
======================

Those events are in place for a couple of special cases during early frontend rendering.
Most instances will not be affected, but some extensions may register event listeners.


Migration
=========

Use method :php:`getPageInformation()` instead to retrieve calculated page state at
this point in the frontend rendering chain. Event listeners that manipulate that
object should set it again within the event using :php:`setPageInformation()`.

In case middleware :php:`TypoScriptFrontendInitialization` no longer dispatches an event
when it created an early response on its own, an own middleware can be added around
that middleware to retrieve and further manipulate a response if needed.


.. index:: Frontend, PHP-API, PartiallyScanned, ext:frontend
