..  include:: /Includes.rst.txt

..  _breaking-107823-1730000000:

================================================================================
Breaking: #107823 - Strict typing and API cleanup in backend template components
================================================================================

See :issue:`107823`

Description
===========

The backend template components system (buttons, dropdown items, and menus) has
been modernized with strict type hints, consistent return types, and improved
architecture to enhance type safety and developer experience.

Impact
======

Extensions that implement or extend backend template components must verify
their type declarations and update any usage of changed methods.

New ComponentInterface
----------------------

A new :php:`\TYPO3\CMS\Backend\Template\Components\ComponentInterface`
has been introduced as the parent interface for both
:php-short:`\TYPO3\CMS\Backend\Template\Components\ButtonInterface` and
:php-short:`\TYPO3\CMS\Backend\Template\Components\DropDownItemInterface`.
This unifies the common contract for all renderable backend components.

Both interfaces now extend
:php-short:`\TYPO3\CMS\Backend\Template\Components\ComponentInterface`,
which defines:

*   :php:`isValid(): bool`
*   :php:`getType(): string`
*   :php:`render(): string`

Custom implementations of :php-short:`\TYPO3\CMS\Backend\Template\Components\ButtonInterface`
or :php-short:`\TYPO3\CMS\Backend\Template\Components\DropDownItemInterface` will
now trigger a :php:`\TypeError` if these return types are missing.

PositionInterface enforced
--------------------------

The :php:`\TYPO3\CMS\Backend\Template\Components\PositionInterface` now enforces
strict type hints:

*   :php:`getPosition(): string`
*   :php:`getGroup(): int`

This interface allows buttons to define their own fixed position and group,
which automatically override the position and group parameters passed to
:php:`ButtonBar::addButton()`.

Icon nullability
----------------

Icons are now consistently nullable across all button types. The
:php:`AbstractButton::$icon` property and related getter/setter methods now use
:php:`?Icon` instead of :php:`Icon`.

This affects classes extending
:php-short:`\TYPO3\CMS\Backend\Template\Components\Buttons\AbstractButton`, including
:php-short:`\TYPO3\CMS\Backend\Template\Components\Buttons\LinkButton`,
:php-short:`\TYPO3\CMS\Backend\Template\Components\Buttons\InputButton`, and
:php-short:`\TYPO3\CMS\Backend\Template\Components\Buttons\SplitButton`.

..  note::
    While icons are technically optional at the type level, validation methods
    may still require icons for buttons to be considered valid.

Method signature changes
------------------------

Several methods now have stricter parameter types or modified signatures:

*   :php:`MenuItem::isValid()` and :php:`Menu::isValid()` no longer accept
    parameters
*   :php:`AbstractDropDownItem::render()` now declares a :php:`string` return
    type
*   Various setter methods now require strict type hints for their parameters

Return type consistency
-----------------------

Abstract classes now use :php:`static` return types for better inheritance
support, while concrete implementations may use :php:`self` or :php:`static`
depending on extensibility requirements.

SplitButton API improvement
---------------------------

The :php:`SplitButton::getButton()` method has been replaced with
:php:`getItems()`, which returns a type-safe
:php-short:`\TYPO3\CMS\Backend\Template\Components\Buttons\SplitButtonItems` DTO
instead of an untyped array.

**Old (removed):**

..  code-block:: php

    public function getButton(): array  // Returns array with magic keys 'primary' and 'options'

**New:**

..  code-block:: php

    public function getItems(): SplitButtonItems  // Returns typed DTO

The :php:`SplitButtonItems` DTO provides:

*   :php:`public readonly AbstractButton $primary` - The primary action button
*   :php:`public readonly array $options` - Array of option buttons

This change improves type safety and prevents runtime errors from accessing
non-existent array keys.

Affected installations
======================

All TYPO3 instances with custom backend components, such as buttons, menus, or
dropdown items, that extend or implement the affected interfaces are impacted.

Migration
=========

Extension authors should:

1.  **Verify custom button implementations** have correct return types on
    interface methods.
2.  **Check custom classes extending abstract buttons** use proper strict types.
3.  **Update `isValid()` calls** on :php:`MenuItem` and :php:`Menu` objects
    (remove the parameter).
4.  **Handle nullable icons** when working with :php:`getIcon()` methods.

Example: implementing ButtonInterface
-------------------------------------

..  code-block:: php

    use TYPO3\CMS\Backend\Template\Components\ButtonInterface;

    // Before
    class CustomButton implements ButtonInterface {
        public function isValid() { ... }
        public function render() { ... }
        public function getType() { ... }
    }

..  code-block:: php

    use TYPO3\CMS\Backend\Template\Components\ButtonInterface;

    // After
    class CustomButton implements ButtonInterface {
        public function isValid(): bool { ... }
        public function render(): string { ... }
        public function getType(): string { return static::class; }
    }

Example: working with MenuItem/Menu
-----------------------------------

..  code-block:: php

    // Before
    if ($menuItem->isValid($menuItem)) { ... }

..  code-block:: php

    // After
    if ($menuItem->isValid()) { ... }

Example: nullable icons
-----------------------

..  code-block:: php

    // Handle nullable icon return
    $icon = $button->getIcon();  // Now returns ?Icon
    $html = $icon?->render() ?? '';

Example: using SplitButton with typed DTO
-----------------------------------------

If you were directly accessing the :php:`getButton()` method:

..  code-block:: php

    // Before
    $items = $splitButton->getButton();
    $primary = $items['primary'];  // Magic string key
    $options = $items['options'];  // Magic string key

..  code-block:: php

    // After
    $items = $splitButton->getItems();
    $primary = $items->primary;  // Type-safe property access
    $options = $items->options;  // Type-safe property access

..  index:: Backend, PHP-API, NotScanned, ext:backend
