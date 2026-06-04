.. include:: /Includes.rst.txt

.. _breaking-109998-1780583751:

==================================
Breaking: #109998 - Removed jQuery
==================================

See :issue:`109998`

Description
===========

The jQuery JavaScript library is no longer shipped with TYPO3. All remaining
usages in the TYPO3 backend have been migrated to native DOM APIs.

In detail, the following pieces have been removed:

- The bundled library file
  :file:`EXT:core/Resources/Public/JavaScript/Contrib/jquery.js`
- The import map entries :js:`jquery` and :js:`jquery/` provided by EXT:core
- The JavaScript module :js:`@typo3/backend/multi-step-wizard.js` together
  with its global :js:`TYPO3.MultiStepWizard` object

Note that :js:`@typo3/backend/multi-step-wizard.js` has been removed without
a prior deprecation phase. It was the recommended migration target when
:js:`@typo3/backend/wizard.js` was deprecated with TYPO3 v13.1
:ref:`(Deprecation entry) <deprecation-103230-1709202638>`. The module is
unused within TYPO3 itself since v14.2, when the form and translation wizards
switched to the internal :js:`typo3-backend-wizard` web component.

Furthermore, a couple of JavaScript API signatures changed because they
returned or received jQuery objects before:

- :js:`FormEngine.getFieldElement()` now returns a native :js:`HTMLElement`
  or :js:`null` instead of a jQuery collection
- The callbacks of the preview, new, duplicate and delete doc header actions
  of :js:`@typo3/backend/form-engine.js` (for example
  :js:`FormEngine.showPreviewModal()`) now receive a native
  :js:`HTMLInputElement` or :js:`HTMLAnchorElement` instead of a jQuery
  object
- The :js:`@typo3/backend/modal.js` API no longer accepts jQuery objects as
  modal content

Impact
======

Extensions that import :js:`jquery` in their backend JavaScript modules will
fail to load those modules in the browser because the bare module specifier
can no longer be resolved.

Extensions using :js:`@typo3/backend/multi-step-wizard.js` or the
:js:`TYPO3.MultiStepWizard` global will fail accordingly.

Extensions calling the changed :js:`FormEngine` and :js:`Modal` methods with
jQuery semantics will trigger JavaScript errors.

Affected installations
======================

All installations with extensions that load jQuery via the TYPO3 core import
map, use the multi step wizard module, or rely on the jQuery based
:js:`FormEngine` and :js:`Modal` API signatures in their backend JavaScript.

Migration
=========

jQuery
------

Extensions that still depend on jQuery need to ship their own copy of the
library and register it in their import map configuration:

..  code-block:: php
    :caption: EXT:my_extension/Configuration/JavaScriptModules.php

    return [
        'dependencies' => ['core'],
        'imports' => [
            'jquery' => 'EXT:my_extension/Resources/Public/JavaScript/Contrib/jquery.js',
        ],
    ];

Consider migrating extension JavaScript to native DOM APIs instead.

Multi step wizard
-----------------

There is no direct replacement for the removed
:js:`@typo3/backend/multi-step-wizard.js` module. TYPO3 itself uses the
:js:`typo3-backend-wizard` web component, which is currently internal and
provides no API stability guarantees yet. Extensions should either bring
their own wizard implementation, or copy the removed module into their own
codebase together with a bundled jQuery.

FormEngine
----------

Adapt callers of the changed :js:`FormEngine` methods to the native return
types:

..  code-block:: diff

    -const field = FormEngine.getFieldElement(fieldName).get(0);
    +const field = FormEngine.getFieldElement(fieldName);

.. index:: Backend, JavaScript, NotScanned, ext:backend
