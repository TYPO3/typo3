.. include:: /Includes.rst.txt

.. _feature-99084-1667981931:

========================================================
Feature: #99084 - Make Context Menu Trigger Configurable
========================================================

See :issue:`99084`

Description
===========

The context menu JavaScript API was adapted to also support opening
the menu through the "contextmenu" event type (right click) only.
Configuration for the context menu was streamlined and now reflects
its purpose. The trigger can now be set to "click" or "contextmenu".

New options
-----------

data-contextmenu-trigger:
- click: Opens the context menu click and contextmenu
- contextmenu: Opens the context menu only on contextmenu

Examples
--------

.. code-block:: html
    <a href="#"
        data-contextmenu-trigger="click"
        data-contextmenu-table="pages"
        data-contextmenu-uid="10"
    >Click and Contextmenu</a>

.. code-block:: html
    <a href="#"
        data-contextmenu-trigger="contextmenu"
        data-contextmenu-table="pages"
        data-contextmenu-uid="10"
    >Contextmenu only</a>

Impact
======

It is now possible to bind the context menu only to the
event type "contextmenu".


.. index:: Backend, JavaScript
