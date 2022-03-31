.. include:: /Includes.rst.txt

========================================================================
Feature: #94577 - Clear indexed_search documents when content is changed
========================================================================

See :issue:`94577`

Description
===========

A new Extension Configuration setting `deleteFromIndexAfterEditing` has been added
to the extension `indexed_search`.

If enabled and a page or its content is edited, :php:`DataHandler` triggers a hook
to remove the page and its content from the search index.


Impact
======

A separate index and clearing it is always a tradeoff between having wrong content
and no content in the search result. The index is filled by website visitors or bots
calling the page in the frontend or by using the 3rd party extension crawler_.

If the setting is enabled and the page is not yet re-indexed, **no** content will
be shown in the search result, no matter if the editor just fixed one tiny typo in a content element.

If the feature flag is disabled, the editor needs to manually clear the index.

.. _crawler: https://extensions.typo3.org/extension/crawler

.. index:: Backend, ext:indexed_search
