..  include:: /Includes.rst.txt

..  _important-107594-1759439282:

======================================================
Important: #107594 - Icon overlay for TCA select items
======================================================

See :issue:`107594`

Description
===========

The ability to define an icon overlay for items in the "New Content Element"
wizard was originally introduced in :issue:`92942` using Page TSconfig, but was
accidentally removed during the web-component migration in :issue:`100065` and
then restored in :issue:`105253`.

In the meantime, :issue:`102834` added auto-registration of wizard items
directly from TCA. Since icon overlays defined in Page TSconfig duplicate
configuration that can now be specified in TCA, the recommended approach is to
define icon overlays directly in TCA using the new :php:`iconOverlay` option
for select items.

The :php:`iconOverlay` property is now supported in the :php:`SelectItem`
component, enabling icon overlays for wizard items that are auto-registered
via TCA.

Impact
======

Icon overlays for New Content Element Wizard items can now be defined directly
in TCA alongside other item properties like :php:`icon`, :php:`label`,
:php:`description`, and :php:`group`.

This consolidates configuration in a single location and eliminates the need
for separate Page TSconfig definitions. Page TSconfig icon overlays remain
supported for backward compatibility, but TCA-based configuration is now the
recommended approach.

Migration
=========

**Previous approach using Page TSconfig (still works, but no longer recommended):**

..  code-block:: typoscript
    :caption: EXT:my_extension/Configuration/page.tsconfig

    mod.wizards.newContentElement.wizardItems {
        my_group.elements {
            my_element {
                iconIdentifier = content-header
                iconOverlay = actions-approve
                title = LLL:EXT:my_extension/Resources/Private/Language/locallang.xlf:my_element_title
                description = LLL:EXT:my_extension/Resources/Private/Language/locallang.xlf:my_element_description
                tt_content_defValues {
                    CType = my_element
                }
            }
        }
    }

**Recommended approach using TCA:**

..  code-block:: php
    :caption: EXT:my_extension/Configuration/TCA/Overrides/tt_content.php

    use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

    ExtensionManagementUtility::addRecordType(
        [
            'label' => 'LLL:EXT:my_extension/Resources/Private/Language/locallang.xlf:my_element_title',
            'description' => 'LLL:EXT:my_extension/Resources/Private/Language/locallang.xlf:my_element_description',
            'value' => 'my_element',
            'icon' => 'content-header',
            'iconOverlay' => 'actions-approve',
            'group' => 'my_group'
        ],
        '...',
    );

..  important::

    While Page TSconfig-based icon overlay configuration remains functional for
    backward compatibility, it is recommended to migrate to TCA-based
    configuration to avoid duplicating configuration across multiple files.

..  index:: Backend, TCA, TSConfig, ext:backend
