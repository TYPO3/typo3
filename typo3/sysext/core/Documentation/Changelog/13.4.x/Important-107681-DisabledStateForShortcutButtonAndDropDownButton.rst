..  include:: /Includes.rst.txt

..  _important-107681-1760427687:

=========================================================================
Important: #107681 - Disabled state for ShortcutButton and DropDownButton
=========================================================================

See :issue:`107681`

Description
===========

The :php:`ShortcutButton` and :php:`DropDownButton` classes in the TYPO3
backend button bar system have been enhanced with new methods to support
disabled state functionality. This brings them in line with other button
types that already support the disabled state.

Two new methods have been added to both classes:

-   :php:`isDisabled(): bool` - Checks if the button is disabled
-   :php:`setDisabled(bool $disabled)` - Sets the disabled state of the button

When a button is disabled, it is rendered with appropriate HTML attributes
and CSS classes to indicate its non-interactive state. For :php:`ShortcutButton`,
the disabled state is applied to both the simple button rendering and the
dropdown rendering modes.

Impact
======

Extension developers can now programmatically disable shortcut and dropdown
buttons in the TYPO3 backend, preventing user interaction when needed. This is
particularly useful for:

-   Preventing operations during form initialization
-   Disabling buttons during async operations
-   Conditional button availability based on application state
-   Improving user experience on slow network connections

The disabled state is properly propagated through the rendering process:

-   For shortcut buttons rendered as :php:`GenericButton`, the disabled
    attribute is added to the button element
-   For shortcut buttons rendered as :php:`DropDownButton`, the disabled
    state is passed to the dropdown button

Migration
=========

No migration is required. This change is fully backward compatible as it only
adds new optional functionality. Existing code will continue to work without
modifications.

**Example usage for disabling a shortcut button:**

..  code-block:: php
    :caption: EXT:my_extension/Classes/Controller/MyController.php

    $shortcutButton = $buttonBar->makeShortcutButton()
        ->setRouteIdentifier('my_module')
        ->setDisplayName('My Module')
        ->setArguments(['id' => $pageId])
        ->setDisabled(true);
    $buttonBar->addButton($shortcutButton);

**Example usage for disabling a dropdown button:**

..  code-block:: php
    :caption: EXT:my_extension/Classes/Controller/MyController.php

    $dropdownButton = GeneralUtility::makeInstance(DropDownButton::class)
        ->setLabel('Actions')
        ->setIcon($iconFactory->getIcon('actions-menu'))
        ->setDisabled(true);
    $dropdownButton->addItem($item1);
    $dropdownButton->addItem($item2);
    $buttonBar->addButton($dropdownButton);

**Example usage for checking disabled state:**

..  code-block:: php
    :caption: EXT:my_extension/Classes/Controller/MyController.php

    if ($shortcutButton->isDisabled()) {
        // Handle disabled state
    }

..  index:: Backend, PHP-API, ext:backend
