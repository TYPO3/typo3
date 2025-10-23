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

Extensions that implement or extend backend template components need to verify
their type declarations and update usage of changed methods.

New ComponentInterface
------------------------

A new :php:`ComponentInterface` has been introduced as the parent interface
for both :php:`ButtonInterface` and :php:`DropDownItemInterface`. This unifies
the common contract for all renderable backend components.

Both interfaces now extend :php:`ComponentInterface` which defines:

- :php:`isValid(): bool`
- :php:`getType(): string`
- :php:`render(): string`

Custom implementations of :php:`ButtonInterface` or :php:`DropDownItemInterface`
will cause a :php:`TypeError` if these return types are missing.

PositionInterface enforced
---------------------------

The :php:`PositionInterface` now enforces strict type hints:

- :php:`getPosition(): string`
- :php:`getGroup(): int`

This interface allows buttons to define their own fixed position and group,
which will automatically override the position/group parameters passed to
:php:`ButtonBar::addButton()`.

Icon nullability
-----------------

Icons are now consistently nullable across all button types. The
:php:`AbstractButton::$icon` property and related getter/setter methods now
use :php:`?Icon` instead of :php:`Icon`.

This affects classes extending :php:`AbstractButton` (:php:`LinkButton`,
:php:`InputButton`, :php:`SplitButton`).

..  note::
    While icons are technically optional at the type level, validation methods
    may still require icons for buttons to be considered valid.

Method signature changes
-------------------------

Several methods now have stricter parameter types or modified signatures:

- :php:`MenuItem::isValid()` and :php:`Menu::isValid()` no longer accept parameters
- :php:`AbstractDropDownItem::render()` now declares :php:`string` return type
- Various setter methods now require proper type hints for their parameters

Return type consistency
------------------------

Abstract classes use :php:`static` return types for better inheritance support,
while concrete implementations may use :php:`self` or :php:`static` depending
on extensibility requirements.

SplitButton API improvement
----------------------------

The :php:`SplitButton::getButton()` method has been replaced with
:php:`getItems()` which returns a type-safe :php:`SplitButtonItems` DTO
instead of an untyped array.

**Old (removed):**

..  code-block:: php

    public function getButton(): array  // Returns array with magic keys 'primary' and 'options'

**New:**

..  code-block:: php

    public function getItems(): SplitButtonItems  // Returns typed DTO

The :php:`SplitButtonItems` DTO provides:

- :php:`public readonly AbstractButton $primary` - The primary action button
- :php:`public readonly array $options` - Array of option buttons

This change improves type safety and prevents runtime errors from accessing
non-existent array keys.

Affected Migration
==================

Extension authors should:

1. **Verify custom button implementations** have correct return types on interface methods
2. **Check custom classes extending abstract buttons** use appropriate return types
3. **Update isValid() calls** on MenuItem and Menu objects (remove the parameter)
4. **Handle nullable icons** when working with button getIcon() methods

Example: Implementing ButtonInterface
--------------------------------------

..  code-block:: php

    // Before
    class CustomButton implements ButtonInterface {
        public function isValid() { ... }
        public function render() { ... }
        public function getType() { ... }
    }

    // After
    class CustomButton implements ButtonInterface {
        public function isValid(): bool { ... }
        public function render(): string { ... }
        public function getType(): string { return static::class; }
    }

Example: Working with MenuItem/Menu
------------------------------------

..  code-block:: php

    // Before
    if ($menuItem->isValid($menuItem)) { ... }

    // After
    if ($menuItem->isValid()) { ... }

Example: Nullable icons
-----------------------

..  code-block:: php

    // Handle nullable icon return
    $icon = $button->getIcon();  // Now returns ?Icon
    $html = $icon?->render() ?? '';

Example: Using SplitButton with typed DTO
------------------------------------------

If you were directly accessing the :php:`getButton()` method:

..  code-block:: php

    // Before
    $items = $splitButton->getButton();
    $primary = $items['primary'];  // Magic string key
    $options = $items['options'];  // Magic string key

    // After
    $items = $splitButton->getItems();
    $primary = $items->primary;  // Type-safe property access
    $options = $items->options;  // Type-safe property access

.. index:: Backend, PHP-API, NotScanned, ext:backend
