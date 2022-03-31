.. include:: /Includes.rst.txt

===================================================================
Feature: #90203 - Make workspace available in TypoScript conditions
===================================================================

See :issue:`90203`

Description
===========

A new TypoScript expression language variable :typoscript:`workspace` has been added.
It can be used to match a given expression against common workspace parameters.

Currently, the parameters :typoscript:`workspaceId`, :typoscript:`isLive` and :typoscript:`isOffline` are supported.

Examples
--------

Match the current workspace id:

.. code-block:: typoscript

   [workspace.workspaceId === 3]
       # Current workspace id equals: 3
   [end]

Match against current workspace state:

.. code-block:: typoscript

   [workspace.isLive]
       # Current workspace is live
   [end]

   [workspace.isOffline]
       # Current workspace is offline
   [end]


Impact
======

The new feature allows matching against several workspace parameters within TypoScript.

.. index:: TypoScript
