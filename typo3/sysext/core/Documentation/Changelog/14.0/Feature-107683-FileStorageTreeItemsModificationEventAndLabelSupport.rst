..  include:: /Includes.rst.txt

..  _feature-107683-1234567890:

====================================================================================
Feature: #107683 - File storage tree items modification event and label support
====================================================================================

See :issue:`107683`

Description
===========

Similar to the page tree functionality introduced in TYPO3 v12 and v13, the
file storage tree now supports modification of tree items through the new
PSR-14 event
:php:`\TYPO3\CMS\Backend\Controller\Event\AfterFileStorageTreeItemsPreparedEvent`.

The event is dispatched in the file storage
:php-short:`\TYPO3\CMS\Backend\Controller\FileStorage\TreeController` after the
storage tree items have been resolved and prepared. It provides the current
PSR-7 request as well as the collection of file storage tree items.

Additionally, labels can now be added to file storage tree nodes via user
TSconfig, using the combined identifier of the folder:

..  code-block:: typoscript
    :caption: EXT:my_extension/Configuration/user.tsconfig

    options.folderTree.label.1:/campaigns {
        label = Main Storage
        color = #ff8700
    }

Labels and status information
=============================

Similar to the page tree, labels and status information can be added to file
storage tree nodes. These features significantly improve the clarity and
accessibility of the file storage tree component:

*   **Labels**: Each node can support multiple labels, sorted by priority.
    The highest priority label takes precedence, and only its marker is
    rendered. All additional labels are added to the title attribute of the
    node.

*   **Status information**: Can be added through the event to provide
    additional visual feedback. Like labels, status information is sorted by
    priority. Only the highest priority status indicator is displayed, while
    all status labels are added to the title attribute.

Example event listener
======================

..  code-block:: php
    :caption: EXT:my_extension/Classes/Backend/EventListener/ModifyFileStorageTreeItems.php
    :emphasize-lines: 23-27

    <?php

    declare(strict_types=1);

    namespace MyVendor\MyExtension\Backend\EventListener;

    use TYPO3\CMS\Backend\Controller\Event\AfterFileStorageTreeItemsPreparedEvent;
    use TYPO3\CMS\Backend\Dto\Tree\Label\Label;
    use TYPO3\CMS\Backend\Dto\Tree\Status\StatusInformation;
    use TYPO3\CMS\Core\Attribute\AsEventListener;
    use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;

    #[AsEventListener(
        identifier: 'my-extension/backend/modify-file-storage-tree-items',
    )]
    final readonly class ModifyFileStorageTreeItems
    {
        public function __invoke(AfterFileStorageTreeItemsPreparedEvent $event): void
        {
            $items = $event->getItems();

            foreach ($items as &$item) {
                // Add special label for storage with uid 1
                if ($item['resource']->getCombinedIdentifier() === '1:/campaigns/') {
                    $item['labels'][] = new Label(
                        label: 'A label',
                        color: '#abcdef',
                        priority: 10,
                    );
                    $item['statusInformation'][] = new StatusInformation(
                        label: 'An important information',
                        severity: ContextualFeedbackSeverity::INFO,
                        priority: 10,
                        icon: 'content-info',
                    );
                }
            }

            $event->setItems($items);
        }
    }

..  note::
    The combined identifier used in TSconfig must not be URL-encoded.
    For example, use :typoscript:`1:/` instead of :typoscript:`1%3A%2F`.

Impact
======

It is now possible to modify the prepared file storage tree items before they
are returned by the
:php-short:`\TYPO3\CMS\Backend\Controller\FileStorage\TreeController`,
using the new PSR-14 event
:php-short:`\TYPO3\CMS\Backend\Controller\Event\AfterFileStorageTreeItemsPreparedEvent`.
Additionally, labels can be assigned to file storage tree nodes via user
TSconfig.

Using these functionalities helps provide visual cues and improved
accessibility for editors working with file storages and folders.

..  index:: Backend, PHP-API, TSConfig, ext:backend
