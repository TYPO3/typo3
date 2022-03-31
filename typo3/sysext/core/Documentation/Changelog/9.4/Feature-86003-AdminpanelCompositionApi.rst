.. include:: /Includes.rst.txt

==========================================================
Feature: #86003 - Composition based API for the Adminpanel
==========================================================

See :issue:`86003`

Description
===========

A new API to extend the adminpanel for extension authors has been introduced.
Enabling future enhancements for the adminpanel without having to make breaking changes for existing module providers
is a key ingredient for providing a future proof extensible solution. Using single big interfaces that need to change on
updates break backwards compatibility and do not provide sufficient feature encapsulation.

The adminpanel APIs have been refactored to use a composition pattern to allow modules more flexibility.
Modules can now only implement the interfaces they need instead of implementing all functionality.
For example an adminpanel module that only provides page related settings does no longer have to implement
the :php:`getContent()` method.

Small interfaces have been provided as building blocks for modules with rich functionality.
Easy addition of new interfaces that _can_ (not must) be implemented allow future improvements.

Additionally the API has been modified to allow a more object-oriented approach using simple DTOs instead
of associative arrays for better type-hinting and a better developer experience. Storing and rendering data
have been separated in two steps allowing to completely disconnect the rendered adminpanel from the current page.
This can be used as a base for building a complete standalone adminpanel without API changes.

General Request Flow and the Adminpanel
=======================================

To better understand how the adminpanel stores and renders data, let's take a short look at how the adminpanel
is initialized and rendered.

Since TYPO3 v9 TYPO3 uses PSR-15 middlewares. The adminpanel brings three that are relevant to its rendering process:

* :php:`AdminPanelInitiator` - Called early in the request stack to allow initialisation of modules to catch most of the request data (for example log entries)
* :php:`AdminPanelDataPersister` - Called at nearly the end of a frontend request to store the collected data (this is where module data gets saved)
* :php:`AdminPanelRenderer` - Called as one of the last steps in the rendering process, currently replacing the closing body tag with its own code (this is where module content gets rendered)

