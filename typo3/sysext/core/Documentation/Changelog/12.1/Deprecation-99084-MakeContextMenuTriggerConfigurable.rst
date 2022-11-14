.. include:: /Includes.rst.txt

.. _deprecation-99084-1667981931:

============================================================
Deprecation: #99084 - Make Context Menu Trigger Configurable
============================================================

See :issue:`99084`

Description
===========

The context menu JavaScript API was adapted to also support opening
the menu through the "contextmenu" event type (right click) only.
Configuration for the context menu was streamlined and now reflects
its purpose. The trigger can now be set to "click" or "contextmenu".


Impact
======

Using the deprecated JavaScript API will trigger a warning in the console.


Affected installations
======================

All extensions that use the context menu.


Migration
=========

Replace the trigger `class="t3js-contextmenutrigger"` with `data-contextmenu-trigger="click"`.
Prefix all configuration with `data-contextmenu-`.

Before
------

.. code-block:: html
    <a href="#"
        class="t3js-contextmenutrigger"
        data-table="pages"
        data-uid="10"
        data-context="tree"
    >...</a>

After
-----

.. code-block:: html
    <a href="#"
        data-contextmenu-trigger="click"
        data-contextmenu-table="pages"
        data-contextmenu-uid="10"
        data-contextmenu-context="tree"
    >...</a>


.. index:: Backend, JavaScript, NotScanned
