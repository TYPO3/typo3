.. include:: /Includes.rst.txt

.. _feature-99459-1672857664:

================================================================
Feature: #99459 - Respect record type while creating new records
================================================================

See :issue:`99459`

Description
===========

The "Create new record" component in the backend, which is accessbile in
the :guilabel:`Web > List` module, has been enhanced to automatically detect
and display all available record types for tables that support sub-schemas
(record types). This makes creating specific record types much easier and avoids
the need to change the type in the editing form, as this often leads to invalid
record state stored in the database.

The interface now features automatic record type detection. Tables with multiple
record types are automatically expanded to show all available types in a
collapsible dropdown interface.

The options to create new pages now also shows the different page types
(:php:`doktype`) as expandable options when creating new pages "inside" or "after".

To disable direct creation of a specific record type, a new TCA option
:php:`['creationOptions']['enableDirectRecordTypeCreation']` is available on
a record types level:

..  code-block:: php

    // Disable direct creation of shortcuts
    $GLOBALS['TCA']['pages']['types'][(string)\TYPO3\CMS\Core\Domain\Repository\PageRepository::DOKTYPE_SHORTCUT]['creationOptions']['enableDirectRecordTypeCreation'] = false;

With :php:`['creationOptions']['title']` it's possible to set an individual
title for the record type, to be used as label on the creation link.

Additionally, the new PSR-14 :php:`ModifyNewRecordCreationLinksEvent` allows
for complete customization of the creation links.

Impact
======

For tables that support sub-schemas (multiple record types), the new record
wizard automatically detects all available types and displays them in a
collapsible interface. This includes:

* All tables with TCA type fields (like :php:`sys_file_collection` or :php:`index_config`)
* The :php:`pages` table with its different doktypes
* Extension tables with custom record types

.. note::

    The collapsible interface keeps the view clean while providing access
    to all options. Icons and labels are automatically generated for each
    record type based on TCA.

The :php:`ModifyNewRecordCreationLinksEvent` provides complete control over
the creation links structure, allowing extensions to:

* Add custom record creation options
* Modify existing groups and items
* Override icons, labels, and URLs
* Create entirely custom wizard interfaces

Data Structure
==============

The event works with a nested array structure representing grouped creation links:

..  code-block:: php

    [
        'content' => [
            'title' => 'Content',
            'icon' => '<img src="..." />',
            'items' => [
                'sys_file_collection' => [
                    'label' => 'File Collection',
                    'icon' => '<typo3-backend-icon ...>',
                    'types' => [
                        'static' => [
                            'url' => '/typo3/record/edit?edit[sys_file_collection][1]=new&defVals[sys_file_collection][type]=static',
                            'icon' => '<typo3-backend-icon ...>',
                            'label' => 'Static File Collection'
                        ],
                        'folder' => [
                            'url' => '/typo3/record/edit?edit[sys_file_collection][1]=new&defVals[sys_file_collection][type]=folder',
                            'icon' => '<typo3-backend-icon ...>',
                            'label' => 'Folder from Storage'
                        ]
                    ]
                ]
            ]
        ],
        'pages' => [
            'title' => 'Create New Page',
            'icon' => '<typo3-backend-icon ...>',
            'items' => [
                'inside' => [
                    'label' => 'Page (inside)',
                    'icon' => '<typo3-backend-icon ...>',
                    'types' => [
                        '1' => [
                            'url' => '/typo3/record/edit?edit[pages][1]=new&defVals[pages][doktype]=1',
                            'icon' => '<typo3-backend-icon ...>',
                            'label' => 'Standard Page'
                        ],
                        '254' => [
                            'url' => '/typo3/record/edit?edit[pages][1]=new&defVals[pages][doktype]=254',
                            'icon' => '<typo3-backend-icon ...>',
                            'label' => 'Folder'
                        ]
                    ]
                ]
            ]
        ]
    ]

Event Listener Example
======================

The event provides access to:

* :php:`$event->groupedCreationLinks` - The complete structure of creation links
* :php:`$event->pageTS` - The current page's TSconfig array
* :php:`$event->pageId` - The current page ID
* :php:`$event->request` - The current server request object

This allows for comprehensive customization while maintaining backward
compatibility with existing customizations.

..  code-block:: php
    :caption: EXT:my_extension/Classes/EventListener/CustomizeNewRecordCreationLinksEventListener.php

    <?php

    declare(strict_types=1);

    namespace MyVendor\MyExtension\EventListener;

    use TYPO3\CMS\Backend\Controller\Event\ModifyNewRecordCreationLinksEvent;
    use TYPO3\CMS\Backend\Routing\UriBuilder;
    use TYPO3\CMS\Core\Imaging\IconFactory;
    use TYPO3\CMS\Core\Imaging\IconSize;

    final readonly class CustomizeNewRecordWizardEventListener
    {
        public function __construct(
            private IconFactory $iconFactory,
            private UriBuilder $uriBuilder,
        ) {}

        #[AsEventListener]
        public function __invoke(ModifyNewRecordCreationLinksEvent $event): void
        {
            // Add a custom creation group
            $customGroup = [
                'title' => 'Custom Records',
                'icon' => $this->iconFactory->getIcon('apps-pagetree-category')->render(),
                'items' => [
                    'tx_myext_domain_model_item' => [
                        'url' => (string)$this->uriBuilder->buildUriFromRoute('record_edit', [
                            'edit' => ['tx_myext_domain_model_item' => [$event->pageId => 'new']],
                            'returnUrl' => $event->request->getAttribute('normalizedParams')->getRequestUri(),
                        ]),
                        'icon' => $this->iconFactory->getIconForRecord('tx_myext_domain_model_item', []),
                        'label' => 'Custom Item',
                    ]
                ]
            ];

            // Add the custom group to the existing structure
            $event->groupedCreationLinks['custom'] = $customGroup;

            // Modify existing groups - for example, remove specific items
            if (isset($event->groupedCreationLinks['system']['items']['sys_template'])) {
                unset($event->groupedCreationLinks['system']['items']['sys_template']);
            }

            // Add custom types to an existing table
            if (isset($event->groupedCreationLinks['content']['items']['sys_note'])) {
                $event->groupedCreationLinks['content']['items']['sys_note']['types'] = [
                    'important' => [
                        'url' => (string)$this->uriBuilder->buildUriFromRoute('record_edit', [
                            'edit' => ['sys_note' => [$event->pageId => 'new']],
                            'defVals' => ['sys_note' => ['category' => '1']],
                            'returnUrl' => $event->request->getAttribute('normalizedParams')->getRequestUri(),
                        ]),
                        'icon' => $this->iconFactory->getIcon('status-dialog-warning', IconSize::SMALL),
                        'label' => 'Important Note',
                    ],
                    'info' => [
                        'url' => (string)$this->uriBuilder->buildUriFromRoute('record_edit', [
                            'edit' => ['sys_note' => [$event->pageId => 'new']],
                            'defVals' => ['sys_note' => ['category' => '0']],
                            'returnUrl' => $event->request->getAttribute('normalizedParams')->getRequestUri(),
                        ]),
                        'icon' => $this->iconFactory->getIcon('status-dialog-information', IconSize::SMALL),
                        'label' => 'Information Note',
                    ]
                ];
            }
        }
    }

.. index:: Backend, ext:backend
