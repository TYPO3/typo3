..  include:: /Includes.rst.txt

..  _feature-108941-1770902109:

========================================================================
Feature: #108941 - Provide language labels as virtual JavaScript modules
========================================================================

See :issue:`108941`

Description
===========

JavaScript modules can now import language labels as code.
The labels are exposed as a object that offers a `get()` method
and allows to substitute placeholders.


..  code-block:: javascript

    // Import labels from language domain "core.bookmarks"
    import { html } from 'lit';
    import labels from '~labels/core.bookmarks';

    // Use label
    html`<p>{labels.get('groupType.global')}</p>`


This avoids the need for controllers to inject arbitrary labels into
global `TYPO3.lang` configuration, which impeded writing generic
web components. (Often hindered simple adopting by a plain import)

Virtual JavaScript modules (schema `~label/{language.domain}`)
are created that resolve the labels for the specified language domain,
that is provided after the prefix `~label/`. Technically this mapping is
implemented using an importmap path prefix, which instructs to the
JavaScript engine to append the specified suffix to the mapped prefix.

The labels are allowed to be cached client side with a far future
cache timeout, similar to static resources. We therefore generate
version and locale-specific URLs, to ensure labels can be cached by
the user agent, without requiring explicit cache invalidation.


Impact
======

Extension developers can now use labels in JavaScript components, without
requiring to preload labels globally or per module, reducing the risk for
missing labels and simplifying developer workflows.

Several hacks like pushing labels to the top frame,
loading labels globally or adding labels to component attributes
have been used previously and will be replaced by this infrastructure.

..  index:: Backend, JavaScript, ext:backend
