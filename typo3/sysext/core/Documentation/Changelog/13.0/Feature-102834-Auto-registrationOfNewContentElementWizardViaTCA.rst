.. include:: /Includes.rst.txt

.. _feature-102834-1705256634:

==========================================================================
Feature: #102834 - Auto-registration of New Content Element Wizard via TCA
==========================================================================

See :issue:`102834`

Description
===========

Content element types defined in TCA field :php:`CType` are now automatically
registered for the New Content Element Wizard. This replaces the former extra
step to define a wizard entry in page TSconfig :typoscript:`mod.wizards.newContentElement.wizardItems.<group>`.

The item entries :php:`value`, :php:`label`, :php:`description`, :php:`group`
and :php:`icon` are used to define the wizard entry.

The migration looks as follows:

Before:

..  code-block:: typoscript
    :caption: EXT:my_extension/Configuration/page.tsconfig

    # Add a new element (header) to the "common" group
    mod.wizards.newContentElement.wizardItems.common.elements.header {
      iconIdentifier = content-header
      title = LLL:EXT:backend/Resources/Private/Language/locallang_db_new_content_el.xlf:common_headerOnly_title
      description = LLL:EXT:backend/Resources/Private/Language/locallang_db_new_content_el.xlf:common_headerOnly_description
      tt_content_defValues {
        CType = header
      }
    }
    mod.wizards.newContentElement.wizardItems.common.show := addToList(header)

After:

..  code-block:: php
    :caption: EXT:my_extension/Configuration/TCA/Overrides/tt_content.php
    :emphasize-lines: 7-9

    // use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

    ExtensionManagementUtility::addPlugin(
        [
            'label' => 'LLL:EXT:backend/Resources/Private/Language/locallang_db_new_content_el.xlf:common_headerOnly_title',
            'description' => 'LLL:EXT:backend/Resources/Private/Language/locallang_db_new_content_el.xlf:common_headerOnly_description',
            'group' => 'default',
            'value' => 'header',
            'icon' => 'content-header',
        ],
        'CType',
        'my_extension',
    );

And for an Extbase plugin:

..  code-block:: php
    :caption: EXT:my_extension/Configuration/TCA/Overrides/tt_content.php
    :emphasize-lines: 7-9

    // use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

    ExtensionUtility::registerPlugin(
        'my_extension',         // extension name
        'my_plugin',            // plugin name
        'My plugin title',      // plugin title
        'my-icon',              // icon identifier
        'default',              // group
        'My plugin description' // plugin description
    );


..  note::

    Probably it will only be necessary to migrate the :typoscript:`description`,
    as the other values are already set most of the time. This can be done
    for plugins as well, using the :php:`$pluginDescription` argument of
    the :php:`ExtensionUtility::registerPlugin()` method.
    Additionally, the group :typoscript:`common` is now called :php:`default`.
    This will be migrated automatically for page TSconfig.


The :typoscript:`saveAndClose` option is now defined through TCA as well:

Before:

..  code-block:: typoscript
    :caption: EXT:my_extension/Configuration/page.tsconfig

    mod.wizards.newContentElement.wizardItems {
      special.elements {
        div {
          saveAndClose = 1
        }
      }
    }

After:

..  code-block:: php
    :caption: EXT:my_extension/Configuration/TCA/Overrides/tt_content.php

    <?php

    $GLOBALS['TCA']['tt_content'] = array_merge_recursive(
        $GLOBALS['TCA']['tt_content'],
        [
            'types' => [
                'div' => [
                    'creationOptions' => [
                        'saveAndClose' => true,
                    ],
                ],
            ],
        ]
    );

The same applies to the default values. The option has been renamed from
`tt_content_defValues` to `defaultValues`:

Before:

..  code-block:: typoscript
    :caption: EXT:my_extension/Configuration/page.tsconfig

    mod.wizards.newContentElement.wizardItems {
      special.elements {
        html {
          tt_content_defValues {
            bodytext = some text
          }
        }
      }
    }

After:

..  code-block:: php
    :caption: EXT:my_extension/Configuration/TCA/Overrides/tt_content.php

    <?php

    $GLOBALS['TCA']['tt_content'] = array_merge_recursive(
        $GLOBALS['TCA']['tt_content'],
        [
            'types' => [
                'html' => [
                    'creationOptions' => [
                        'defaultValues' => [
                            'bodytext' => 'some text'
                        ],
                    ],
                ],
            ],
        ]
    );

Removing items from the select box still works as before through page
TSconfig :typoscript:`TCEFORM`. This will remove both the TCA items entry
and the wizard entry.

..  code-block:: typoscript
    :caption: EXT:my_extension/Configuration/page.tsconfig

    TCEFORM.tt_content.CType {
      removeItems := addToList(header)
    }

To hide groups or elements in the wizard a new option :typoscript:`removeItems`
is available.

..  code-block:: typoscript
    :caption: EXT:my_extension/Configuration/page.tsconfig

    # Before
    mod.wizards.newContentElement.wizardItems.special.show := removeFromList(html)

    # After
    mod.wizards.newContentElement.wizardItems.special.removeItems := addToList(html)

As mentioned, it's also possible to remove a whole group:

..  code-block:: typoscript
    :caption: EXT:my_extension/Configuration/page.tsconfig

    # This will remove the "menu" group
    mod.wizards.newContentElement.wizardItems.removeItems := addToList(menu)

Impact
======

The groups and elements of the new Content Element Wizard are now registered
automatically from the TCA type field. This eases the creation of new content
elements and plugins for integrators and developers, since the whole definition
is done at a central place.

.. index:: Backend, TCA, TSConfig, ext:backend
