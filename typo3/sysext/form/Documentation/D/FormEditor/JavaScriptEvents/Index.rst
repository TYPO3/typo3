..  include:: /Includes.rst.txt

..  _apireference-formeditor-jsevents:

===================
JavaScript events
===================

The form editor uses a **publish/subscribe bus** for all cross-component
communication. Any custom JavaScript module can subscribe to these events
to extend or react to editor behaviour without patching core files.

..  note::
    The module system is **ES modules** (ESM). The legacy AMD
    :js:`define([…], function() {})` pattern from TYPO3 v11 and earlier is
    no longer supported. Custom modules must use :js:`export function bootstrap(formEditorApp)`.
    See :ref:`apireference-formeditor-custom-modules`.

..  contents::
    :depth: 1
    :local:


..  _apireference-formeditor-jsevents-pubsub:

Publish / subscribe basics
==========================

..  literalinclude:: _codesnippets/_subscribe.js
    :language: javascript
    :caption: Subscribe to an event

..  literalinclude:: _codesnippets/_publish.js
    :language: javascript
    :caption: Publish a custom event from within your module


..  note::
    The order in which subscribers receive an event is not guaranteed.
    Subscribers cannot pass data to each other. All event handlers must
    be designed without assumptions about execution order.


..  _apireference-formeditor-jsevents-overview:

Event quick-reference
=====================

..  _apireference-formeditor-jsevents-overview-lifecycle:

Lifecycle
---------

.. list-table::
   :header-rows: 1
   :widths: 45 55

   *  -  Event
      -  When it fires
   *  -  :ref:`view/ready <apireference-formeditor-jsevents-view-ready>`
      -  All modules loaded; editor is fully initialised.


..  _apireference-formeditor-jsevents-overview-ajax:

Ajax / data transfer
--------------------

.. list-table::
   :header-rows: 1
   :widths: 45 55

   *  -  Event
      -  When it fires
   *  -  :ref:`core/ajax/saveFormDefinition/success <apireference-formeditor-jsevents-core-ajax-savesuccess>`
      -  Form definition saved successfully.
   *  -  :ref:`core/ajax/saveFormDefinition/error <apireference-formeditor-jsevents-core-ajax-saveerror>`
      -  Server returned an error while saving.
   *  -  :ref:`core/ajax/renderFormDefinitionPage/success <apireference-formeditor-jsevents-core-ajax-rendersuccess>`
      -  Preview HTML for the current page returned successfully.
   *  -  :ref:`core/ajax/error <apireference-formeditor-jsevents-core-ajax-error>`
      -  Any Ajax request (save or preview render) failed.


..  _apireference-formeditor-jsevents-overview-state:

Application state
-----------------

.. list-table::
   :header-rows: 1
   :widths: 45 55

   *  -  Event
      -  When it fires
   *  -  :ref:`core/applicationState/add <apireference-formeditor-jsevents-core-applicationstate-add>`
      -  Undo/redo stack was updated.
   *  -  :ref:`core/currentlySelectedFormElementChanged <apireference-formeditor-jsevents-core-currentlyselectedformelementchanged>`
      -  The currently selected form element changed.
   *  -  :ref:`core/formElement/somePropertyChanged <apireference-formeditor-jsevents-core-formelement-somepropertychanged>`
      -  A property was written to a FormElement model via ``set()``.


..  _apireference-formeditor-jsevents-overview-formelement:

Form element lifecycle
----------------------

.. list-table::
   :header-rows: 1
   :widths: 45 55

   *  -  Event
      -  When it fires
   *  -  :ref:`view/formElement/inserted <apireference-formeditor-jsevents-view-formelement-inserted>`
      -  A new form element was added to the tree.
   *  -  :ref:`view/formElement/moved <apireference-formeditor-jsevents-view-formelement-moved>`
      -  A form element was moved within the tree.
   *  -  :ref:`view/formElement/removed <apireference-formeditor-jsevents-view-formelement-removed>`
      -  A form element was deleted.


..  _apireference-formeditor-jsevents-overview-collection:

Collection elements (validators / finishers)
--------------------------------------------

.. list-table::
   :header-rows: 1
   :widths: 45 55

   *  -  Event
      -  When it fires
   *  -  :ref:`view/collectionElement/new/added <apireference-formeditor-jsevents-view-collectionelement-new-added>`
      -  A validator or finisher was added.
   *  -  :ref:`view/collectionElement/moved <apireference-formeditor-jsevents-view-collectionelement-moved>`
      -  A validator or finisher was reordered.
   *  -  :ref:`view/collectionElement/removed <apireference-formeditor-jsevents-view-collectionelement-removed>`
      -  A validator or finisher was removed.


..  _apireference-formeditor-jsevents-overview-insert:

Insert element / page dialogs
------------------------------

.. list-table::
   :header-rows: 1
   :widths: 45 55

   *  -  Event
      -  When it fires
   *  -  :ref:`view/insertElements/perform/before <apireference-formeditor-jsevents-view-insertelements-perform-before>`
      -  Insert new element *before* the selected one.
   *  -  :ref:`view/insertElements/perform/after <apireference-formeditor-jsevents-view-insertelements-perform-after>`
      -  Insert new element *after* the selected one.
   *  -  :ref:`view/insertElements/perform/inside <apireference-formeditor-jsevents-view-insertelements-perform-inside>`
      -  Insert new element *inside* the selected composite.
   *  -  :ref:`view/insertElements/perform/bottom <apireference-formeditor-jsevents-view-insertelements-perform-bottom>`
      -  Insert new element at the end of the current page.
   *  -  :ref:`view/insertPages/perform <apireference-formeditor-jsevents-view-insertpages-perform>`
      -  Insert a new page after the current one.


..  _apireference-formeditor-jsevents-overview-header:

Header buttons
--------------

