.. include:: /Includes.rst.txt

.. _feature-103211-1709036591:

=============================================
Feature: #103211 - Introduce tree node labels
=============================================

See :issue:`103211`

Description
===========

We've upgraded the backend tree component by extending tree nodes to
incorporate labels, offering enhanced functionality and additional
information.

Before the implementation of labels, developers and integrators
relied on :typoscript:`pageTree.backgroundColor.<pageid>` for visual cues,
which has been :ref:`deprecated <deprecation-103211-1709038752>` with TYPO3 v13.
However, these background colors lacked accessibility and meaningful context,
catering only to users with perfect eyesight and excluding those
dependent on screen readers or contrast modes.

With labels, we now cater to all editors. These labels not only offer
customizable color markings for tree nodes but also require an
associated label for improved accessibility.

Each node can support multiple labels, sorted by priority, with the
highest priority label taking precedence over others. Users can
assign a label to a node via user TSconfig, noting that only one label
can be set through this method.

..  code-block:: typoscript
    :caption: EXT:my_extension/Configuration/user.tsconfig

    options.pageTree.label.<pageid> {
        label = Campaign A
        color = #ff8700
    }

The labels can also be added by using the event
:php:`\TYPO3\CMS\Backend\Controller\Event\AfterPageTreeItemsPreparedEvent`.

..  code-block:: php
    :caption: EXT:my_extension/Classes/Backend/EventListener/ModifyPageTreeItems.php
    :emphasize-lines: 22-26

    <?php

    declare(strict_types=1);

    namespace MyVendor\MyExtension\Backend\EventListener;

    use TYPO3\CMS\Backend\Controller\Event\AfterPageTreeItemsPreparedEvent;
    use TYPO3\CMS\Backend\Dto\Tree\Label\Label;
    use TYPO3\CMS\Core\Attribute\AsEventListener;

    #[AsEventListener(
        identifier: 'my-extension/backend/modify-page-tree-items',
    )]
    final readonly class ModifyPageTreeItems
    {
        public function __invoke(AfterPageTreeItemsPreparedEvent $event): void
        {
            $items = $event->getItems();
            foreach ($items as &$item) {
                // Add special label for all pages with parent page ID 123
                if (($item['_page']['pid'] ?? null) === 123) {
                    $item['labels'][] = new Label(
                        label: 'Campaign B',
                        color: '#00658f',
                        priority: 1,
                    );
                }
            }
            $event->setItems($items);
        }
    }

Please note that only the marker for the label with the highest priority is
rendered. All additional labels will only be added to the title of the node.


Impact
======

Labels are now added to the node and their children, significantly
improving the clarity and accessibility of the tree component.

.. index:: Backend, JavaScript, TSconfig, ext:backend
