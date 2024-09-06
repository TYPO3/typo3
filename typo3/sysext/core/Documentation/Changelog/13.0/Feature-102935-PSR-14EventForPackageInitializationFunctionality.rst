.. include:: /Includes.rst.txt

.. _feature-102935-1706258668:

========================================================================
Feature: #102935 - PSR-14 event for package initialization functionality
========================================================================

See :issue:`102935`

Description
===========

A new PSR-14 event :php:`\TYPO3\CMS\Core\Package\Event\PackageInitializationEvent`
has been introduced. It allows listeners to execute custom functionality after
a package has been activated. The event is therefore being dispatched at several
places, where packages get activated. Those are e.g. on extension installation
by the extension manager, or on calling the `typo3 extension:setup` command. The
main component, dispatching the event however is the new
:php:`PackageActivationService`. The new service is a drop-in replacement for
the :php:`InstallUtility->install()` method, which is from now on just a wrapper
around :php:`PackageActivationService->activate()`. The wrapper is used to still
pass the current instance to listeners of the :php:`AfterPackageActivationEvent`.

TYPO3 already registers a couple of listeners to this event:

* :php:`\TYPO3\CMS\Core\Package\Initialization\ImportExtensionDataOnPackageInitialization`
* :php:`\TYPO3\CMS\Core\Package\Initialization\ImportStaticSqlDataOnPackageInitialization`
* :php:`\TYPO3\CMS\Core\Package\Initialization\CheckForImportRequirements`
* :php:`\TYPO3\CMS\Impexp\Initialization\ImportContentOnPackageInitialization`
* :php:`\TYPO3\CMS\Impexp\Initialization\ImportSiteConfigurationsOnPackageInitialization`

Developers are able to listen to the new event before or after TYPO3 Core
listeners have been executed, using :php:`before` and :php:`after` in the
listener registration. All listeners are able to store arbitrary data
in the Event using the :php:`addStorageEntry()` method. This is also used
by the core listeners to store their result, which was previously passed
to the :doc:`removed <../13.0/Breaking-102935-OverhauledExtensionInstallationInExtensionManager>`
`EXT:extensionmanager` PSR-14 events.

Listeners can access that information using corresponding :php:`getStorageEntry()`
method. Those entries are a :php:`PackageInitializationResult` object, which
features the following methods:

* :php:`getIdentifier()` - Returns the entry identifier, which is the listener service name for the TYPO3 Core listeners
* :php:`getResult()` - Returns the result data, added by the corresponding listener

Using the new Event, listeners are equipped with following methods:

* :php:`getExtensionKey()` - Returns the extension key for the activated package
* :php:`getPackage()` - Returns the :php:`PackageInterface` object of the activated package
* :php:`getContainer()` - Returns the :php:`ContainerInterface`, used on activating the package
* :php:`getEmitter()` - Returns the emitter / the service, which has dispatched the event
* :php:`hasStorageEntry()` - Whether a storage entry for a given identifier exists
* :php:`getStorageEntry()` - Returns a storage entry for a given identifier
* :php:`addStorageEntry()` - Adds a storage entry (:php:`PackageInitializationResult`) to the event
* :php:`removeStorageEntry()` - Removes a storage entry by a given identifier

.. note::

    In case you have previously called :php:`InstallUtility->processExtensionSetup()`
    directly, you can now just dispatch the new event.

Example
=======

The event listener class, using the PHP attribute :php:`#[AsEventListener]` for
registration, placing the listener after a specific core listener and adding
a storage entry, using the listener class name as identifier (which is
recommended and also done by TYPO3 Core):

..  code-block:: php

    use TYPO3\CMS\Core\Attribute\AsEventListener;
    use TYPO3\CMS\Core\Package\Event\PackageInitializationEvent;
    use TYPO3\CMS\Core\Package\Initialization\ImportExtensionDataOnPackageInitialization;

    final class PackageInitializationEventListener
    {
        #[AsEventListener(after: ImportExtensionDataOnPackageInitialization::class)]
        public function __invoke(PackageInitializationEvent $event): void
        {
            if ($event->getExtensionKey() === 'my_ext') {
                $event->addStorageEntry(__CLASS__, 'my result');
            }
        }
    }

Impact
======

Using the new PSR-14 event, it's now possible to execute custom functionality
when a package has been activated. Since TYPO3 Core also uses listeners to
this event, custom extensions can easily place their functionality in between
and fetch necessary information directly from the event's storage, instead of
registering dedicated listeners.

.. index:: Backend, PHP-API, ext:core
