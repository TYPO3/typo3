..  include:: /Includes.rst.txt

..  _feature-108819-1738329600:

==========================================================================
Feature: #108819 - RecordFieldPreviewProcessor for custom PreviewRenderers
==========================================================================

See :issue:`108819`

Description
===========

A new service :php:`\TYPO3\CMS\Backend\Preview\RecordFieldPreviewProcessor`
has been introduced to provide common field rendering helpers for custom
content element preview renderers.

Previously, these helper methods were only available in
:php:`StandardContentPreviewRenderer`, which required custom preview
renderers to extend that class to access them.

Instead, this service uses the pattern of Composition over Inheritance.

The new service provides the following methods:

prepareFieldWithLabel()
-----------------------

Renders a field value with its TCA label prepended in bold.

..  code-block:: php

    public function prepareFieldWithLabel(RecordInterface $record, string $fieldName): ?string

prepareField()
--------------

Renders a processed field value without a label.

..  code-block:: php

    public function prepareField(RecordInterface $record, string $fieldName): ?string

prepareText()
-------------

Processes larger text fields (e.g., RTE content) with truncation and HTML
stripping.

..  code-block:: php

    public function prepareText(RecordInterface $record, string $fieldName, int $maxLength = 1500): ?string

preparePlainHtml()
------------------

Renders plain HTML content with line limiting.

..  code-block:: php

    public function preparePlainHtml(RecordInterface $record, string $fieldName, int $maxLines = 100): ?string

prepareFiles()
--------------

Renders thumbnails for file references.

..  code-block:: php

    public function prepareFiles(iterable|FileReference $fileReferences): ?string

linkToEditForm()
----------------

Wraps content in an edit link if the user has appropriate permissions.

..  code-block:: php

    public function linkToEditForm(string $linkText, RecordInterface $record, ServerRequestInterface $request): string


Impact
======

Extension developers implementing custom preview renderers can now inject
:php:`RecordFieldPreviewProcessor` to access common field rendering helpers
without extending :php:`StandardContentPreviewRenderer`.

Example usage:

..  code-block:: php

    use TYPO3\CMS\Backend\Preview\RecordFieldPreviewProcessor;
    use TYPO3\CMS\Backend\Preview\PreviewRendererInterface;

    final class MyCustomPreviewRenderer implements PreviewRendererInterface
    {
        public function __construct(
            private readonly RecordFieldPreviewProcessor $fieldProcessor,
        ) {}

        public function renderPageModulePreviewContent(GridColumnItem $item): string
        {
            $record = $item->getRecord();
            $content = $this->fieldProcessor->prepareFieldWithLabel($record, 'header');
            $content .= $this->fieldProcessor->prepareFiles($record->get('image'));
            return $content;
        }
    }

..  index:: Backend, PHP-API, ext:backend
