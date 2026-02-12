..  include:: /Includes.rst.txt

..  _deprecation-108963-1770907005:

==========================================================================
Deprecation: #108963 - Deprecate `PageRenderer->addInlineLanguageDomain()`
==========================================================================

See :issue:`108963`

Description
===========

:php:`PageRenderer->addInlineLanguageDomain()` has been deprecated
in favor of importing JavaScript modules as added in
:ref:`_feature-108941-1770902109`.

Impact
======

Extension developers can now use labels in JavaScript components, without
requiring to preload labels globally or per module, reducing the risk for
missing labels and simplifying developer workflows.


Affected installations
======================

The deprecated method has been added in 14.1, that means only installations that
used :php:`addInlineLanguageDomain()` in 14.1 are affected.


Migration
=========

The method call to :php:`PageRenderer::addInlineLanguageDomain()` can
be removed and the JavaScript part adds an module import that
imports from the :js:`'~label/'` prefix.

Before:

..  code-block:: php

    $pageRenderer->addInlineLanguageDomain('core.bookmarks');

..  code-block:: javascript

    import { html } from 'lit';
    import { lll } from '@typo3/core/lit-helper.js'

    html`<p>{lll('core.bookmarks:groupType.global')}</p>`


After::

..  code-block:: javascript

    import { html } from 'lit';
    // Import labels from language domain "core.bookmarks"
    import labels from '~labels/core.bookmarks';

    // Use label
    html`<p>{labels.get('groupType.global')}</p>`

..  index:: Backend, JavaScript, FullyScanned, ext:backend