.. list-table::
   :header-rows: 1
   :widths: 45 55

   *  -  Event
      -  When it fires
   *  -  :ref:`view/header/button/save/clicked <apireference-formeditor-jsevents-view-header-save>`
      -  "Save" button clicked.
   *  -  :ref:`view/header/button/close/clicked <apireference-formeditor-jsevents-view-header-close>`
      -  "Close" button clicked (with unsaved changes guard).
   *  -  :ref:`view/header/button/newPage/clicked <apireference-formeditor-jsevents-view-header-newpage>`
      -  "New page" button clicked.
   *  -  :ref:`view/header/formSettings/clicked <apireference-formeditor-jsevents-view-header-formsettings>`
      -  "Form settings" button clicked.
   *  -  :ref:`view/undoButton/clicked <apireference-formeditor-jsevents-view-undobutton>`
      -  Undo button clicked.
   *  -  :ref:`view/redoButton/clicked <apireference-formeditor-jsevents-view-redobutton>`
      -  Redo button clicked.
   *  -  :ref:`view/viewModeButton/abstract/clicked <apireference-formeditor-jsevents-view-viewmode-abstract>`
      -  "Abstract view" toggle clicked.
   *  -  :ref:`view/viewModeButton/preview/clicked <apireference-formeditor-jsevents-view-viewmode-preview>`
      -  "Preview" toggle clicked.
   *  -  :ref:`view/paginationNext/clicked <apireference-formeditor-jsevents-view-pagination-next>`
      -  "Next page" pagination button clicked.
   *  -  :ref:`view/paginationPrevious/clicked <apireference-formeditor-jsevents-view-pagination-previous>`
      -  "Previous page" pagination button clicked.


..  _apireference-formeditor-jsevents-overview-stage:

Stage
-----

.. list-table::
   :header-rows: 1
   :widths: 45 55

   *  -  Event
      -  When it fires
   *  -  :ref:`view/stage/abstract/render/template/perform <apireference-formeditor-jsevents-view-stage-abstract-render-template-perform>`
      -  **Main extension point.** Stage renders a form element that has a
         :yaml:`formEditorPartials` entry.
   *  -  :ref:`view/stage/abstract/render/preProcess <apireference-formeditor-jsevents-view-stage-abstract-render-preprocess>`
      -  Before the abstract stage area is rendered.
   *  -  :ref:`view/stage/abstract/render/postProcess <apireference-formeditor-jsevents-view-stage-abstract-render-postprocess>`
      -  After the abstract stage area was rendered.
   *  -  :ref:`view/stage/preview/render/postProcess <apireference-formeditor-jsevents-view-stage-preview-render-postprocess>`
      -  After the preview stage area was rendered.
   *  -  :ref:`view/stage/element/clicked <apireference-formeditor-jsevents-view-stage-element-clicked>`
      -  A form element in the stage was clicked.
   *  -  :ref:`view/stage/panel/clicked <apireference-formeditor-jsevents-view-stage-panel-clicked>`
      -  The stage panel background was clicked.
   *  -  :ref:`view/stage/abstract/button/newElement/clicked <apireference-formeditor-jsevents-view-stage-abstract-button-newelement>`
      -  "Add element" button at the bottom of the stage clicked.
   *  -  :ref:`view/stage/abstract/elementToolbar/button/newElement/clicked <apireference-formeditor-jsevents-view-stage-abstract-toolbar-newelement>`
      -  Toolbar "add element" / split button on an element clicked.
   *  -  :ref:`view/stage/abstract/dnd/start <apireference-formeditor-jsevents-view-stage-dnd-start>`
      -  Drag started in the stage.
   *  -  :ref:`view/stage/abstract/dnd/change <apireference-formeditor-jsevents-view-stage-dnd-change>`
      -  Drag position changed in the stage.
   *  -  :ref:`view/stage/abstract/dnd/update <apireference-formeditor-jsevents-view-stage-dnd-update>`
      -  Drag ended, model position updated.
   *  -  :ref:`view/stage/abstract/dnd/stop <apireference-formeditor-jsevents-view-stage-dnd-stop>`
      -  Drag operation finished.


..  _apireference-formeditor-jsevents-overview-inspector:

Inspector
---------

.. list-table::
   :header-rows: 1
   :widths: 45 55

   *  -  Event
      -  When it fires
   *  -  :ref:`view/inspector/editor/insert/perform <apireference-formeditor-jsevents-view-inspector-editor-insert>`
      -  **Extension point for custom inspector editors.**
   *  -  :ref:`view/inspector/collectionElement/new/selected <apireference-formeditor-jsevents-view-inspector-collection-new-selected>`
      -  A new validator/finisher was chosen in the select box.
   *  -  :ref:`view/inspector/collectionElement/existing/selected <apireference-formeditor-jsevents-view-inspector-collection-existing-selected>`
      -  An existing validator/finisher section was expanded.
   *  -  :ref:`view/inspector/collectionElements/dnd/update <apireference-formeditor-jsevents-view-inspector-collection-dnd-update>`
      -  A validator/finisher was reordered via drag-and-drop.
   *  -  :ref:`view/inspector/removeCollectionElement/perform <apireference-formeditor-jsevents-view-inspector-removecollectionelement>`
      -  Remove a validator/finisher (from RequiredValidatorEditor checkbox).


..  _apireference-formeditor-jsevents-overview-structure:

Structure tree
--------------

.. list-table::
   :header-rows: 1
   :widths: 45 55

   *  -  Event
      -  When it fires
   *  -  :ref:`view/structure/root/selected <apireference-formeditor-jsevents-view-structure-root-selected>`
      -  Root element in the tree was clicked.
   *  -  :ref:`view/structure/button/newPage/clicked <apireference-formeditor-jsevents-view-structure-button-newpage>`
      -  "New page" button in the tree panel clicked.
   *  -  :ref:`view/structure/renew/postProcess <apireference-formeditor-jsevents-view-structure-renew-postprocess>`
      -  Tree was re-rendered.
   *  -  :ref:`view/tree/node/clicked <apireference-formeditor-jsevents-view-tree-node-clicked>`
      -  A tree node was clicked.
   *  -  :ref:`view/tree/node/changed <apireference-formeditor-jsevents-view-tree-node-changed>`
      -  A tree node label was edited inline.
   *  -  :ref:`view/tree/render/listItemAdded <apireference-formeditor-jsevents-view-tree-listitem-added>`
      -  Reserved – not yet published by core. (A list item was added to the tree.)
   *  -  :ref:`view/tree/dnd/change <apireference-formeditor-jsevents-view-tree-dnd-change>`
      -  Drag position changed in the tree.
   *  -  :ref:`view/tree/dnd/update <apireference-formeditor-jsevents-view-tree-dnd-update>`
      -  Drag ended, model position updated.
   *  -  :ref:`view/tree/dnd/stop <apireference-formeditor-jsevents-view-tree-dnd-stop>`
      -  Drag operation finished.


..  _apireference-formeditor-jsevents-overview-modals:

Dialogs (modals)
----------------

