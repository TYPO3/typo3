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
relied on :tsconfig:`pageTree.backgroundColor.<pageid>` for visual cues.
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

..  code-block:: tsconfig

    options.pageTree.label.<pageid> {
        label = Campaign A
        color = #ff8700
    }

The labels can also be added by using the event
:php:`\TYPO3\CMS\Backend\Controller\Event\AfterPageTreeItemsPreparedEvent`.

..  code-block:: php

    $items = $event->getItems();
    foreach ($items as &$item) {
        $item['labels'][] = new Label(
            label: 'Campaign B',
            color: #00658f,
            priority: 1,
        );
    }

Please note that only the marker for the label with the highest priority is
rendered. All additional labels will only be added to the title of the node.


Impact
======

Labels are now added to the node and their children, significantly
improving the clarity and accessibility of the tree component.

.. index:: Backend, JavaScript, TSconfig, ext:backend
