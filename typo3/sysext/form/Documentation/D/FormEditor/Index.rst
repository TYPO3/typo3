..  include:: /Includes.rst.txt


..  _apireference-formeditor:
..  _apireference-formeditor-basicjavascriptconcepts:
..  _apireference-formeditor-basicjavascriptconcepts-events:
..  _apireference-formeditor-stage:

===========
Form Editor
===========

This chapter is the developer reference for the TYPO3 backend form editor.
It covers the JavaScript extension points and the data model used by the
editor's TypeScript modules.

..  contents::
    :depth: 1
    :local:

..  _apireference-formeditor-architecture:

Architecture overview
=====================

The form editor consists of four cooperating TypeScript modules, each
responsible for one UI component:

.. list-table::
   :header-rows: 1
   :widths: 30 35 35

   *  -  Module (import path)
      -  Component
      -  Responsibility
   *  -  :js:`@typo3/form/backend/form-editor/view-model`
      -  —
      -  Central view model; wires DOM events and publishes/subscribes
         to all cross-component events.
   *  -  :js:`@typo3/form/backend/form-editor/stage-component`
      -  **Stage**
      -  Renders the abstract and preview views of the current form page.
   *  -  :js:`@typo3/form/backend/form-editor/inspector-component`
      -  **Inspector**
      -  Renders the property editors for the selected form element.
   *  -  :js:`@typo3/form/backend/form-editor/tree-component-adapter`
      -  **Structure tree**
      -  Wraps the TYPO3 backend tree web component and bridges its
         events to the publish/subscribe bus.
   *  -  :js:`@typo3/form/backend/form-editor/mediator`
      -  —
      -  Wires all publish/subscribe events to view-model actions.
         Loaded automatically; replace via
         :yaml:`dynamicJavaScriptModules.mediator` only when you need to
         completely swap the event-wiring logic.

All modules communicate exclusively via a **publish/subscribe bus**
(:js:`PublisherSubscriber`). Direct module-to-module calls are avoided
so that extension code can hook into any point without modifying core
files.

..  _apireference-formeditor-custom-modules:

Registering a custom JavaScript module
=======================================

Custom modules must export a :js:`bootstrap` function. The form editor
calls this function once all built-in modules have loaded, passing the
central :js:`FormEditor` application object as the sole argument.

..  rst-class:: bignums-xxl

1.  Create the JavaScript module

    ..  literalinclude:: _codesnippets/_bootstrap.js
        :language: javascript
        :caption: EXT:my_extension/Resources/Public/JavaScript/backend/form-editor/view-model.js

2.  Register the module in the importmap

    ..  literalinclude:: _codesnippets/_JavaScriptModules.php
        :language: php
        :caption: EXT:my_extension/Configuration/JavaScriptModules.php

3.  Tell the form editor to load the module

    ..  literalinclude:: _codesnippets/_prototype-setup.yaml
        :language: yaml
        :caption: EXT:my_extension/Configuration/Form/MyFormSet/config.yaml


..  toctree::
    :maxdepth: 1

    JavaScriptEvents/Index
    StageTemplates/Index
    FormElementModel/Index
