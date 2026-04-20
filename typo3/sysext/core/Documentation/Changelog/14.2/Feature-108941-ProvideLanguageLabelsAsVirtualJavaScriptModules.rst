..  include:: /Includes.rst.txt

..  _feature-108941-1770902109:

========================================================================
Feature: #108941 - Provide language labels as virtual JavaScript modules
========================================================================

See :issue:`108941`

Description
===========

JavaScript modules can now import language labels as code.
The labels are exposed as an object with a `get()` method
and allows placeholder substitution conforming to the ICU message format.

..  code-block:: javascript

    // Import labels from language domain "core.bookmarks"
    import { html } from 'lit';
    import labels from '~labels/core.bookmarks';

    // Use label
    html`<p>{labels.get('groupType.global')}</p>`

    // Retrieve label and use ICU MessageFormat placeholders
    // Example label: <source>File "{filename}" deleted</source>
    html`<p>{labels.get('file.deleted', { filename: 'my-file.txt' })}</p>`

    // Render a label containing pseudo XML tags
    // Example label: "File <bold>{filename}</bold> deleted"
    html`<p>{labels.get('file.deleted', {
        filename: 'my-file.txt',
        // Callback function that renders the contents of <bold>
        bold: chunks => html`<strong>${chunks}</strong>`,
    })}</p>`

This means controllers do not need to inject arbitrary labels into the
global `TYPO3.lang` configuration, which impeded writing generic web
components.

Virtual JavaScript modules (schema `~labels/{language.domain}`)
are created that resolve the labels for the specified language domain
provided after the `~labels/` prefix. This mapping is
implemented technically by using an import map path prefix which instructs the
JavaScript engine to append a specified suffix to the mapped prefix.

The labels can be cached client-side with a far-future cache lifetime,
similar to static resources. TYPO3 therefore generates version-specific
and locale-specific URLs to ensure labels can be cached by the user
agent without requiring explicit cache invalidation.

Impact
======

Extension developers can now use labels in JavaScript components without
requiring labels to be preloaded globally or per module, reducing the
risk of missing labels and also simplifying developer workflows.

Workarounds such as pushing labels to the top frame,
loading labels globally, and adding labels to component attributes
have previously been used and are replaced by this infrastructure.

..  index:: Backend, JavaScript, ext:backend
