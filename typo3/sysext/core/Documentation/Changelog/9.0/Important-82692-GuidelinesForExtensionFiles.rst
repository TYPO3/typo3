.. include:: ../../Includes.txt

=======================================================================
Important: #82692 - Guidelines for ext_localconf.php and ext_tables.php
=======================================================================

See :issue:`82692`

Description
===========

Each extension has two easy ways to extend TYPO3 Core, as the TYPO3 Bootstrap loads all active
extensions and include the following files of all extensions, depending on the loading order
of the extensions' dependencies located in :file:`ext_emconf.php`:

1. :file:`ext_localconf.php`
This file is included at every request of TYPO3 (Frontend, Backend, CLI) at a very early stage, just
after the global configuration is loaded and the Package Manager knows which extensions are active.

2. :file:`ext_tables.php`
This file is only included when

* a TYPO3 Backend or CLI request is happening
* or the TYPO3 Frontend is called and a valid Backend User is authenticated

This file gets usually included later within the request and after TCA information is loaded,
and a Backend User is authenticated as well.

These are the typical functionalities that extension authors should place within :file:`ext_localconf.php`

* Registering hooks or any simple array assignments to :php:`$TYPO3_CONF_VARS` options
* Registering additional Request Handlers within the Bootstrap
* Adding any PageTSconfig or Default TypoScript via :php:`ExtensionManagementUtility` APIs
* Registering Extbase Command Controllers
* Registering Scheduler Tasks
* Adding reports to the reports module
* Adding slots to signals via Extbase's SignalSlotDispatcher
* Registering Icons to the IconRegistry
* Registering Services via the Service API

These are the typical functions that should be placed inside :file:`ext_tables.php`

* Registering of Backend modules or Backend module functions
* Adding Context-Sensitive-Help docs via ExtensionManagementUtility API
* Adding TCA descriptions (via :php:`ExtensionManagementUtility::addLLrefForTCAdescr()`)
* Adding table options via :php:`ExtensionManagementUtility::allowTableOnStandardPages`
* Assignments to the global configuration arrays :php:`$TBE_STYLES` and :php:`$PAGES_TYPES`
* Adding new fields to User Settings ("Setup" Extension)

Additionally, it is possible to extend TYPO3 in a lot of different ways (adding TCA, Backend Routes,
Symfony Console Commands etc) which do not need to touch these files.

It is heavily recommended to AVOID any checks on :php:`TYPO3_MODE` or :php:`TYPO3_REQUESTTYPE` constants
(e.g. `if(TYPO3_MODE === 'BE')`) within these files as it limits the functionality to cache the
whole systems' configuration. Any extension author should remove the checks if not explicitly
necessary, and re-evaluate if these context-depending checks could go inside the hooks / caller
function directly.

Additionally, it is recommend to use the extension name (e.g. "tt_address") instead of :php:`$_EXTKEY`
within the two configuration files as this variable will be removed in the future. This also applies
to :php:`$_EXTCONF`.

However, due to limitations to TER, the :php:`$_EXTKEY` option should be kept within an extensions
`ext_emconf.php`.

See any system extension for best practice on this behaviour.

.. index:: PHP-API