..  include:: /Includes.rst.txt

..  _deprecation-107823-1761297638:

====================================================================================
Deprecation: #107823 - ButtonBar, Menu, and MenuRegistry make* methods deprecated
====================================================================================

See :issue:`107823`

Description
===========

The factory methods in :php:`\TYPO3\CMS\Backend\Template\Components\ButtonBar`
for creating button instances, in
:php:`\TYPO3\CMS\Backend\Template\Components\Menu` for creating menu item
instances, and in
:php:`\TYPO3\CMS\Backend\Template\Components\MenuRegistry` for creating
menu instances have been deprecated in favor of using the new
:php:`\TYPO3\CMS\Backend\Template\Components\ComponentFactory` class directly.

The following methods are now deprecated:

*   :php-short:`\TYPO3\CMS\Backend\Template\Components\ButtonBar::makeGenericButton()`
*   :php-short:`\TYPO3\CMS\Backend\Template\Components\ButtonBar::makeInputButton()`
*   :php-short:`\TYPO3\CMS\Backend\Template\Components\ButtonBar::makeSplitButton()`
*   :php-short:`\TYPO3\CMS\Backend\Template\Components\ButtonBar::makeDropDownButton()`
*   :php-short:`\TYPO3\CMS\Backend\Template\Components\ButtonBar::makeLinkButton()`
*   :php-short:`\TYPO3\CMS\Backend\Template\Components\ButtonBar::makeFullyRenderedButton()`
*   :php-short:`\TYPO3\CMS\Backend\Template\Components\ButtonBar::makeShortcutButton()`
*   :php-short:`\TYPO3\CMS\Backend\Template\Components\ButtonBar::makeButton()`
*   :php-short:`\TYPO3\CMS\Backend\Template\Components\Menu\Menu::makeMenuItem()`
*   :php-short:`\TYPO3\CMS\Backend\Template\Components\MenuRegistry::makeMenu()`

Impact
======

Calling any of the deprecated :php:`make*()` methods on
:php-short:`\TYPO3\CMS\Backend\Template\Components\ButtonBar`,
:php-short:`\TYPO3\CMS\Backend\Template\Components\Menu`, or
:php-short:`\TYPO3\CMS\Backend\Template\Components\MenuRegistry` will trigger
a PHP deprecation notice.

The methods continue to work in TYPO3 v14 but will be removed in TYPO3 v15.

Affected installations
======================

All extensions using :php-short:`\TYPO3\CMS\Backend\Template\Components\ButtonBar::make*()` methods to create buttons,
:php-short:`\TYPO3\CMS\Backend\Template\Components\Menu\Menu::makeMenuItem()` to create menu items, or
:php-short:`\TYPO3\CMS\Backend\Template\Components\MenuRegistry::makeMenu()` to create menus are affected.
The extension scanner will report any usages.

Migration
=========

Inject :php:`\TYPO3\CMS\Backend\Template\Components\ComponentFactory` in your
controller and use its :php:`create*()` methods instead of
:php-short:`\TYPO3\CMS\Backend\Template\Components\ButtonBar::make*()`.

**Before:**

..  code-block:: php
    :caption: Example (before)

    use Psr\Http\Message\ResponseInterface;

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

**After:**

..  code-block:: php
    :caption: Example (after)

    use Psr\Http\Message\ResponseInterface;
    use TYPO3\CMS\Backend\Template\Components\ComponentFactory;

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

Additionally, consider using the preconfigured button creation methods like
:php:`createBackButton()`, :php:`createCloseButton()`,
:php:`createSaveButton()`, :php:`createReloadButton()`, and
:php:`createViewButton()` for common button patterns.

For the low-level :php:`makeButton(string $className)` method, use
:php-short:`\TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance()` directly or the appropriate
:php-short:`\TYPO3\CMS\Backend\Template\Components\ComponentFactory::create*()` method:

..  code-block:: php
    :caption: Example (button instantiation)

    use TYPO3\CMS\Core\Utility\GeneralUtility;

    // Before:
    $button = $buttonBar->makeButton(MyCustomButton::class);

    // After (option 1 - direct instantiation):
    $button = GeneralUtility::makeInstance(MyCustomButton::class);

    // After (option 2 - via factory if it's a standard button):
    $button = $this->componentFactory->createLinkButton();

For :php-short:`\TYPO3\CMS\Backend\Template\Components\Menu\Menu::makeMenuItem()`, use :php-short:`\TYPO3\CMS\Backend\Template\Components\ComponentFactory::createMenuItem()`:

..  code-block:: php
    :caption: Example (menu items)

    use TYPO3\CMS\Backend\Template\Components\ComponentFactory;

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

For :php-short:`\TYPO3\CMS\Backend\Template\Components\MenuRegistry::makeMenu()`, use :php-short:`\TYPO3\CMS\Backend\Template\Components\ComponentFactory::createMenu()`:

..  code-block:: php
    :caption: Example (menus)

    use TYPO3\CMS\Backend\Template\Components\ComponentFactory;

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

Additionally, note that :php-short:`\TYPO3\CMS\Backend\Template\Components\Menu\Menu::addMenuItem()` now returns :php:`static`
to support fluent interface patterns:

..  code-block:: php
    :caption: Example (fluent chaining)

    // Now possible with fluent interface:
    $menu->addMenuItem($menuItem1)
        ->addMenuItem($menuItem2)
        ->addMenuItem($menuItem3);

..  index:: Backend, PHP-API, FullyScanned, ext:backend
