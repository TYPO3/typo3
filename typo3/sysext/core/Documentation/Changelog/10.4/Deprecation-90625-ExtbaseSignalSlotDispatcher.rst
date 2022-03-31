.. include:: /Includes.rst.txt

===================================================
Deprecation: #90625 - Extbase SignalSlot Dispatcher
===================================================

See :issue:`90625`

Description
===========

TYPO3 has various methods to extend existing TYPO3 Core functionality via PHP.

One of the famous APIs is the so-called "SignalSlot Dispatcher", originally provided by Extbase and
TYPO3 Flow.

The SignalSlot Dispatcher follows the Observer pattern, which was originally not designed to
actually interact (= modify) the information handed in - it's a signal that is sent.

Since March 2019, a new standard recommendation in PHP - PSR-14 - was put into place, and adopted
in TYPO3 v10.0. TYPO3s PSR-14 implementation has several advantages over SignalSlot:

* All Events ("Signals" in Extbase world) are actual PHP objects that clearly define what can
  be read or modified.
* All Events are registered at compile-time (inside the Service Container), so the Listeners
  ("Slots" in Extbase world) are defined in one place and are always available. Previously the
  registration of the slots was done in :file:`ext_localconf.php`.
* Events can be used across other PHP projects as well, and the EventDispatcher can be the same
  instance, as it is standard recommendation.

In TYPO3 v10, all Extbase signals provided by TYPO3 Core have been migrated to PSR-14 events.

For this reason, the Extbase SignalSlot Dispatcher has been marked as deprecated in TYPO3 Core.
It is recommended to migrate to PSR-14 Events and Event Listeners.


Impact
======

As :php:`SignalSlotDispatcher` is still in place within TYPO3 Core for backwards-compatibility reasons,
and extensions still have lots of Signals defined, no PHP :php:`E_USER_DEPRECATED` error will be triggered
if an extension is using the SignalSlot mechanism. However using it is highly discouraged, as it
will be removed in future TYPO3 versions.


Affected Installations
======================

Any TYPO3 installations with custom extensions that are using the SignalSlot Dispatcher.


Migration
=========

Use PSR-14 Events and Event-Listeners instead.

See the documentation for details:
https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/ApiOverview/Hooks/EventDispatcher/Index.html

.. index:: PHP-API, FullyScanned, ext:extbase
