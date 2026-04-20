..  include:: /Includes.rst.txt

..  _deprecation-108963-1770907005:

==========================================================================
Deprecation: #108963 - Deprecate `PageRenderer->addInlineLanguageDomain()`
==========================================================================

See :issue:`108963`

Description
===========

:php:`\TYPO3\CMS\Core\Page\PageRenderer->addInlineLanguageDomain()` has been
deprecated in favor of importing JavaScript modules, as introduced in
:ref:`feature-108941-1770902109`.

Impact
======

Extension developers can now use labels in JavaScript components without
requiring labels to be preloaded globally or per module. This reduces the risk
of missing labels and simplifies developer workflows.

Affected installations
======================

The deprecated method was introduced in TYPO3 v14.1. This means that only
installations that use :php:`addInlineLanguageDomain()` in TYPO3 v14.1 or later
are affected.

Migration
=========

The call to :php:`PageRenderer::addInlineLanguageDomain()` can be removed. In
the JavaScript code, add a module import that imports from the
:js:`'~labels/'` prefix.

Before:

..  code-block:: php

    $pageRenderer->addInlineLanguageDomain('core.bookmarks');

..  code-block:: javascript

    import { html } from 'lit';
    import { lll } from '@typo3/core/lit-helper.js';

    html`<p>{lll('core.bookmarks:groupType.global')}</p>`


After:

..  code-block:: javascript

    import { html } from 'lit';
    // Import labels from language domain "core.bookmarks"
    import labels from '~labels/core.bookmarks';

    // Use label
    html`<p>{labels.get('groupType.global')}</p>`

..  index:: Backend, JavaScript, FullyScanned, ext:backend
