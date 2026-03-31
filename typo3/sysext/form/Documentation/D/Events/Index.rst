..  include:: /Includes.rst.txt

..  _apireference-events:
..  _apireference-formeditor-events:
..  _apireference-formeditor-hooks-beforeformcreate:
..  _apireference-formeditor-hooks-beforeformcreate-connect:
..  _apireference-formeditor-hooks-beforeformcreate-use:
..  _apireference-formeditor-events-beforeformiscreatedevent:
..  _apireference-formeditor-hooks-beforeformduplicate:
..  _apireference-formeditor-hooks-beforeformduplicate-connect:
..  _apireference-formeditor-hooks-beforeformduplicate-use:
..  _apireference-formeditor-hooks-beforeformdelete:
..  _apireference-formeditor-hooks-beforeformdelete-connect:
..  _apireference-formeditor-hooks-beforeformdelete-use:
..  _apireference-frontendrendering-runtimemanipulation-hooks:
..  _apireference-frontendrendering-runtimemanipulation-hooks-initializeformelement:
..  _apireference-frontendrendering-runtimemanipulation-hooks-initializeformelement-connect:
..  _apireference-frontendrendering-runtimemanipulation-hooks-initializeformelement-use:
..  _apireference-frontendrendering-runtimemanipulation-hooks-beforeremovefromparentrenderable:
..  _apireference-frontendrendering-runtimemanipulation-hooks-beforeremovefromparentrenderable-connect:
..  _apireference-frontendrendering-runtimemanipulation-hooks-beforeremovefromparentrenderable-use:
..  _apireference-frontendrendering-runtimemanipulation-hooks-afterbuildingfinished:
..  _apireference-frontendrendering-runtimemanipulation-hooks-afterbuildingfinished-connect:
..  _apireference-frontendrendering-runtimemanipulation-hooks-afterbuildingfinished-use:
..  _apireference-frontendrendering-runtimemanipulation-hooks-afterinitializecurrentpage:
..  _apireference-frontendrendering-runtimemanipulation-hooks-afterinitializecurrentpage-connect:
..  _apireference-frontendrendering-runtimemanipulation-hooks-afterinitializecurrentpage-use:
..  _apireference-frontendrendering-runtimemanipulation-hooks-aftersubmit:
..  _apireference-frontendrendering-runtimemanipulation-hooks-aftersubmit-connect:
..  _apireference-frontendrendering-runtimemanipulation-hooks-aftersubmit-use:
..  _apireference-frontendrendering-runtimemanipulation-hooks-beforerendering:
..  _apireference-frontendrendering-runtimemanipulation-hooks-beforerendering-connect:
..  _apireference-frontendrendering-runtimemanipulation-hooks-beforerendering-use:

================
PSR-14 Events
================

EXT:form dispatches PSR-14 events at key points in the lifecycle of a form –
both in the backend form editor and during frontend rendering. These events
are the recommended extension point for developers; the legacy
:php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']` hooks have been removed.

..  seealso::
    The canonical, always-up-to-date reference for all EXT:form events lives
    in the TYPO3 Core API documentation:

    `Form <https://docs.typo3.org/permalink/t3coreapi:eventlist-form>`_

    The extension documentation below provides context, usage notes and
    quick navigation; it intentionally avoids duplicating the full API
    reference.

..  contents::
    :depth: 1
    :local:

..  _apireference-events-backend:

Backend events (form editor / manager)
=======================================

These events are dispatched when an editor creates, saves, duplicates or
deletes a form definition in the TYPO3 backend.

..  _apireference-events-backend-table:

.. list-table::
   :header-rows: 1
   :widths: 40 60

   *  -  Event
      -  When / what can be modified
   *  -  `BeforeFormIsCreatedEvent
         <https://docs.typo3.org/permalink/t3coreapi:beforeformiscreatedevent>`_
      -  Modify the form definition array and/or the persistence identifier
         before a new form is created in the backend.
   *  -  `BeforeFormIsSavedEvent
         <https://docs.typo3.org/permalink/t3coreapi:beforeformissavedevent>`_
      -  Modify the form definition array and/or the persistence identifier
         before a form is saved in the backend.
   *  -  `BeforeFormIsDuplicatedEvent
         <https://docs.typo3.org/permalink/t3coreapi:beforeformisduplicatedevent>`_
      -  Modify the form definition array and/or the persistence identifier
         of the copy before a form is duplicated.
   *  -  `BeforeFormIsDeletedEvent
         <https://docs.typo3.org/permalink/t3coreapi:beforeformisdeletedevent>`_
      -  Dispatched before a form is deleted. Set
         :php:`$event->preventDeletion = true` to abort the deletion (the
         event implements :php:`StoppableEventInterface`).

..  _apireference-events-frontend:

Frontend events (form rendering / runtime)
==========================================

