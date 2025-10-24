..  include:: /Includes.rst.txt

..  _deprecation-107824-1761297638:

====================================================================================
Deprecation: #107824 - ButtonBar, Menu, and MenuRegistry make* methods deprecated
====================================================================================

See :issue:`107824`

Description
===========

The factory methods in :php:`ButtonBar` for creating button instances, in
:php:`Menu` for creating menu item instances, and in :php:`MenuRegistry` for
creating menu instances have been deprecated in favor of using the new
:php:`ComponentFactory` class directly.

The following methods are now deprecated:

* :php:`ButtonBar::makeGenericButton()`
* :php:`ButtonBar::makeInputButton()`
* :php:`ButtonBar::makeSplitButton()`
* :php:`ButtonBar::makeDropDownButton()`
* :php:`ButtonBar::makeLinkButton()`
* :php:`ButtonBar::makeFullyRenderedButton()`
* :php:`ButtonBar::makeShortcutButton()`
* :php:`ButtonBar::makeButton()`
* :php:`Menu::makeMenuItem()`
* :php:`MenuRegistry::makeMenu()`

Impact
======

Calling any of the deprecated :php:`make*()` methods on :php:`ButtonBar`,
:php:`Menu`, or :php:`MenuRegistry` will trigger a PHP deprecation notice.

The methods continue to work in TYPO3 v14 but will be removed in TYPO3 v15.

Affected installations
======================

All extensions using :php:`ButtonBar::make*()` methods to create buttons,
:php:`Menu::makeMenuItem()` to create menu items, or
:php:`MenuRegistry::makeMenu()` to create menus are affected. The extension
scanner will report any usages.

Migration
=========

Inject :php:`ComponentFactory` in your controller and use its :php:`create*()`
methods instead of :php:`ButtonBar::make*()`.

Before:

..  code-block:: php

    public function myAction(): ResponseInterface
    {
        $buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();

        $linkButton = $buttonBar->makeLinkButton()
            ->setHref($url)
            ->setTitle('My Link')
            ->setIcon($icon);

        $buttonBar->addButton($linkButton);
        // ...
    }

After:

..  code-block:: php

    public function __construct(
        protected readonly ComponentFactory $componentFactory,
    ) {}

    public function myAction(): ResponseInterface
    {
        $buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();

        $linkButton = $this->componentFactory->createLinkButton()
            ->setHref($url)
            ->setTitle('My Link')
            ->setIcon($icon);

        $buttonBar->addButton($linkButton);
        // ...
    }

Additionally, consider using the pre-configured button creation methods like
:php:`createBackButton()`, :php:`createCloseButton()`, :php:`createSaveButton()`,
:php:`createReloadButton()`, and :php:`createViewButton()` for common button patterns.

For the low-level :php:`makeButton(string $className)` method, use
:php:`GeneralUtility::makeInstance()` directly or the appropriate
:php:`ComponentFactory::create*()` method:

..  code-block:: php

    // Before:
    $button = $buttonBar->makeButton(MyCustomButton::class);

    // After (option 1 - direct instantiation):
    $button = GeneralUtility::makeInstance(MyCustomButton::class);

    // After (option 2 - via factory if it's a standard button):
    $button = $this->componentFactory->createLinkButton();

For :php:`Menu::makeMenuItem()`, use :php:`ComponentFactory::createMenuItem()`:

..  code-block:: php

    // Before:
    $menu = $menuRegistry->makeMenu();
    $menuItem = $menu->makeMenuItem()
        ->setTitle('My View')
        ->setHref($url);
    $menu->addMenuItem($menuItem);

    // After:
    public function __construct(
        protected readonly ComponentFactory $componentFactory,
    ) {}

    $menu = $this->componentFactory->createMenu();
    $menuItem = $this->componentFactory->createMenuItem()
        ->setTitle('My View')
        ->setHref($url);
    $menu->addMenuItem($menuItem);

For :php:`MenuRegistry::makeMenu()`, use :php:`ComponentFactory::createMenu()`:

..  code-block:: php

    // Before:
    $menuRegistry = $this->moduleTemplate->getDocHeaderComponent()->getMenuRegistry();
    $menu = $menuRegistry->makeMenu();
    $menu->setIdentifier('viewSelector')->setLabel('View');

    // After:
    public function __construct(
        protected readonly ComponentFactory $componentFactory,
    ) {}

    $menuRegistry = $this->moduleTemplate->getDocHeaderComponent()->getMenuRegistry();
    $menu = $this->componentFactory->createMenu();
    $menu->setIdentifier('viewSelector')->setLabel('View');

Additionally, note that :php:`Menu::addMenuItem()` now returns :php:`static`
to support fluent interface patterns:

..  code-block:: php

    // Now possible with fluent interface:
    $menu->addMenuItem($menuItem1)
        ->addMenuItem($menuItem2)
        ->addMenuItem($menuItem3);

.. index:: Backend, PHP-API, FullyScanned, ext:backend
