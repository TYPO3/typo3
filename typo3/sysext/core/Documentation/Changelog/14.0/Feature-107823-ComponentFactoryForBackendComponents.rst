..  include:: /Includes.rst.txt

..  _feature-107823-1761297638:

==========================================================
Feature: #107823 - ComponentFactory for backend components
==========================================================

See :issue:`107823`

Description
===========

A new :php:`\TYPO3\CMS\Backend\Template\Components\ComponentFactory` class
has been introduced as the central location for all backend component creation.
It provides factory methods for buttons and menu components, offering both
pre-configured buttons for common patterns and basic component creation methods.

The :php:`ComponentFactory` serves multiple purposes:

1. **Pre-configured common buttons** - Ready-to-use buttons like back, close, save,
   reload, and view with standardized icons, labels, and behavior
2. **Basic button creation** - Factory methods for creating button instances
   (previously available only on :php:`ButtonBar`)
3. **Menu component creation** - Factory methods for creating Menu and MenuItem instances
   (previously available only on :php:`MenuRegistry` and :php:`Menu`)

The deprecated :php:`ButtonBar::make*()`, :php:`Menu::makeMenuItem()`, and
:php:`MenuRegistry::makeMenu()` methods have been replaced by :php:`ComponentFactory`,
providing a cleaner separation of concerns where container classes manage organization
and :php:`ComponentFactory` handles component creation.

Additionally, several "add" methods now support fluent interface patterns, to
enable method chaining for improved code readability.

.. _feature-107823-recordlists:

Database record list and file list
----------------------------------

The :php`TYPO3\CMS\Backend\RecordList\DatabaseRecordList` and
:php:`TYPO3\CMS\Filelist\FileList` classes, which are responsible in the backend
to show all lists of records and files, now make use of the ComponentFactory.
The list of "action buttons" is no longer represented with plain HTML and allows
a clear distinction of primary and secondary actions.

For this, the new
enum :php:`TYPO3\CMS\Backend\Template\Components\ActionGroup` and
:php:`TYPO3\CMS\Backend\Template\Components\ComponentGroup` are also added
to the Button API to allow this grouping.

The existing PSR-14 events (
:php:`\TYPO3\CMS\Backend\RecordList\Event\ModifyRecordListRecordActionsEvent`,
:php:`\TYPO3\CMS\Filelist\Event\ProcessFileListActionsEvent`) which are used within
these classes have been streamlined to deal with these API changes. Details can
be found see in the related :ref:`breaking changes document <breaking-107884-1730135000>`
of :issue:`107884`.

The Button API has also been enhanced to allow passing a new
:php:`TYPO3\CMS\Backend\Template\Components\Buttons\ButtonSize` enum
to differentiate the buttons for certain icon sizes.

.. _feature-107823-factorymethods:

Available Factory Methods
-------------------------

The :php:`ComponentFactory` provides two categories of methods:

**Pre-configured Common Buttons:**

These methods provide ready-to-use buttons with sensible defaults. The returned instances
are fully mutable and can be further customized using fluent interface methods
(e.g., :php:`setDataAttributes()`, :php:`setClasses()`, :php:`setIcon()`).

URL parameters accept both :php:`string` and :php:`UriInterface` for convenience.

* :php:`createBackButton(string|UriInterface $returnUrl)` - Standard back navigation with "Go back" label
* :php:`createCloseButton(string|UriInterface $closeUrl)` - Close button for modal-like views
* :php:`createSaveButton(string $formName = '')` - Standard save button for forms
* :php:`createReloadButton(string|UriInterface $requestUri)` - Reload current view
* :php:`createViewButton(array $previewDataAttributes = [])` - View/preview page button with data attributes

**Basic Button Creation:**

* :php:`createLinkButton()` - Creates a new LinkButton instance
* :php:`createInputButton()` - Creates a new InputButton instance
* :php:`createGenericButton()` - Creates a new GenericButton instance
* :php:`createSplitButton()` - Creates a new SplitButton instance
* :php:`createDropDownButton()` - Creates a new DropDownButton instance
* :php:`createFullyRenderedButton()` - Creates a new FullyRenderedButton instance
* :php:`createShortcutButton()` - Creates a new ShortcutButton instance
* :php:`createDropDownDivider()` - Creates a new DropDownDivider instance
* :php:`createDropDownItem()` - Creates a new DropDownItem instance