When building own modules keep in mind at which step your modules` methods get called.
In the last step for example (the rendering), you should not depend on any data outside of
that provided to the module directly (for example do not rely on :php:`$GLOBALS` to be filled).

Current Design Considerations
------------------------------

While the API of the adminpanel is very flexible in combining interfaces, the UI has a fixed structure
and therefor a few things to consider when implementing own modules.

* The bottom bar of the adminpanel will only be rendered for modules that have submodules and implement the :php:`SubmoduleProviderInterface`
* ShortInfo (see below) is only displayed for "TopLevel" modules
* Content is only rendered for submodules


How-To add own modules
======================

Adding custom adminpanel modules always follows these steps:

#. Create a class implementing the basic :php:`ModuleInterface`
#. Register the class in :file:`ext_localconf.php`
#. Implement further interfaces for additional capabilities


1. Create module class
----------------------

To create your own admin panel module, create a
new PHP class implementing :php:`\TYPO3\CMS\Adminpanel\ModuleApi\ModuleInterface`.
The interface denotes your class as an adminpanel module and requires the
implementation of :php:`getIdentifier()` and :php:`getLabel()` as a minimum of methods for a module.

2. Register your module
------------------------

Displayed as a top level module:
++++++++++++++++++++++++++++++++

Register your module by adding the following in your :file:`ext_localconf.php`::

    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['adminpanel']['modules']['mynamespace_modulename'] => [
        'module' => \Your\Namespace\Adminpanel\Modules\YourModule::class,
        'before' => ['cache'],
    ];

via :php:`before` or :php:`after` you can influence where your module will be displayed in the module menu
by referencing the identifier / array key of other modules.

Displayed as a sub module:
++++++++++++++++++++++++++

Register your module by adding the following in your :file:`ext_localconf.php`::

    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['adminpanel']['modules']['info']['submodules']['mynamespace_modulename'] => [
        'module' => \Your\Namespace\Adminpanel\Modules\YourModule::class
    ];

Note the :php:`submodules` key in the array allowing you to introduce hierarchical configuration.


3. Add additional interfaces
----------------------------

Your module is currently registered but is not doing anything yet, as it has no additional capabilities.
The adminpanel provides additional separate interfaces (see list below).
By implementing multiple interfaces you have fine-grained control over how your module behaves, which
data it stores and how it gets rendered.

Adminpanel Interfaces
=====================

ModuleInterface
---------------

Purpose
+++++++
Base interface all adminpanel modules share, defines common methods.

Methods
+++++++

- :php:`getIdentifier()` - Returns :php:`string` identifier of a module (for example `mynamespace_modulename`)
- :php:`getLabel()` - Returns speaking label of a module (for example `My Module`)


ConfigurableInterface
---------------------

Purpose
+++++++
Used to indicate that an adminpanel module can be enabled or disabled via configuration

Methods
+++++++

- :php:`isEnabled` - Returns :php:`bool` depending on whether the module is enabled.

Example implementation
++++++++++++++++++++++

:php:`\TYPO3\CMS\Adminpanel\ModuleApi\AbstractModule::isEnabled()`::

    /**
     * Returns true if the module is
     * -> either enabled via TSConfig admPanel.enable
     * -> or any setting is overridden
     * override is a way to use functionality of the admin panel without displaying the panel to users
     * for example: hidden records or pages can be displayed by default
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        $identifier = $this->getIdentifier();
        $result = $this->isEnabledViaTsConfig();
        if ($this->mainConfiguration['override.'][$identifier] ?? false) {
            $result = (bool)$this->mainConfiguration['override.'][$identifier];
        }
        return $result;
    }



ContentProviderInterface
------------------------

Purpose
+++++++
Adminpanel interface to denote that a module has content to be rendered

Methods
+++++++

* :php:`getContent(ModuleData $data)` - Return content as HTML. For modules implementing the :php:`DataProviderInterface`
  the "ModuleData" object is automatically filled with the stored data - if no data is given a "fresh" ModuleData object is injected.

Example implementation
++++++++++++++++++++++

:php:`\TYPO3\CMS\Adminpanel\Modules\Debug\QueryInformation::getContent`::

    public function getContent(ModuleData $data): string
    {
        $view = new StandaloneView();
        $view->setTemplatePathAndFilename(
            'typo3/sysext/adminpanel/Resources/Private/Templates/Modules/Debug/QueryInformation.html'
        );
        $this->getLanguageService()->includeLLFile('EXT:adminpanel/Resources/Private/Language/locallang_debug.xlf');
        $view->assignMultiple($data->getArrayCopy());
        return $view->render();
    }


DataProviderInterface
------------------------

Purpose
+++++++
Adminpanel interface to denote that a module provides data to be stored for the current request.

Adminpanel modules can save data to the adminpanel request cache and access this data in the rendering process.
Data necessary for rendering the module content has to be returned via this interface implementation, as this allows
for separate data collection and rendering and is a pre-requisite for a standalone debug tool.

Methods
+++++++

- :php:`getDataToStore(ServerRequestInterface $request): ModuleData` - Return a `ModuleData` instance with the data to store

Example implementation
++++++++++++++++++++++

:php:`\TYPO3\CMS\Adminpanel\Modules\Info\RequestInformation::getDataToStore`::

    public function getDataToStore(ServerRequestInterface $request): ModuleData
    {
        return new ModuleData(
            [
                'post' => $_POST,
                'get' => $_GET,
                'cookie' => $_COOKIE,
                'session' => $_SESSION,
                'server' => $_SERVER,
            ]
        );
    }

InitializableInterface
------------------------

Purpose
+++++++

Adminpanel interface to denote that a module has tasks to perform on initialization of the request.

Modules that need to set data / options early in the rendering process to be able to collect data, should implement
this interface - for example the log module uses the initialization to register the adminpanel log collection early
in the rendering process.

Initialize is called in the PSR-15 middleware stack through adminpanel initialisation via the AdminPanel MainController.

Methods
+++++++

* :php:`initializeModule(ServerRequestInterface $request)` - Called on adminpanel initialization

Example implementation
++++++++++++++++++++++

:php:`\TYPO3\CMS\Adminpanel\Modules\CacheModule::initializeModule`::

    public function initializeModule(ServerRequestInterface $request): void
    {
        if ($this->configurationService->getConfigurationOption('cache', 'noCache')) {
            $this->getTypoScriptFrontendController()->set_no_cache('Admin Panel: No Caching', true);
        }
    }


ModuleSettingsProviderInterface
-------------------------------

Purpose
+++++++

Adminpanel module settings interface denotes that a module has own settings.

The adminpanel knows two types of settings:

* ModuleSettings are relevant for the module itself and its representation (for example the log module provides settings
  where displayed log level and grouping of the module can be configured)

* PageSettings are relevant for rendering the page (for example the preview module provides settings showing or hiding
  hidden content elements or simulating a specific rendering time)

If a module provides settings relevant to its own content, use this interface.

Methods
+++++++

* :php:`getSettings(): string` - Return settings as rendered HTML form elements

Example implementation
++++++++++++++++++++++

:php:`\TYPO3\CMS\Adminpanel\Modules\TsDebug\TypoScriptWaterfall::getSettings`::

    public function getSettings(): string
    {
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $templateNameAndPath = 'EXT:adminpanel/Resources/Private/Templates/Modules/TsDebug/TypoScriptSettings.html';
        $view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName($templateNameAndPath));
        $view->setPartialRootPaths(['EXT:adminpanel/Resources/Private/Partials']);

        $view->assignMultiple(
            [
                'tree' => (int)$this->getConfigurationOption('tree'),
                ...
            ]
        );

        return $view->render();
    }


OnSubmitActorInterface
-----------------------

Purpose
+++++++

Adminpanel interface for modules that need to react on changed configuration
(for example if fluid debug settings change, the frontend cache should be cleared).

OnSubmitActors are currently called upon persisting new configuration _before_ the page is reloaded.

Methods
+++++++

* :php:`onSubmit(array $configurationToSave, ServerRequestInterface $request)` - Can act when configuration gets saved.
  Configuration form vars are provided in :php:`$configurationToSave` as an array.

Example implementation
++++++++++++++++++++++

:php:`\TYPO3\CMS\Adminpanel\Modules\PreviewModule::onSubmit`::

    /**
     * Clear page cache if fluid debug output setting is changed
     *
     * @param array $input
     * @param ServerRequestInterface $request
     * @throws \TYPO3\CMS\Core\Cache\Exception\NoSuchCacheException
     */
    public function onSubmit(array $input, ServerRequestInterface $request): void
    {
        $activeConfiguration = (int)$this->getConfigOptionForModule('showFluidDebug');
        if (isset($input['preview_showFluidDebug']) && (int)$input['preview_showFluidDebug'] !== $activeConfiguration) {
            $pageId = (int)$request->getParsedBody()['TSFE_ADMIN_PANEL']['preview_clearCacheId'];
            $cacheManager = GeneralUtility::makeInstance(CacheManager::class);
            $cacheManager->getCache('cache_pages')->flushByTag('pageId_' . $pageId);
            $cacheManager->getCache('fluid_template')->flush();
        }
    }



PageSettingsProviderInterface
-----------------------------

Purpose
+++++++

Adminpanel page settings interface denotes that a module has settings regarding the page rendering.

The adminpanel knows two types of settings:

* ModuleSettings are relevant for the module itself and its representation (for example the log module provides settings
  where displayed log level and grouping of the module can be configured)

* PageSettings are relevant for rendering the page (for example the preview module provides settings showing or hiding
  hidden content elements or simulating a specific rendering time)

If a module provides settings changing the rendering of the main page request, use this interface.

Methods
+++++++

* :php:`getSettings(): string` - Return HTML form elements for settings

Example implementation
++++++++++++++++++++++

:php:`\TYPO3\CMS\Adminpanel\Modules\EditModule::getPageSettings`::

    public function getPageSettings(): string
    {
        $editToolbarService = GeneralUtility::makeInstance(EditToolbarService::class);
        $toolbar = $editToolbarService->createToolbar();
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $templateNameAndPath = 'EXT:adminpanel/Resources/Private/Templates/Modules/Settings/Edit.html';
        $view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName($templateNameAndPath));
        $view->setPartialRootPaths(['EXT:adminpanel/Resources/Private/Partials']);
        $view->assignMultiple(
            [
                'feEdit' => ExtensionManagementUtility::isLoaded('feedit'),
                ...
            ]
        );
        return $view->render();


PageSettingsProviderInterface
-----------------------------

Purpose
+++++++

Adminpanel interface to denote that a module has own resource files.

An adminpanel module implementing this interface may deliver custom JavaScript and Css files to provide additional
styling and JavaScript functionality

Methods
+++++++

* :php:`getJavaScriptFiles(): array` - Returns a string array with javascript files that will be rendered after the module
* :php:`getCssFiles(): array` - Returns a string array with CSS files that will be rendered after the module

Example implementation
++++++++++++++++++++++

:php:`\TYPO3\CMS\Adminpanel\Modules\TsDebugModule`::

    public function getJavaScriptFiles(): array
    {
        return ['EXT:adminpanel/Resources/Public/JavaScript/Modules/TsDebug.js'];
    }

    public function getCssFiles(): array
    {
        return [];
    }


ShortInfoProviderInterface
-----------------------------

Purpose
+++++++

Adminpanel shortinfo provider interface can be used to add the module to the short info bar of the adminpanel.

Modules providing shortinfo will be displayed in the bottom bar of the adminpanel and may provide "at a glance" info
about the current state (for example the log module provides the number of warnings and errors directly).

Be aware that modules with submodules at the moment can only render one short info (the one of the "parent" module).
This will likely change in v10.

Methods
+++++++

* :php:`getShortInfo(): string` - Info string (no HTML) that should be rendered
* :php:`getIconIdentifier(): string` - An icon for this info line, needs to be registered in :php:`IconRegistry`

Example implementation
++++++++++++++++++++++

:php:`\TYPO3\CMS\Adminpanel\Modules\InfoModule`::

    public function getShortInfo(): string
    {
        $parseTime = $this->getTimeTracker()->getParseTime();
        return sprintf($this->getLanguageService()->sL(
            'LLL:EXT:adminpanel/Resources/Private/Language/locallang_info.xlf:module.shortinfo'
        ), $parseTime);
    }

    public function getIconIdentifier(): string
    {
        return 'actions-document-info';
    }



SubmoduleProviderInterface
-----------------------------

Purpose
+++++++

Adminpanel interface providing hierarchical functionality for modules.

A module implementing this interface may have submodules. Be aware that the current implementation of the adminpanel
renders a maximum level of 2 for modules. If you need to render more levels, write your own module and implement
multi-hierarchical rendering in the getContent method.

Methods
+++++++

* :php:`setSubModules(array $subModules)` - Sets array of module instances (instances of :php:`ModuleInterface`) as submodules
* :php:`getSubModules(): array` - Returns an array of module instances
* :php:`hasSubmoduleSettings(): bool` - Return true if any of the submodules has settings to be rendered
  (can be used to render settings in a central place)

Example implementation
++++++++++++++++++++++

:php:`\TYPO3\CMS\Adminpanel\ModuleApi\AbstractModule`::


    public function setSubModules(array $subModules): void
    {
        $this->subModules = $subModules;
    }

    public function getSubModules(): array
    {
        return $this->subModules;
    }

    public function hasSubmoduleSettings(): bool
    {
        $hasSettings = false;
        foreach ($this->subModules as $subModule) {
            if ($subModule instanceof ModuleSettingsProviderInterface) {
                $hasSettings = true;
                break;
            }
            if ($subModule instanceof SubmoduleProviderInterface) {
                $hasSettings = $subModule->hasSubmoduleSettings();
            }
        }
        return $hasSettings;
    }


.. index:: Frontend, PHP-API, ext:adminpanel
