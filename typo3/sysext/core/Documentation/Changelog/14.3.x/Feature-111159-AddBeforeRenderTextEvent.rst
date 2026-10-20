..  include:: /Includes.rst.txt

..  _feature-111159-render-text-event:

==========================================================
Feature: #111159 - Add a rendering event for f:render.text
==========================================================

See :issue:`111159`

Description
===========

The PSR-14 event :php-short:`\TYPO3\CMS\Fluid\Event\BeforeRenderTextEvent`
allows extensions to take over rendering of :html:`<f:render.text>`, for
example to provide inline editing controls without overriding the ViewHelper.

The event is dispatched after resolving and validating the record, string value,
and supported field schema, before escaping or rich-text processing. It exposes
the record, database table, full type, field name and schema, original value,
and optional request. Page information objects are
resolved to Record API objects before dispatching.

A listener can supply a complete result through
:php:`setRenderedText(UnsafeHTML $renderedText)`. This stops propagation to later
listeners and skips default rendering. The result object is returned unchanged,
preserving any additional metadata it provides. An empty
:php:`new UnsafeHTMLString('')` also counts as a replacement. The listener is
responsible for appropriate escaping or sanitization of its HTML.

If no listener supplies a result, existing rendering remains unchanged.
Missing optional fields return :php:`null` without dispatching the event.
Invalid records, non-string values, and unsupported field types retain their
existing exception behavior.

Example:

..  code-block:: php

    use TYPO3\CMS\Core\Attribute\AsEventListener;
    use TYPO3\CMS\Core\Schema\Field\InputFieldType;
    use TYPO3\CMS\Fluid\Event\BeforeRenderTextEvent;
    use TYPO3Fluid\Fluid\Core\Parser\UnsafeHTMLString;

    #[AsEventListener(identifier: 'my-extension/render-text')]
    final class RenderTextListener
    {
        public function __invoke(BeforeRenderTextEvent $event): void
        {
            if (
                !$event->getFieldSchema() instanceof InputFieldType
                || !$event->getRequest()?->getAttribute('my_extension.edit_mode')
            ) {
                return;
            }

            $event->setRenderedText(new UnsafeHTMLString(
                '<span class="editable">'
                . htmlspecialchars($event->getValue())
                . '</span>'
            ));
        }
    }

Impact
======

Extensions can replace text rendering through an event listener while preserving
the default behavior for fields or requests they do not handle.

..  index:: Fluid, PHP-API, ext:fluid