.. list-table::
   :header-rows: 1
   :widths: 45 55

   *  -  Event
      -  When it fires
   *  -  :ref:`view/modal/close/perform <apireference-formeditor-jsevents-view-modal-close>`
      -  User confirmed closing the editor with unsaved changes.
   *  -  :ref:`view/modal/removeFormElement/perform <apireference-formeditor-jsevents-view-modal-removeformelement>`
      -  User confirmed deleting a form element.
   *  -  :ref:`view/modal/removeCollectionElement/perform <apireference-formeditor-jsevents-view-modal-removecollectionelement>`
      -  User confirmed removing a validator/finisher.
   *  -  :ref:`view/modal/validationErrors/element/clicked <apireference-formeditor-jsevents-view-modal-validationerrors-clicked>`
      -  A form element was clicked in the validation-error dialog.


..  _apireference-formeditor-jsevents-reference:

Event reference
===============



..  _apireference-formeditor-basicjavascriptconcepts-events-view-ready:
..  _apireference-formeditor-jsevents-view-ready:

view/ready
----------

Published once all additional view-model modules registered via
:yaml:`dynamicJavaScriptModules.additionalViewModelModules` have
bootstrapped. EXT:form uses this event to remove the loading indicator
and finish editor initialisation. This is the earliest safe point to
interact with the fully wired editor.

:Arguments: none

..  literalinclude:: _codesnippets/_view-ready.js
    :language: javascript


..  _apireference-formeditor-basicjavascriptconcepts-events-core-ajax-saveformdefinition-success:
..  _apireference-formeditor-jsevents-core-ajax-savesuccess:

core/ajax/saveFormDefinition/success
--------------------------------------

Published after the form definition was saved successfully. EXT:form
shows a success flash message, updates the in-memory form definition
and re-renders all components.

:Arguments:

.. list-table::
   :widths: 15 25 60
   :header-rows: 1

   *  -  Index
      -  Type
      -  Description
   *  -  ``args[0]``
      -  :js:`{ status: string, formDefinition: object }`
      -  Response payload; :js:`formDefinition` is the saved definition.


..  _apireference-formeditor-jsevents-core-ajax-saveerror:

core/ajax/saveFormDefinition/error
------------------------------------

Published when the save Ajax request returns a server-side error.

:Arguments:

.. list-table::
   :widths: 15 25 60
   :header-rows: 1

   *  -  Index
      -  Type
      -  Description
   *  -  ``args[0]``
      -  :js:`{ status: string, message: string, code: number }`
      -  Error details from the server.


..  _apireference-formeditor-basicjavascriptconcepts-events-core-ajax-renderformdefinitionpage-success:
..  _apireference-formeditor-jsevents-core-ajax-rendersuccess:

core/ajax/renderFormDefinitionPage/success
-------------------------------------------

Published after the preview Ajax request returns successfully. EXT:form
uses this to display the rendered form HTML in the preview stage.

:Arguments:

.. list-table::
   :widths: 15 25 60
   :header-rows: 1

   *  -  Index
      -  Type
      -  Description
   *  -  ``args[0]``
      -  :js:`string`
      -  Rendered HTML of the current form page.
   *  -  ``args[1]``
      -  :js:`number`
      -  Zero-based index of the rendered page.


..  _apireference-formeditor-basicjavascriptconcepts-events-core-ajax-error:
..  _apireference-formeditor-jsevents-core-ajax-error:

core/ajax/error
----------------

Published when any Ajax request (save or preview render) fails at the
HTTP level. EXT:form shows an error flash message and displays the raw
error in the preview area.

:Arguments:

.. list-table::
   :widths: 15 25 60
   :header-rows: 1

   *  -  Index
      -  Type
      -  Description
   *  -  ``args[0]``
      -  :js:`string`
      -  HTTP status text (e.g. ``'Internal Server Error'``).
   *  -  ``args[1]``
      -  :js:`string`
      -  Raw response body.


..  _apireference-formeditor-basicjavascriptconcepts-events-core-applicationstate-add:
..  _apireference-formeditor-jsevents-core-applicationstate-add:

core/applicationState/add
--------------------------

Published every time an action (add / remove / move element or
collection element) is pushed onto the undo/redo stack. EXT:form uses
this to enable or disable the undo/redo buttons.

:Arguments:

.. list-table::
   :widths: 15 25 60
   :header-rows: 1

   *  -  Index
      -  Type
      -  Description
   *  -  ``args[0]``
      -  :js:`ApplicationState`
      -  Snapshot of the application state that was just pushed.
   *  -  ``args[1]``
      -  :js:`number`
      -  Current stack pointer position (0-based).
   *  -  ``args[2]``
      -  :js:`number`
      -  Total number of entries in the undo/redo stack.


..  _apireference-formeditor-basicjavascriptconcepts-events-core-currentlyselectedformelementchanged:
..  _apireference-formeditor-jsevents-core-currentlyselectedformelementchanged:

core/currentlySelectedFormElementChanged
-----------------------------------------

Published at the end of :js:`formEditorApp.setCurrentlySelectedFormElement()`.
All components that need to react to a selection change (inspector, stage,
tree highlight) subscribe to this event.

:Arguments:

.. list-table::
   :widths: 15 25 60
   :header-rows: 1

   *  -  Index
      -  Type
      -  Description
   *  -  ``args[0]``
      -  :js:`FormElement`
      -  The newly selected FormElement model.


..  _apireference-formeditor-basicjavascriptconcepts-events-core-formelement-somepropertychanged:
..  _apireference-formeditor-jsevents-core-formelement-somepropertychanged:

core/formElement/somePropertyChanged
--------------------------------------

Published by the FormElement model whenever a property is written via
:js:`set()`. EXT:form uses this to keep the tree labels, stage and
inspector in sync. It is also the mechanism behind
:ref:`FormElement.on() <apireference-formeditor-formelementmodel-api-on>`.

:Arguments:

.. list-table::
   :widths: 15 25 60
   :header-rows: 1

   *  -  Index
      -  Type
      -  Description
   *  -  ``args[0]``
      -  :js:`string`
      -  Dot-separated property path that was written.
   *  -  ``args[1]``
      -  :js:`unknown`
      -  New value.
   *  -  ``args[2]``
      -  :js:`unknown`
      -  Previous value.
   *  -  ``args[3]``
      -  :js:`string | undefined`
      -  :js:`__identifierPath` of the element whose property changed.

..  literalinclude:: _codesnippets/_some-property-changed.js
    :language: javascript


..  _apireference-formeditor-basicjavascriptconcepts-events-view-formelement-inserted:
..  _apireference-formeditor-jsevents-view-formelement-inserted:

view/formElement/inserted
--------------------------

