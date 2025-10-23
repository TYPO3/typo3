..  include:: /Includes.rst.txt

..  _feature-107824-1761297638:

==========================================================
Feature: #107824 - ComponentFactory for backend components
==========================================================

See :issue:`107824`

Description
===========

A new :php:`\TYPO3\CMS\Backend\Template\Components\ComponentFactory` class
has been introduced as the central location for all backend component creation.
It provides factory methods for buttons and menu components, offering both
pre-configured buttons for common patterns and basic component creation methods.

The :php:`ComponentFactory` serves multiple purposes:

1. **Pre-configured common buttons** - Ready-to-use buttons like back, close, save,
   and refresh with standardized icons, labels, and behavior
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

Available Factory Methods
--------------------------

The :php:`ComponentFactory` provides two categories of methods:

**Pre-configured Common Buttons:**

* :php:`createBackButton(string $returnUrl)` - Standard back navigation with "Go back" label
* :php:`createCloseButton(string $closeUrl)` - Close button for modal-like views
* :php:`createSaveButton(string $formName = '')` - Standard save button for forms
* :php:`createRefreshButton()` - Reload current view

**Basic Button Creation:**

* :php:`createLinkButton()` - Creates a new LinkButton instance
* :php:`createInputButton()` - Creates a new InputButton instance
* :php:`createGenericButton()` - Creates a new GenericButton instance
* :php:`createSplitButton()` - Creates a new SplitButton instance
* :php:`createDropDownButton()` - Creates a new DropDownButton instance
* :php:`createFullyRenderedButton()` - Creates a new FullyRenderedButton instance
* :php:`createShortcutButton()` - Creates a new ShortcutButton instance

**Menu Component Creation:**

* :php:`createMenu()` - Creates a new Menu instance
* :php:`createMenuItem()` - Creates a new MenuItem instance

.. note::

   The corresponding :php:`ButtonBar::make*()`, :php:`Menu::makeMenuItem()`, and
   :php:`MenuRegistry::makeMenu()` methods are now deprecated.
   See :ref:`deprecation-107824-1761297638`

Impact
======

Backend module developers should now inject :php:`ComponentFactory` in their controllers
to create buttons. The factory provides:

1. **Pre-configured buttons** for common patterns (back, close, save, refresh)
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

Fluent Interface Improvements
==============================

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
   :php:`createBackButton()`, :php:`createCloseButton()`, and :php:`createRefreshButton()`
   that handle the most common use cases with minimal code.

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