**Menu Component Creation:**

* :php:`createMenu()` - Creates a new Menu instance
* :php:`createMenuItem()` - Creates a new MenuItem instance

.. note::

   The corresponding :php:`ButtonBar::make*()`, :php:`Menu::makeMenuItem()`, and
   :php:`MenuRegistry::makeMenu()` methods are now deprecated.
   See :ref:`deprecation-107823-1761297638`

The Button API has also been enhanced to allow passing a new
 enum
to differentiate the buttons for certain icon sizes.

.. _feature-107823-buttonapi:

Improvements to Button API types
--------------------------------

The following button types can use :php:`getSize()` and :php:`setSize()`
methods in their instance to set the icon size with the
:php:`TYPO3\CMS\Backend\Template\Components\Buttons\ButtonSize` enum,
choosing between a small, medium and large variant (utilizing CSS classes
internally):

* :php:`TYPO3\CMS\Backend\Template\Components\Buttons\DropDownButton`
* :php:`TYPO3\CMS\Backend\Template\Components\Buttons\GenericButton`
* :php:`TYPO3\CMS\Backend\Template\Components\Buttons\LinkButton`


Impact
======

Backend module developers should now inject :php:`ComponentFactory` in their controllers
to create buttons. The factory provides:

1. **Pre-configured buttons** for common patterns (back, close, save, reload, view)
2. **Basic button creation** methods (formerly only on ButtonBar)

The :php:`ButtonBar::make*()` methods continue to work but are deprecated and will
be removed in TYPO3 v15. This change provides a cleaner architecture where
:php:`ComponentFactory` handles all button creation and :php:`ButtonBar` focuses solely
on managing button positioning and organization.

Example - Using pre-configured buttons (inject ComponentFactory):

..  code-block:: php

    // In controller constructor
    public function __construct(
        protected readonly ComponentFactory $componentFactory,
    ) {}

    // In controller action
    public function editAction(): ResponseInterface
    {
        $buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();

        // Use pre-configured back button
        $backButton = $this->componentFactory->createBackButton($returnUrl);
        $buttonBar->addButton($backButton, ButtonBar::BUTTON_POSITION_LEFT, 1);

        // Use pre-configured save button
        $saveButton = $this->componentFactory->createSaveButton('editform');
        $buttonBar->addButton($saveButton, ButtonBar::BUTTON_POSITION_LEFT, 2);

        // ...
    }

Example - Creating basic buttons via ComponentFactory:

..  code-block:: php

    // Inject ComponentFactory in constructor
    public function __construct(
        protected readonly ComponentFactory $componentFactory,
    ) {}

    // Create basic buttons via factory
    $linkButton = $this->componentFactory->createLinkButton()
        ->setHref($url)
        ->setTitle('Custom')
        ->setIcon($icon);

Example - Using the ModuleTemplate convenience method:

..  code-block:: php

    public function __construct(
        protected readonly ComponentFactory $componentFactory,
    ) {}

    public function myAction(): ResponseInterface
    {
        // Shorthand: use ModuleTemplate::addButtonToButtonBar()
        $this->moduleTemplate->addButtonToButtonBar(
            $this->componentFactory->createReloadButton($this->request->getUri()->getPath()),
            ButtonBar::BUTTON_POSITION_RIGHT
        );

        $this->moduleTemplate->addButtonToButtonBar(
            $this->componentFactory->createBackButton($returnUrl),
            ButtonBar::BUTTON_POSITION_LEFT,
            1
        );

        // ...
    }

Example - Customizing pre-configured buttons:

..  code-block:: php

    public function __construct(
        protected readonly ComponentFactory $componentFactory,
    ) {}

    public function myAction(): ResponseInterface
    {
        // Pre-configured buttons return mutable instances that can be further customized
        $reloadButton = $this->componentFactory
            ->createReloadButton((string)$this->uriBuilder->buildUriFromRoute($currentModule->getIdentifier()))
            ->setDataAttributes(['csp-reports-handler' => 'refresh']);

        $this->moduleTemplate->addButtonToButtonBar($reloadButton, ButtonBar::BUTTON_POSITION_RIGHT);

        // Add custom styling or behavior to a save button
        $saveButton = $this->componentFactory
            ->createSaveButton('myform')
            ->setClasses('btn-primary custom-save')
            ->setDataAttributes(['validate' => 'true']);

        $this->moduleTemplate->addButtonToButtonBar($saveButton, ButtonBar::BUTTON_POSITION_LEFT);

        // URL parameters accept both string and UriInterface
        $backButton = $this->componentFactory->createBackButton($this->request->getUri()); // UriInterface
        // or
        $backButton = $this->componentFactory->createBackButton('/return/url'); // string

        // ...
    }

.. _feature-107823-fluent:

Fluent Interface Improvements
=============================

Several "add" methods now support fluent interface pattern, enabling method
chaining:

**ButtonBar**

   ..  code-block:: php

       $buttonBar
           ->addButton($backButton, ButtonBar::BUTTON_POSITION_LEFT, 1)
           ->addButton($saveButton, ButtonBar::BUTTON_POSITION_LEFT, 2);

**Menu**

   ..  code-block:: php

       $menu
           ->addMenuItem($listItem)
           ->addMenuItem($gridItem)
           ->addMenuItem($tableItem);

**MenuRegistry**

   ..  code-block:: php

       $menuRegistry
           ->addMenu($viewMenu)
           ->addMenu($filterMenu);

These changes provide a more fluent API while maintaining backward compatibility, as the
return values were previously ignored (:php:`void`).

.. _feature-107823-rationale:

Design Rationale
================

**Why fluent interface instead of named parameters?**

The ComponentFactory intentionally uses a fluent interface approach (chained method calls)
rather than accepting parameters in factory methods. This design decision was made for
several important reasons:

**Consistency with TYPO3 patterns**
   The fluent interface pattern is well-established throughout TYPO3's codebase and
   familiar to extension developers. Introducing a different pattern here would be
   inconsistent with the rest of the framework.

**Diverse button types with different properties**
   Different button types have vastly different configuration requirements. For example,
   InputButton needs name/value/form attributes, LinkButton needs href attributes,
   DropDownButton needs items, and SplitButton needs primary/secondary actions. A unified
   parameter-based approach doesn't fit this diversity well and would lead to confusing
   method signatures with many optional parameters.

**Pre-configured buttons solve common cases**
   The factory already provides pre-configured methods like :php:`createSaveButton()`,
   :php:`createBackButton()`, :php:`createCloseButton()`, :php:`createReloadButton()`,
   and :php:`createViewButton()` that handle the most common use cases with minimal code.

**Avoids duplication and maintenance burden**
   A parameter-based approach would require duplicating all button-specific configuration
   knowledge in both the button classes and the factory methods. This creates a maintenance
   burden where changes to a button's properties must be reflected in multiple locations.

**Keeps factory simple and maintainable**
   By keeping factory methods focused on instantiation, the ComponentFactory remains simple,
   maintainable, and easy to extend. Each button class maintains complete ownership of its
   own configuration logic.

**Fluent interface is already concise**
   The fluent interface provides a clear and concise API that is self-documenting:

   ..  code-block:: php

       // Common case - use pre-configured button
       $saveButton = $this->componentFactory->createSaveButton('myform');

       // Custom case - fluent interface is clear and flexible
       $customButton = $this->componentFactory->createInputButton()
           ->setName('custom_action')
           ->setValue('1')
           ->setTitle('Custom Action')
           ->setIcon($this->iconFactory->getIcon('actions-heart', IconSize::SMALL));

This design ensures the API remains maintainable, consistent with TYPO3 conventions,
and suitable for the diverse requirements of different button types while still providing
convenience methods for common patterns.

.. index:: Backend, PHP-API, ext:backend