Published after a new form element has been added to the form definition
tree. EXT:form selects the new element and re-renders tree, stage and
inspector.

:Arguments:

.. list-table::
   :widths: 15 25 60
   :header-rows: 1

   *  -  Index
      -  Type
      -  Description
   *  -  ``args[0]``
      -  :js:`FormElement`
      -  The newly inserted FormElement model.


..  _apireference-formeditor-basicjavascriptconcepts-events-view-formelement-moved:
..  _apireference-formeditor-jsevents-view-formelement-moved:

view/formElement/moved
-----------------------

Published after a form element has been moved within the tree. EXT:form
does not add additional behaviour here by default.

:Arguments:

.. list-table::
   :widths: 15 25 60
   :header-rows: 1

   *  -  Index
      -  Type
      -  Description
   *  -  ``args[0]``
      -  :js:`FormElement`
      -  The moved FormElement model.


..  _apireference-formeditor-basicjavascriptconcepts-events-view-formelement-removed:
..  _apireference-formeditor-jsevents-view-formelement-removed:

view/formElement/removed
-------------------------

Published after a form element has been removed. EXT:form selects the
parent element and re-renders tree, stage and inspector.

:Arguments:

.. list-table::
   :widths: 15 25 60
   :header-rows: 1

   *  -  Index
      -  Type
      -  Description
   *  -  ``args[0]``
      -  :js:`FormElement`
      -  The parent FormElement model of the deleted element.


..  _apireference-formeditor-basicjavascriptconcepts-events-view-collectionelement-new-added:
..  _apireference-formeditor-jsevents-view-collectionelement-new-added:

view/collectionElement/new/added
---------------------------------

Published after a new validator or finisher has been created and added to
the form definition. EXT:form re-renders the inspector.

:Arguments:

.. list-table::
   :widths: 15 25 60
   :header-rows: 1

   *  -  Index
      -  Type
      -  Description
   *  -  ``args[0]``
      -  :js:`string`
      -  Identifier of the new collection element (e.g. ``'NotEmpty'``).
   *  -  ``args[1]``
      -  :js:`string`
      -  Collection name: ``'validators'`` or ``'finishers'``.
   *  -  ``args[2]``
      -  :js:`FormElement`
      -  The owning form element.
   *  -  ``args[3]``
      -  :js:`object`
      -  Full configuration object of the added collection element.
   *  -  ``args[4]``
      -  :js:`string`
      -  Identifier of the reference element (inserted before/after).


..  _apireference-formeditor-basicjavascriptconcepts-events-view-collectionelement-moved:
..  _apireference-formeditor-jsevents-view-collectionelement-moved:

view/collectionElement/moved
-----------------------------

Published after a validator or finisher has been reordered. EXT:form
re-renders the inspector.

:Arguments:

.. list-table::
   :widths: 15 25 60
   :header-rows: 1

   *  -  Index
      -  Type
      -  Description
   *  -  ``args[0]``
      -  :js:`string`
      -  Identifier of the moved element.
   *  -  ``args[1]``
      -  :js:`string`
      -  Relative position: ``'before'`` or ``'after'``.
   *  -  ``args[2]``
      -  :js:`string`
      -  Identifier of the reference element.
   *  -  ``args[3]``
      -  :js:`string`
      -  Collection name.
   *  -  ``args[4]``
      -  :js:`FormElement`
      -  The owning form element.


..  _apireference-formeditor-basicjavascriptconcepts-events-view-collectionelement-removed:
..  _apireference-formeditor-jsevents-view-collectionelement-removed:

view/collectionElement/removed
--------------------------------

Published after a validator or finisher has been removed from the form
definition. EXT:form re-renders the inspector.

:Arguments:

.. list-table::
   :widths: 15 25 60
   :header-rows: 1

   *  -  Index
      -  Type
      -  Description
   *  -  ``args[0]``
      -  :js:`string`
      -  Identifier of the removed element.
   *  -  ``args[1]``
      -  :js:`string`
      -  Collection name.
   *  -  ``args[2]``
      -  :js:`FormElement`
      -  The owning form element.


..  _apireference-formeditor-jsevents-view-insertelements-perform-before:

view/insertElements/perform/before
------------------------------------

Published when the user selects an element type in the "New element"
dialog after clicking the "Before" toolbar option. EXT:form creates the
new element and moves it *before* the currently selected element.

:Arguments:

.. list-table::
   :widths: 15 25 60
   :header-rows: 1

   *  -  Index
      -  Type
      -  Description
   *  -  ``args[0]``
      -  :js:`string`
      -  Form element type identifier (e.g. ``'Text'``).


..  _apireference-formeditor-basicjavascriptconcepts-events-view-insertelements-perform-after:
..  _apireference-formeditor-jsevents-view-insertelements-perform-after:

view/insertElements/perform/after
----------------------------------

Published when the user selects an element type after clicking the
"After" toolbar option or the standard toolbar button for non-composite
elements. EXT:form creates the element and moves it *after* the selected
element (as a sibling).

:Arguments:

.. list-table::
   :widths: 15 25 60
   :header-rows: 1

   *  -  Index
      -  Type
      -  Description
   *  -  ``args[0]``
      -  :js:`string`
      -  Form element type identifier.


..  _apireference-formeditor-basicjavascriptconcepts-events-view-insertelements-perform-inside:
..  _apireference-formeditor-jsevents-view-insertelements-perform-inside:

view/insertElements/perform/inside
------------------------------------

Published when the user selects an element type after clicking the
"Inside" toolbar option on a composite element (e.g. Fieldset). EXT:form
creates the element as a *child* of the currently selected composite.

:Arguments:

.. list-table::
   :widths: 15 25 60
   :header-rows: 1

   *  -  Index
      -  Type
      -  Description
   *  -  ``args[0]``
      -  :js:`string`
      -  Form element type identifier.


..  _apireference-formeditor-basicjavascriptconcepts-events-view-insertelements-perform-bottom:
..  _apireference-formeditor-jsevents-view-insertelements-perform-bottom:

view/insertElements/perform/bottom
------------------------------------

Published when the user selects an element type after clicking the
"Create new element" button at the very bottom of the stage in abstract
view. EXT:form appends the element as the last child of the current page.

:Arguments:

.. list-table::
   :widths: 15 25 60
   :header-rows: 1

   *  -  Index
      -  Type
      -  Description
   *  -  ``args[0]``
      -  :js:`string`
      -  Form element type identifier.


..  _apireference-formeditor-basicjavascriptconcepts-events-view-insertpages-perform:
..  _apireference-formeditor-jsevents-view-insertpages-perform:

view/insertPages/perform
-------------------------

Published when the user selects a page type in the "New page" dialog.
EXT:form creates the page *after* the currently selected page.

:Arguments:

.. list-table::
   :widths: 15 25 60
   :header-rows: 1

   *  -  Index
      -  Type
      -  Description
   *  -  ``args[0]``
      -  :js:`string`
      -  Form element type identifier (typically ``'Page'``).


..  _apireference-formeditor-basicjavascriptconcepts-events-view-header-button-save-clicked:
..  _apireference-formeditor-jsevents-view-header-save:

view/header/button/save/clicked
---------------------------------

Published when the "Save" button is clicked. EXT:form either opens a
validation-error dialog (if there are errors) or saves the form definition.

:Arguments: none


..  _apireference-formeditor-basicjavascriptconcepts-events-view-header-button-close-clicked:
..  _apireference-formeditor-jsevents-view-header-close:

view/header/button/close/clicked
----------------------------------

Published when the "Close" button is clicked *and* the form has unsaved
changes. EXT:form opens a confirmation dialog.

:Arguments: none


..  _apireference-formeditor-basicjavascriptconcepts-events-view-header-button-newpage-clicked:
..  _apireference-formeditor-jsevents-view-header-newpage:

view/header/button/newPage/clicked
------------------------------------

Published when the "New page" icon in the header is clicked. EXT:form
opens the "New page" dialog.

:Arguments:

.. list-table::
   :widths: 15 25 60
   :header-rows: 1

   *  -  Index
      -  Type
      -  Description
   *  -  ``args[0]``
      -  :js:`'view/insertPages/perform'`
      -  The event to publish once the user picks a page type.


..  _apireference-formeditor-basicjavascriptconcepts-events-view-header-formsettings-clicked:
..  _apireference-formeditor-jsevents-view-header-formsettings:

view/header/formSettings/clicked
----------------------------------

Published when the "Form settings" button is clicked. EXT:form selects
the root form element and renders its settings in the inspector.

:Arguments: none


..  _apireference-formeditor-basicjavascriptconcepts-events-view-undobutton-clicked:
..  _apireference-formeditor-jsevents-view-undobutton:

view/undoButton/clicked
------------------------

Published when the undo button is clicked. EXT:form steps back one state
in the undo/redo stack and re-renders all components.

:Arguments: none


..  _apireference-formeditor-basicjavascriptconcepts-events-view-redobutton-clicked:
..  _apireference-formeditor-jsevents-view-redobutton:

view/redoButton/clicked
------------------------

Published when the redo button is clicked. EXT:form steps forward one
state in the undo/redo stack and re-renders all components.

:Arguments: none


..  _apireference-formeditor-basicjavascriptconcepts-events-view-viewmodebutton-abstract-clicked:
..  _apireference-formeditor-jsevents-view-viewmode-abstract:

view/viewModeButton/abstract/clicked
--------------------------------------

Published when the "Abstract view" toggle in the stage header is clicked.
EXT:form switches to abstract view if not already active.

:Arguments: none


..  _apireference-formeditor-basicjavascriptconcepts-events-view-viewmodebutton-preview-clicked:
..  _apireference-formeditor-jsevents-view-viewmode-preview:

view/viewModeButton/preview/clicked
-------------------------------------

Published when the "Preview" toggle in the stage header is clicked.
EXT:form switches to preview view if not already active.

:Arguments: none


..  _apireference-formeditor-basicjavascriptconcepts-events-view-paginationnext-clicked:
..  _apireference-formeditor-jsevents-view-pagination-next:

view/paginationNext/clicked
----------------------------

Published when the "next page" arrow in the stage header is clicked.
EXT:form advances to the next form page.

:Arguments: none


..  _apireference-formeditor-basicjavascriptconcepts-events-view-paginationprevious-clicked:
..  _apireference-formeditor-jsevents-view-pagination-previous:

view/paginationPrevious/clicked
---------------------------------

Published when the "previous page" arrow in the stage header is clicked.
EXT:form goes back to the previous form page.

:Arguments: none


..  _apireference-formeditor-basicjavascriptconcepts-events-view-stage-abstract-render-template-perform:
..  _apireference-formeditor-jsevents-view-stage-abstract-render-template-perform:

view/stage/abstract/render/template/perform
--------------------------------------------

**The primary extension point for custom stage rendering.**

Published by the Stage component for each form element that has a
:yaml:`formEditorPartials` entry in the prototype configuration. Form
elements *without* a :yaml:`formEditorPartials` entry are rendered
automatically by the
:html:`<typo3-form-form-element-stage-item>` web component — no
subscriber is needed for those.

..  note::
    For most custom form elements the web-component approach (no
    :yaml:`formEditorPartials`, no subscriber) is sufficient.
    Use this event only when you need fully custom DOM inside the stage
    that the built-in web component cannot provide.

:Arguments:

.. list-table::
   :widths: 15 25 60
   :header-rows: 1

   *  -  Index
      -  Type
      -  Description
   *  -  ``args[0]``
      -  :js:`FormElement`
      -  The FormElement model being rendered.
   *  -  ``args[1]``
      -  :js:`HTMLElement`
      -  Cloned DOM node from the Fluid partial. Populate this via DOM
         manipulation.

**Full example — custom element with a dedicated stage partial:**

*Fluid partial* (:file:`EXT:my_extension/Resources/Private/Backend/Partials/FormEditor/Stage/MyCustomElement.html`):

..  literalinclude:: _codesnippets/_stage-template-perform.html
    :language: html

*Prototype YAML configuration:*

..  literalinclude:: _codesnippets/_stage-template-perform.yaml
    :language: yaml

*JavaScript module:*

..  literalinclude:: _codesnippets/_stage-template-perform.js
    :language: javascript
    :caption: EXT:my_extension/Resources/Public/JavaScript/backend/form-editor/view-model.js



..  _apireference-formeditor-basicjavascriptconcepts-events-view-stage-abstract-render-preprocess:
..  _apireference-formeditor-jsevents-view-stage-abstract-render-preprocess:

view/stage/abstract/render/preProcess
---------------------------------------

Published immediately before the abstract stage area is re-rendered.

:Arguments: none


..  _apireference-formeditor-basicjavascriptconcepts-events-view-stage-abstract-render-postprocess:
..  _apireference-formeditor-jsevents-view-stage-abstract-render-postprocess:

view/stage/abstract/render/postProcess
----------------------------------------

