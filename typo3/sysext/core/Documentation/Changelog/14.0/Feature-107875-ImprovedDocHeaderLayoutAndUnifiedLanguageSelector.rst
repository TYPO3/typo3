.. include:: /Includes.rst.txt

.. _feature-107875-1762212144:

==========================================================================
Feature: #107875 - Improved DocHeader layout and unified language selector
==========================================================================

See :issue:`107875`

Description
===========

The TYPO3 backend document header (DocHeader) has been reorganized to provide
a more consistent and intuitive user experience across all backend modules.

This feature includes the following improvements:

Unified language selector
-------------------------

All backend modules with multi-language support now use a consistent language
selector in the top-right area of the DocHeader. This includes:

- Web > Page module
- Web > List module
- Web > View module
- Record editing (FormEngine)

The language selector displays the currently selected language as the button
text (e.g., "English", "German" and the sepcial "All languages") with a
descriptive "Language:" prefix for screen readers. This provides immediate
visual feedback about the active language while maintaining full accessibility.

The language selector now allows creating new page translations directly from
the dropdown (except in View Mode). The dropdown is organized with existing
translations shown first, followed by a divider and a "Create new translation"
section header. Languages that don't have translations yet appear in this
separate section.

Selecting a language from the "Create new translation" section will:

1. Create the page translation via the DataHandler
2. Open the FormEngine to edit the newly created translation
3. Provide a consistent workflow across all modules

Unified module actions menu
----------------------------

Modules with submodules or multiple "actions" now consistently display these
options as a dropdown button in the DocHeader button bar. The dropdown button
displays the currently active module or action as the button text, providing
clear visual context to users.

For accessibility, each dropdown includes a descriptive label:
- "Display mode" for the :guilabel:`Web > Page` module view selector (Layout vs Language comparison)
- "Module actions" for general module/submodule navigation
- Custom labels set by individual menu configurations

These labels are implemented using visually-hidden label elements, ensuring
screen readers announce both the dropdown's purpose (e.g., "Display mode:")
and the current selection (e.g., "Layout").

The dropdown is automatically hidden when only a single action is available,
reducing visual clutter and displaying the dropdown only when navigation
choices are actually available.

Examples include :guilabel:`System > Backend Users` with Overview, Users,
Groups actions, as well as :guilabel:`Web > Info` with "Pagetree Overview",
"Localization Overview", etc.

This replaces the previous inconsistent mixture of:

- Back buttons
- Inline button groups
- Module menus in different locations

Reorganized DocHeader layout
-----------------------------

The DocHeader now has a more logical structure:

**Top row (navigation bar):**

- **Left side**: Breadcrumb navigation showing the current location
- **Right side**: Language selector (when applicable)

**Second row (button bar):**

- **Left side, group 0**: Module actions dropdown (when applicable)
- **Left side, groups 1+**: Additional action buttons and context-specific buttons (Save, Close, etc.)
- **Right side**: Functional buttons like "Reload", "Bookmark" or "Clear cache"

Technical details
=================

Module actions dropdown
-----------------------

Controllers can use the existing :php:`ModuleTemplate::makeDocHeaderModuleMenu()`
method to automatically create a module actions dropdown based on the module
configuration:

..  code-block:: php

    $view = $this->moduleTemplateFactory->create($request);
    $view->makeDocHeaderModuleMenu();

For backward compatibility, the MenuRegistry API is automatically converted
to module actions dropdowns.

Language selector integration
------------------------------

The language selector is integrated via the
:php:`DocHeaderComponent::setLanguageSelector()` method.

First, inject the :php:`ComponentFactory` into your controller:

..  code-block:: php

    use TYPO3\CMS\Backend\Template\Components\ComponentFactory;

    public function __construct(
        private readonly ComponentFactory $componentFactory,
    ) {}

Then build the language selector dropdown with language items:

..  code-block:: php

    // Get available site languages for the current page
    $siteLanguages = $this->site->getAvailableLanguages($pageRecord, $backendUser);
    $currentLanguageId = (int)$moduleData->get('language');

    // Build dropdown button
    $languageDropdown = $this->componentFactory->createDropDownButton()
        ->setLabel($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.language'))
        ->setShowLabelText(true)
        ->setShowActiveLabelText(true);

    // Create dropdown items for each language
    foreach ($siteLanguages as $siteLanguage) {
        $languageId = $siteLanguage->getLanguageId();
        $isActive = $currentLanguageId === $languageId;

        // Build URL for language selection
        $href = $this->uriBuilder->buildUriFromRoute('web_layout', [
            'id' => $pageId,
            'language' => $languageId,
        ]);

        $item = $this->componentFactory->createDropDownRadio()
            ->setHref($href)
            ->setLabel($siteLanguage->getTitle())
            ->setIcon($this->iconFactory->getIcon($siteLanguage->getFlagIdentifier()))
            ->setActive($isActive);

        $languageDropdown->addItem($item);
    }

    $view->getDocHeaderComponent()->setLanguageSelector($languageDropdown);

The language selector component is automatically rendered in the top-right area
of the DocHeader navigation bar.

..  note::

    For advanced implementations that include "Create new translation" functionality,
    refer to the :php:`PageLayoutController` in EXT:backend. The controller demonstrates
    how to separate existing translations from languages that can be created, using
    :php:`DropDownDivider` and :php:`DropDownHeader` components to organize the
    dropdown menu.

Accessibility implementation
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

The :php:`setShowActiveLabelText(true)` method enables display of the active
item's label while maintaining accessibility. When enabled with
:php:`setShowLabelText(true)`, the button renders the dropdown's label in a
visually-hidden span followed by the active item's label:

- Visual users see: "English" (active language)
- Screen reader users hear: "Language: English" (descriptive label + active language)

When :php:`setShowLabelText(false)`, the accessibility information is provided
through aria-label and title attributes instead.

This pattern applies to all dropdown buttons in the DocHeader:

.. code-block:: php

    $dropdown->setLabel('Display mode')  // Descriptive label for context
        ->setShowLabelText(true)  // Show label text
        ->setShowActiveLabelText(true);  // Show active item label

Impact
======

The changes provide a more consistent and intuitive user experience. Users can
now create and switch between page translations directly from the DocHeader
across all relevant modules, without needing to navigate to specific page areas.
The clear separation between existing translations and languages that can be
created improves discoverability and reduces confusion about which actions are
available.

The language selector and module actions dropdowns now display the currently
active selection as the button text, providing immediate visual feedback. Users
can see at a glance which language is selected or which module action is active,
without needing to open the dropdown.

Accessibility has been significantly improved through the use of descriptive
labels rendered as visually-hidden elements or aria-label attributes. Screen
reader users now receive proper context about each dropdown's purpose
(e.g., "Language:", "Display mode:", "Module actions") combined with the
current selection, ensuring equal access to navigation functionality.

The module actions dropdown makes it immediately clear which module or action is
currently active, and which alternatives are available. The dropdown is only
shown when multiple options exist, reducing visual clutter. Removal of redundant
back buttons in favor of consistent breadcrumb navigation further reduces visual
noise.

.. index:: Backend, UX, ext:backend