These events are dispatched during form rendering in the frontend.

..  _apireference-events-frontend-table:

.. list-table::
   :header-rows: 1
   :widths: 40 60

   *  -  Event
      -  When / what can be modified
   *  -  `AfterFormIsBuiltEvent
         <https://docs.typo3.org/permalink/t3coreapi:afterformisbuiltevent>`_
      -  Modify the :php:`FormDefinition` object after the form factory has
         finished building the complete form.
   *  -  `BeforeRenderableIsAddedToFormEvent
         <https://docs.typo3.org/permalink/t3coreapi:beforerenderableisaddedtoformevent>`_
      -  Modify or replace a renderable (page, section or element) before it
         is added to the form tree.
   *  -  `BeforeRenderableIsRemovedFromFormEvent
         <https://docs.typo3.org/permalink/t3coreapi:beforerenderableisremovedfromformevent>`_
      -  Dispatched before a renderable is removed from the form tree. Set
         :php:`$event->preventRemoval = true` to abort the removal (the
         event implements :php:`StoppableEventInterface`).
   *  -  `AfterCurrentPageIsResolvedEvent
         <https://docs.typo3.org/permalink/t3coreapi:aftercurrentpageisresolvedevent>`_
      -  Override :php:`$event->currentPage` after the current page has been
         resolved from the request, e.g. to implement conditional page-skip
         logic.
   *  -  `BeforeRenderableIsValidatedEvent
         <https://docs.typo3.org/permalink/t3coreapi:beforerenderableisvalidatedevent>`_
      -  Modify :php:`$event->value` before property-mapping and validation
         run for each submitted form element.
   *  -  `BeforeRenderableIsRenderedEvent
         <https://docs.typo3.org/permalink/t3coreapi:beforerenderableisrenderedevent>`_
      -  Modify the renderable or the :php:`FormRuntime` just before a
         renderable is output to the browser.
   *  -  `BeforeEmailFinisherInitializedEvent
         <https://docs.typo3.org/permalink/t3coreapi:beforeemailfinisherinitializedevent>`_
      -  Modify the options used by the :php:`EmailFinisher` (e.g. recipients,
         subject) before they are applied.
   *  -  `AfterFormDefinitionLoadedEvent
         <https://docs.typo3.org/permalink/t3coreapi:afterformdefinitionloadedevent>`_
      -  Dispatched by :php:`FormPersistenceManager` after a YAML form
         definition has been loaded from disk. Modify the definition globally
         before it reaches the form factory.

..  _apireference-events-register:

Registering an event listener
==============================

Register a listener via the :php:`#[AsEventListener]` PHP attribute:

..  literalinclude:: _codesnippets/_MyFormEventListener.php
    :language: php
    :caption: EXT:my_extension/Classes/EventListener/MyFormEventListener.php


..  seealso::
    :ref:`t3coreapi:EventDispatcher` – TYPO3 Core API documentation on how to
    register and implement PSR-14 event listeners.

..  _apireference-events-legacy-hooks:

Legacy hooks (still supported)
===============================

The following :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']` hooks are
**still active** in the current codebase. They have not yet been replaced by
PSR-14 events. Avoid using them in new code if a PSR-14 alternative exists.

..  _apireference-events-legacy-hooks-afterformstateinitialized:

afterFormStateInitialized
--------------------------

Dispatched by :php:`FormRuntime` after the :php:`FormState` has been
restored from the request. At this point both the form state (submitted
values) and the static form definition are available, which makes it
suitable for enriching components that need runtime data.

Implement :php:`\TYPO3\CMS\Form\Domain\Runtime\FormRuntime\Lifecycle\AfterFormStateInitializedInterface`
and register the class:

..  literalinclude:: _codesnippets/_ext-localconf-after-form-state-initialized.php
    :language: php
    :caption: EXT:my_extension/ext_localconf.php


..  literalinclude:: _codesnippets/_MyAfterFormStateInitializedHook.php
    :language: php
    :caption: EXT:my_extension/Classes/Hooks/MyAfterFormStateInitializedHook.php


..  _apireference-events-legacy-hooks-buildformvalidationconfiguration:

buildFormDefinitionValidationConfiguration
------------------------------------------

Used when a custom **form editor inspector** editor does not declare its
writable property paths via the standard YAML configuration (e.g.
:yaml:`propertyPath`). Implement :php:`addAdditionalPropertyPaths()` to
return additional :php:`ValidationDto` objects that tell the backend form
editor which properties may be written.

Register the hook class:

..  literalinclude:: _codesnippets/_ext-localconf-validation-configuration.php
    :language: php
    :caption: EXT:my_extension/ext_localconf.php


..  literalinclude:: _codesnippets/_MyValidationConfigurationHook.php
    :language: php
    :caption: EXT:my_extension/Classes/Hooks/MyValidationConfigurationHook.php
