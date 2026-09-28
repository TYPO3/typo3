.. include:: /Includes.rst.txt

.. _feature-110816-1790589606:

======================================================================
Feature: #110816 - Highlight workspace changes in the frontend preview
======================================================================

See :issue:`110816`

Description
===========

The workspace preview notification in the frontend now contains a
"Highlight changes" checkbox, showing the number of records on the page that
have a version in the current workspace.

When switched on, changed records are outlined: new records with a solid,
modified records with a dashed and moved records with a dotted line. For
modified and moved records, the live version of the page is fetched once and
the text of each changed record is compared with its live counterpart.
Inserted words are highlighted, removed words are shown struck through at
their former position. Switching the checkbox off restores the page as it
was rendered. The choice is remembered in the browser.

Records rendered with the :html:`<f:render.record>` or
:html:`<f:render.contentArea>` ViewHelpers and with the
:typoscript:`CONTENT` or :typoscript:`RECORDS` content objects, for example
:typoscript:`styles.content.get`, are covered. They are marked by event
listeners to :php:`\TYPO3\CMS\Fluid\Event\ModifyRenderedRecordEvent`
and :php:`\TYPO3\CMS\Frontend\ContentObject\Event\AfterRecordIsRenderedEvent`.

Deleted records are not rendered in the workspace preview and are therefore
not highlighted.

Impact
======

Changes of a workspace can be spotted directly in the frontend preview.

The records rendered in a workspace preview are wrapped in HTML comments,
unless the preview notification is disabled with
:typoscript:`config.disablePreviewNotification = 1`. Frontend requests with
`ADMCMD_prev=LIVE` by a logged-in backend user, which are used for the
comparison, mark all rendered records. The comments are removed from
responses which are not HTML pages.

Previews shared with a preview link without backend login only outline the
changed records, the text is not compared.

.. index:: Frontend, ext:workspaces