Published immediately after the abstract stage area has been rendered.
EXT:form uses this to re-render the undo/redo buttons and apply validation
error highlights.

:Arguments: none


..  _apireference-formeditor-basicjavascriptconcepts-events-view-stage-preview-render-postprocess:
..  _apireference-formeditor-jsevents-view-stage-preview-render-postprocess:

view/stage/preview/render/postProcess
---------------------------------------

Published after the preview stage area has been rendered. EXT:form uses
this to re-render the undo/redo buttons.

:Arguments: none


..  _apireference-formeditor-basicjavascriptconcepts-events-view-stage-element-clicked:
..  _apireference-formeditor-jsevents-view-stage-element-clicked:

view/stage/element/clicked
---------------------------

Published when a form element in the abstract stage is clicked. EXT:form
selects the element, shows its toolbar and re-renders the inspector.

:Arguments:

.. list-table::
   :widths: 15 25 60
   :header-rows: 1

   *  -  Index
      -  Type
      -  Description
   *  -  ``args[0]``
      -  :js:`string`
      -  :js:`__identifierPath` of the clicked element.


..  _apireference-formeditor-basicjavascriptconcepts-events-view-stage-panel-clicked:
..  _apireference-formeditor-jsevents-view-stage-panel-clicked:

view/stage/panel/clicked
-------------------------

Published when the stage panel header or background area is clicked
(not on a specific form element).

:Arguments: none


..  _apireference-formeditor-basicjavascriptconcepts-events-view-stage-abstract-button-newelement-clicked:
..  _apireference-formeditor-jsevents-view-stage-abstract-button-newelement:

view/stage/abstract/button/newElement/clicked
----------------------------------------------

Published when the "Create new element" button at the bottom of the
stage (in abstract view) is clicked. EXT:form opens the "New element"
dialog configured to insert at the bottom of the current page.

:Arguments:

.. list-table::
   :widths: 15 25 60
   :header-rows: 1

   *  -  Index
      -  Type
      -  Description
   *  -  ``args[0]``
      -  :js:`'view/insertElements/perform/bottom'`
      -  Target publish event for the dialog result.
   *  -  ``args[1]``
      -  :js:`object | undefined`
      -  Optional modal configuration.


..  _apireference-formeditor-basicjavascriptconcepts-events-view-stage-abstract-elementtoolbar-button-newelement-clicked:
..  _apireference-formeditor-jsevents-view-stage-abstract-toolbar-newelement:

view/stage/abstract/elementToolbar/button/newElement/clicked
-------------------------------------------------------------

Published when the "Add element" button or split-button ("Before",
"After", "Inside") in the per-element toolbar is clicked. EXT:form opens
the "New element" dialog with the appropriate target event.

:Arguments:

.. list-table::
   :widths: 15 25 60
   :header-rows: 1

   *  -  Index
      -  Type
      -  Description
   *  -  ``args[0]``
      -  :js:`string`
      -  Target event: ``'view/insertElements/perform/before'``,
         ``'…/after'`` or ``'…/inside'``.
   *  -  ``args[1]``
      -  :js:`object`
      -  Modal configuration (``disableElementTypes``,
         ``onlyEnableElementTypes``).


..  _apireference-formeditor-basicjavascriptconcepts-events-view-stage-abstract-dnd-start:
..  _apireference-formeditor-jsevents-view-stage-dnd-start:

view/stage/abstract/dnd/start
-------------------------------

Published when a drag operation begins in the abstract stage. EXT:form
adds CSS classes to highlight the dragged element.

:Arguments:

.. list-table::
   :widths: 15 25 60
   :header-rows: 1

   *  -  Index
      -  Type
      -  Description
   *  -  ``args[0]``
      -  :js:`HTMLElement`
      -  The dragged element's DOM node.
   *  -  ``args[1]``
      -  :js:`HTMLElement`
      -  The drag placeholder DOM node.


..  _apireference-formeditor-basicjavascriptconcepts-events-view-stage-abstract-dnd-change:
..  _apireference-formeditor-jsevents-view-stage-dnd-change:

view/stage/abstract/dnd/change
--------------------------------

Published on each positional change during a drag operation in the stage
(SortableJS :js:`onChange`). EXT:form applies hover CSS classes.

:Arguments:

.. list-table::
   :widths: 15 25 60
   :header-rows: 1

   *  -  Index
      -  Type
      -  Description
   *  -  ``args[0]``
      -  :js:`HTMLElement`
      -  The drag placeholder DOM node.
   *  -  ``args[1]``
      -  :js:`string`
      -  :js:`__identifierPath` of the potential parent element.
   *  -  ``args[2]``
      -  :js:`FormElement`
      -  Innermost enclosing composite element (if any).


..  _apireference-formeditor-basicjavascriptconcepts-events-view-stage-abstract-dnd-update:
..  _apireference-formeditor-jsevents-view-stage-dnd-update:

view/stage/abstract/dnd/update
--------------------------------

Published at the end of a drag operation when the element was dropped in a
new position (SortableJS :js:`onEnd`). EXT:form calls
:js:`moveFormElement()` to persist the new order.

:Arguments:

.. list-table::
   :widths: 15 25 60
   :header-rows: 1

   *  -  Index
      -  Type
      -  Description
   *  -  ``args[0]``
      -  :js:`HTMLElement`
      -  The dropped DOM node.
   *  -  ``args[1]``
      -  :js:`string`
      -  :js:`__identifierPath` of the moved element.
   *  -  ``args[2]``
      -  :js:`string`
      -  :js:`__identifierPath` of the preceding sibling (empty string if first).
   *  -  ``args[3]``
      -  :js:`string`
      -  :js:`__identifierPath` of the following sibling (empty string if last).


..  _apireference-formeditor-basicjavascriptconcepts-events-view-stage-abstract-dnd-stop:
..  _apireference-formeditor-jsevents-view-stage-dnd-stop:

view/stage/abstract/dnd/stop
------------------------------

Published after the drag operation completes and all model updates are
done. EXT:form re-renders tree, stage and inspector and selects the moved
element.

:Arguments:

.. list-table::
   :widths: 15 25 60
   :header-rows: 1

   *  -  Index
      -  Type
      -  Description
   *  -  ``args[0]``
      -  :js:`string`
      -  :js:`__identifierPath` of the element that was dragged.


..  _apireference-formeditor-basicjavascriptconcepts-events-view-inspector-editor-insert-perform:
..  _apireference-formeditor-jsevents-view-inspector-editor-insert:

view/inspector/editor/insert/perform
--------------------------------------

