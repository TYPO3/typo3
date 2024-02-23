.. include:: /Includes.rst.txt

.. _feature-103186-1708686767:

=========================================================
Feature: #103186 - Introduce tree node status information
=========================================================

See :issue:`103186`

Description
===========

We've enhanced the backend tree component by extending tree nodes to
incorporate status information. These details serve to indicate the
status of nodes and provide supplementary information.

For instance, if a page undergoes changes within a workspace, it will
now display an indicator on the respective tree node. Additionally,
the status is appended to the node's title. This enhancement not only
improves visual clarity but also enhances information accessibility.

Each node can accommodate multiple status information, prioritized by
severity and urgency. Critical messages take precedence over other
status notifications.

.. code-block:: php

    new TreeItem(
        ...
        statusInformation: [
            new StatusInformation(
                label: 'A warning message',
                severity: ContextualFeedbackSeverity::WARNING,
                priority: 0,
                icon: 'actions-dot',
                overlayIcon: ''
            )
        ]
    ),

Impact
======

Tree nodes can now have status information. Workspace changes are
now reflected in the title of the node in addition to the indicator.

.. index:: Backend, JavaScript, ext:backend
