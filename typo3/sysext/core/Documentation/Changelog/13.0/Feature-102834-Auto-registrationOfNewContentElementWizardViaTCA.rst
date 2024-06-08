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

.. code-block:: typoscript

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

.. code-block:: php

    <?php

    return [
        'columns' => [
            'CType' => [
                'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.type',
                'config' => [
                    'type' => 'select',
                    'renderType' => 'selectSingle',
                    'items' => [
                        [
                            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.header',
                            'description' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.header.description',
                            'value' => 'header',
                            'icon' => 'content-header',
                            'group' => 'default',
                        ],
                    ],
                ],
            ],
        ],
    ];

.. note::

    Probably it will only be necessary to migrate the :typoscript:`description`,
    as the other values are already set most of the time. This can be done
    for plugins as well, using the :php:`$pluginDescription` argument of
    the :php:`ExtensionUtility::registerPlugin()` method.
    Additionally, the group :typoscript:`common` is now called :php:`default`.
    This will be migrated automatically for page TSconfig.


The :typoscript:`saveAndClose` option is now defined through TCA as well:

.. code-block:: typoscript

    mod.wizards.newContentElement.wizardItems {
        special.elements {
            div {
                saveAndClose = 1
            }
        }
    }

.. code-block:: php

    <?php

    return [
        'types' => [
            'div' => [
                'creationOptions' => [
                    'saveAndClose' => true,
                ],
            ],
        ],
    ];

The same goes for the default values. The option has been renamed from
`tt_content_defValues` to `defaultValues`:

.. code-block:: typoscript

    mod.wizards.newContentElement.wizardItems {
        special.elements {
            html {
                tt_content_defValues {
                    bodytext = some text
                }
            }
        }
    }

.. code-block:: php

    <?php

    return [
        'types' => [
            'html' => [
                'creationOptions' => [
                    'defaultValues' => [
                        'bodytext' => 'some text'
                    ],
                ],
            ],
        ],
    ];

Removing items from the select box still works as before through page
TSconfig :typoscript:`TCEFORM`. This will remove both the TCA items entry
and the wizard entry.

.. code-block:: typoscript

    TCEFORM.tt_content.CType {
        removeItems := addToList(header)
    }

To hide groups or elements in the wizard a new option :typoscript:`removeItems`
is available.

.. code-block:: typoscript

    # Before
    mod.wizards.newContentElement.wizardItems.special.show := removeFromList(html)

    # After
    mod.wizards.newContentElement.wizardItems.special.removeItems := addToList(html)

As mentioned, it's also possible to remove a whole group:

.. code-block:: typoscript

    # This will remove the "menu" group
    mod.wizards.newContentElement.wizardItems.removeItems := addToList(menu)

Impact
======

The groups and elements of the new Content Element Wizard are now registered
automatically from the TCA type field. This eases the creation of new content
elements and plugins for integrators and developers, since the whole definition
is done at a central place.

.. index:: Backend, TCA, TSConfig, ext:backend