**Extension point for custom inspector editors.**

Published after each inspector editor has been rendered (both for form
elements and for collection elements). Use :js:`args[0].templateName` to
identify which editor is being rendered and apply custom logic.

:Arguments:

.. list-table::
   :widths: 15 25 60
   :header-rows: 1

   *  -  Index
      -  Type
      -  Description
   *  -  ``args[0]``
      -  :js:`EditorConfiguration`
      -  Full YAML configuration of the editor (includes :js:`templateName`).
   *  -  ``args[1]``
      -  :js:`HTMLElement`
      -  The rendered DOM node of the inspector editor.
   *  -  ``args[2]``
      -  :js:`string`
      -  Identifier of the active collection element (validator/finisher),
         or empty string when rendering a plain element editor.
   *  -  ``args[3]``
      -  :js:`string`
      -  Collection name (``'validators'`` or ``'finishers'``), or empty.

**Example — register a custom inspector editor:**

*Prototype YAML:*

..  literalinclude:: _codesnippets/_inspector-editor-insert.yaml
    :language: yaml

*JavaScript module:*

..  literalinclude:: _codesnippets/_inspector-editor-insert.js
    :language: javascript
    :caption: EXT:my_extension/Resources/Public/JavaScript/backend/form-editor/view-model.js



..  _apireference-formeditor-basicjavascriptconcepts-events-view-inspector-collectionelement-new-selected:
..  _apireference-formeditor-basicjavascriptconcepts-events-view-inspector-collection-new-selected:
..  _apireference-formeditor-jsevents-view-inspector-collection-new-selected:

view/inspector/collectionElement/new/selected
----------------------------------------------

Published when the user selects a *new* validator or finisher from the
select box in the inspector. EXT:form adds the collection element to the
form definition and re-renders the inspector.

:Arguments:

.. list-table::
   :widths: 15 25 60
   :header-rows: 1

   *  -  Index
      -  Type
      -  Description
   *  -  ``args[0]``
      -  :js:`string`
      -  Identifier of the selected collection element.
   *  -  ``args[1]``
      -  :js:`string`
      -  Collection name (``'validators'`` or ``'finishers'``).


..  _apireference-formeditor-basicjavascriptconcepts-events-view-inspector-collectionelement-existing-selected:
..  _apireference-formeditor-basicjavascriptconcepts-events-view-inspector-collection-existing-selected:
..  _apireference-formeditor-jsevents-view-inspector-collection-existing-selected:

view/inspector/collectionElement/existing/selected
----------------------------------------------------

Published when the user expands an *existing* validator or finisher row
in the inspector. EXT:form renders that element's sub-editors.

:Arguments:

.. list-table::
   :widths: 15 25 60
   :header-rows: 1

   *  -  Index
      -  Type
      -  Description
   *  -  ``args[0]``
      -  :js:`string`
      -  Identifier of the already-selected collection element.
   *  -  ``args[1]``
      -  :js:`string`
      -  Collection name.


..  _apireference-formeditor-basicjavascriptconcepts-events-view-inspector-collectionelement-dnd-update:
..  _apireference-formeditor-basicjavascriptconcepts-events-view-inspector-collection-dnd-update:
..  _apireference-formeditor-jsevents-view-inspector-collection-dnd-update:

view/inspector/collectionElements/dnd/update
---------------------------------------------

Published when a validator or finisher is reordered via drag-and-drop
inside the inspector (SortableJS :js:`onEnd`). EXT:form moves the element
in the form definition.

:Arguments:

.. list-table::
   :widths: 15 25 60
   :header-rows: 1

   *  -  Index
      -  Type
      -  Description
   *  -  ``args[0]``
      -  :js:`string`
      -  Identifier of the moved element.
   *  -  ``args[1]``
      -  :js:`string`
      -  Identifier of the preceding element after the move.
   *  -  ``args[2]``
      -  :js:`string`
      -  Identifier of the following element after the move.
   *  -  ``args[3]``
      -  :js:`string`
      -  Collection name.


..  _apireference-formeditor-basicjavascriptconcepts-events-view-inspector-removecollectionelement-perform:
..  _apireference-formeditor-jsevents-view-inspector-removecollectionelement:

view/inspector/removeCollectionElement/perform
-----------------------------------------------

Published by the ``RequiredValidatorEditor`` when its checkbox is
unchecked. EXT:form removes the ``NotEmpty`` validator from the form
definition.

:Arguments:

.. list-table::
   :widths: 15 25 60
   :header-rows: 1

   *  -  Index
      -  Type
      -  Description
   *  -  ``args[0]``
      -  :js:`string`
      -  Validator identifier (e.g. ``'NotEmpty'``).
   *  -  ``args[1]``
      -  :js:`'validators'`
      -  Collection name (always ``'validators'`` for this event).
   *  -  ``args[2]``
      -  :js:`FormElement | undefined`
      -  The owning form element, or ``undefined`` for the currently
         selected one.


..  _apireference-formeditor-basicjavascriptconcepts-events-view-modal-close-perform:
..  _apireference-formeditor-jsevents-view-modal-close:

view/modal/close/perform
-------------------------

Published when the user confirms closing the editor in the "unsaved
changes" dialog. EXT:form clears the unsaved-content flag and navigates
back to the form manager.

:Arguments: none


..  _apireference-formeditor-basicjavascriptconcepts-events-view-modal-removeformelement-perform:
..  _apireference-formeditor-jsevents-view-modal-removeformelement:

view/modal/removeFormElement/perform
--------------------------------------

Published when the user confirms deleting a form element in the
confirmation dialog. EXT:form removes the element from the form
definition.

:Arguments:

.. list-table::
   :widths: 15 25 60
   :header-rows: 1

   *  -  Index
      -  Type
      -  Description
   *  -  ``args[0]``
      -  :js:`FormElement`
      -  The form element to be deleted.


..  _apireference-formeditor-basicjavascriptconcepts-events-view-modal-removecollectionelement-perform:
..  _apireference-formeditor-jsevents-view-modal-removecollectionelement:

view/modal/removeCollectionElement/perform
-------------------------------------------

Published when the user confirms removing a validator or finisher via its
delete icon. EXT:form removes the collection element from the form
definition.

:Arguments:

.. list-table::
   :widths: 15 25 60
   :header-rows: 1

   *  -  Index
      -  Type
      -  Description
   *  -  ``args[0]``
      -  :js:`string`
      -  Identifier of the collection element to remove.
   *  -  ``args[1]``
      -  :js:`string`
      -  Collection name.
   *  -  ``args[2]``
      -  :js:`FormElement`
      -  The owning form element.


