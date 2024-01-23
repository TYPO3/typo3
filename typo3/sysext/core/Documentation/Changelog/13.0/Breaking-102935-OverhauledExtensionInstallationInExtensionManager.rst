.. include:: /Includes.rst.txt

.. _breaking-102935-1706258423:

==========================================================================
Breaking: #102935 - Overhauled extension installation in Extension Manager
==========================================================================

See :issue:`102935`

Description
===========

Installing extensions via the extension manager is only used for non-Composer-based
installations. However, there have been a couple of dependencies to
the `EXT:extensionmanager`, which required even Composer-based installations
to have this extension installed. This has now been resolved. The
`EXT:extensionmanager` extension is now optional.

The public :php:`\TYPO3\CMS\Extensionmanager\Utility\InstallUtility->processExtensionSetup()`
method has therefore been removed. It has previously been used to execute a
couple of "import" tasks, such as import site configurations or media assets to
the :file:`fileadmin/`. However those tasks had dependencies to other optional core
extensions, such as `EXT:impexp`. Therefore the new PSR-14 event
:php:`PackageInitializationEvent` has been introduced and the functionality
has been split into corresponding event listeners, which are added to their
associated Core extensions.

The PSR-14 events, dispatched by those "tasks" have been removed:

* :php:`\TYPO3\CMS\Extensionmanager\Event\AfterExtensionDatabaseContentHasBeenImportedEvent`
* :php:`\TYPO3\CMS\Extensionmanager\Event\AfterExtensionFilesHaveBeenImportedEvent`
* :php:`\TYPO3\CMS\Extensionmanager\Event\AfterExtensionSiteFilesHaveBeenImportedEvent`
* :php:`\TYPO3\CMS\Extensionmanager\Event\AfterExtensionStaticDatabaseContentHasBeenImportedEvent`

The information, provided by those events can now be accessed by fetching the
corresponding storage entry from the new
:php:`\TYPO3\CMS\Core\Package\Event\PackageInitializationEvent`.

Using :php:`before` and :php:`after` keywords in the listener registration,
custom extensions can ensure to be executed, once the corresponding information
is available.

It's even possible to manually execute those "tasks" by dispatching the
:php:`PackageInitializationEvent` in custom extension code. This can be
used as replacement for the :php:`InstallUtility->processExtensionSetup()` call.

Impact
======

Using one of the removed PSR-14 events or calling the removed method will
lead to a PHP error. The extension scanner will report any usages.


Affected installations
======================

TYPO3 installations with extensions registering listeners to the removed events
or calling the removed method in their extension code.

Migration
=========

Instead of registering listeners for the removed events, developers can now
just register a listener to the new :php:`PackageInitializationEvent`, which
contains the listeners result as storage entry:

.. code-block:: php

    // Before

    #[AsEventListener]
    public function __invoke(AfterExtensionSiteFilesHaveBeenImportedEvent $event): void
    {
        foreach ($event->getSiteIdentifierList() as $siteIdentifier) {
            $configuration = $this->siteConfiguration->load($siteIdentifier);
            $configuration = $this->extendSiteConfiguration($configuration);
            $this->siteConfiguration->write($siteIdentifier, $configuration);
        }
    }

    // After

    #[AsEventListener(after: ImportSiteConfigurationsOnPackageInitialization::class)]
    public function __invoke(PackageInitializationEvent $event): void
    {
        foreach ($event->getStorageEntry(ImportSiteConfigurationsOnPackageInitialization::class)->getResult() as $siteIdentifier) {
            $configuration = $this->siteConfiguration->load($siteIdentifier);
            $configuration = $this->extendSiteConfiguration($configuration);
            $this->siteConfiguration->write($siteIdentifier, $configuration);
        }
    }

Instead of calling :php:`InstallUtility->processExtensionSetup()`, extensions
can just dispatch the :php:`PackageInitializationEvent` on their own.

.. index:: Backend, PHP-API, PartiallyScanned, ext:extensionmanager