..  _apireference-formeditor-basicjavascriptconcepts-events-view-modal-validationerrors-element-clicked:
..  _apireference-formeditor-jsevents-view-modal-validationerrors-clicked:

view/modal/validationErrors/element/clicked
--------------------------------------------

Published when the user clicks a form element link inside the validation
error dialog. EXT:form selects the element and navigates to it.

:Arguments:

.. list-table::
   :widths: 15 25 60
   :header-rows: 1

   *  -  Index
      -  Type
      -  Description
   *  -  ``args[0]``
      -  :js:`string`
      -  :js:`__identifierPath` of the element with the validation error.


..  _apireference-formeditor-basicjavascriptconcepts-events-view-structure-root-selected:
..  _apireference-formeditor-jsevents-view-structure-root-selected:

view/structure/root/selected
------------------------------

Published when the root element in the structure tree is clicked. EXT:form
selects the root form element and re-renders stage, tree and inspector.

:Arguments: none


..  _apireference-formeditor-basicjavascriptconcepts-events-view-structure-button-newpage-clicked:
..  _apireference-formeditor-jsevents-view-structure-button-newpage:

view/structure/button/newPage/clicked
---------------------------------------

Published when the "Create new page" button inside the structure tree panel
is clicked. EXT:form opens the "New page" dialog.

:Arguments:

.. list-table::
   :widths: 15 25 60
   :header-rows: 1

   *  -  Index
      -  Type
      -  Description
   *  -  ``args[0]``
      -  :js:`'view/insertPages/perform'`
      -  Target publish event for the dialog result.


..  _apireference-formeditor-basicjavascriptconcepts-events-view-structure-renew-postprocess:
..  _apireference-formeditor-jsevents-view-structure-renew-postprocess:

view/structure/renew/postProcess
----------------------------------

Published after the structure tree has been fully re-rendered. EXT:form
uses this to apply validation error markers to tree nodes.

:Arguments: none


..  _apireference-formeditor-basicjavascriptconcepts-events-view-tree-node-clicked:
..  _apireference-formeditor-jsevents-view-tree-node-clicked:

view/tree/node/clicked
-----------------------

Published when a node in the structure tree is clicked. EXT:form selects
the element and re-renders stage and inspector.

:Arguments:

.. list-table::
   :widths: 15 25 60
   :header-rows: 1

   *  -  Index
      -  Type
      -  Description
   *  -  ``args[0]``
      -  :js:`string`
      -  :js:`__identifierPath` of the clicked element.


..  _apireference-formeditor-basicjavascriptconcepts-events-view-tree-node-changed:
..  _apireference-formeditor-jsevents-view-tree-node-changed:

view/tree/node/changed
-----------------------

..  versionadded:: 13.4
    This event was previously missing from the documentation. It has been
    dispatched since inline label editing in the structure tree was introduced.

Published when a tree node label is edited inline (inline-rename). EXT:form
writes the new label to the FormElement model and updates the inspector if
the element is currently selected.

:Arguments:

.. list-table::
   :widths: 15 25 60
   :header-rows: 1

   *  -  Index
      -  Type
      -  Description
   *  -  ``args[0]``
      -  :js:`string`
      -  :js:`__identifierPath` of the renamed element.
   *  -  ``args[1]``
      -  :js:`string`
      -  The new label string.


..  _apireference-formeditor-basicjavascriptconcepts-events-view-tree-render-listitemadded:
..  _apireference-formeditor-jsevents-view-tree-listitem-added:

view/tree/render/listItemAdded
--------------------------------

..  note::
    This event is defined in the TypeScript event-map interface but is
    **not yet published** by the core tree component. It is reserved for
    future use. Subscribing to it will currently have no effect.

Published by the tree component for each form element as it is added to
the rendered tree. Use this to augment individual tree nodes.

:Arguments:

.. list-table::
   :widths: 15 25 60
   :header-rows: 1

   *  -  Index
      -  Type
      -  Description
   *  -  ``args[0]``
      -  :js:`HTMLElement | null`
      -  The list item DOM node that was added.
   *  -  ``args[1]``
      -  :js:`FormElement`
      -  The FormElement model for this tree node.


..  _apireference-formeditor-basicjavascriptconcepts-events-view-tree-dnd-change:
..  _apireference-formeditor-jsevents-view-tree-dnd-change:

view/tree/dnd/change
---------------------

Published on each positional change during a drag in the structure tree
(SortableJS :js:`onChange`). EXT:form applies hover CSS classes.

:Arguments:

.. list-table::
   :widths: 15 25 60
   :header-rows: 1

   *  -  Index
      -  Type
      -  Description
   *  -  ``args[0]``
      -  :js:`HTMLElement | null`
      -  The drag placeholder node.
   *  -  ``args[1]``
      -  :js:`string`
      -  :js:`__identifierPath` of the potential parent element.
   *  -  ``args[2]``
      -  :js:`FormElement`
      -  Innermost enclosing composite element (if any).


..  _apireference-formeditor-basicjavascriptconcepts-events-view-tree-dnd-update:
..  _apireference-formeditor-jsevents-view-tree-dnd-update:

view/tree/dnd/update
---------------------

Published when a drag in the structure tree ends and the element was
dropped in a new position. EXT:form calls :js:`moveFormElement()`.

:Arguments:

.. list-table::
   :widths: 15 25 60
   :header-rows: 1

   *  -  Index
      -  Type
      -  Description
   *  -  ``args[0]``
      -  :js:`HTMLElement | null`
      -  The dropped DOM node.
   *  -  ``args[1]``
      -  :js:`string`
      -  :js:`__identifierPath` of the moved element.
   *  -  ``args[2]``
      -  :js:`string`
      -  :js:`__identifierPath` of the preceding sibling.
   *  -  ``args[3]``
      -  :js:`string`
      -  :js:`__identifierPath` of the following sibling.


..  _apireference-formeditor-basicjavascriptconcepts-events-view-tree-dnd-stop:
..  _apireference-formeditor-jsevents-view-tree-dnd-stop:

view/tree/dnd/stop
-------------------

Published after the tree drag operation completes. EXT:form re-renders
tree, stage and inspector and selects the moved element.

:Arguments:

.. list-table::
   :widths: 15 25 60
   :header-rows: 1

   *  -  Index
      -  Type
      -  Description
   *  -  ``args[0]``
      -  :js:`string`
      -  :js:`__identifierPath` of the element that was dragged.



