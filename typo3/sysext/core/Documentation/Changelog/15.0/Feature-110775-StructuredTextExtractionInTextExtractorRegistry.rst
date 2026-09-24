..  include:: /Includes.rst.txt

..  _feature-110775-1790261961:

======================================================================
Feature: #110775 - Structured text extraction in TextExtractorRegistry
======================================================================

See :issue:`110775`

Description
===========

Text extractors, used to extract the textual content from files, can now
also provide the metadata they find in a file, declare the MIME types they
support, and work on any system resource: files of a FAL storage as well as
files of packages or public files outside of any storage.

A text extractor implementing the new interface
:php:`\TYPO3\CMS\Core\Resource\TextExtraction\StructuredTextExtractorInterface`
receives a :php:`\TYPO3\CMS\Core\SystemResource\Type\SystemResourceInterface`
and returns an :php:`\TYPO3\CMS\Core\Resource\TextExtraction\ExtractedText`
object, which holds the text together with a title, a description and
keywords. FAL files implement :php:`SystemResourceInterface`, so the same
extractor serves both:

..  code-block:: php
    :caption: EXT:my_extension/Classes/Resource/TextExtraction/PdfTextExtractor.php

    use TYPO3\CMS\Core\Attribute\AsTextExtractor;
    use TYPO3\CMS\Core\Resource\TextExtraction\ExtractedText;
    use TYPO3\CMS\Core\Resource\TextExtraction\StructuredTextExtractorInterface;
    use TYPO3\CMS\Core\SystemResource\Type\SystemResourceInterface;

    #[AsTextExtractor(priority: 10)]
    final class PdfTextExtractor implements StructuredTextExtractorInterface
    {
        public function getSupportedMimeTypes(): array
        {
            return ['application/pdf'];
        }

        public function extractStructuredText(SystemResourceInterface $resource): ExtractedText
        {
            // Read $resource->getContents() ...
            return new ExtractedText($text, $title, $description, $keywords);
        }
    }

:php:`getSupportedMimeTypes()` returns the lowercase MIME types which the
extractor can currently handle. The registry selects the extractor by the MIME
type of the resource, not by its file extension. An extractor that depends on
an external tool returns an empty list as long as the tool is not available.
This allows to list the supported file types without having a file at hand,
for example to offer them in a search form.

File types which are detected differently depending on the environment, for
example Office Open XML documents detected as ZIP archives, are resolved to one
MIME type by the file extension through the existing setting
:php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['FileInfo']['mimeTypeCompatibility']`,
so an extractor only declares the official MIME type. FAL applies the setting
when it detects the MIME type of a file. The registry applies it as well, if
no extractor supports the stored MIME type, so files indexed before an entry
was added are found without updating the file index. The default entries for
Office documents are described in :ref:`important-111287-1793349163`.

The :php:`\TYPO3\CMS\Core\Resource\TextExtraction\TextExtractorRegistry`
provides three new methods:

-   :php:`findTextExtractor(FileInterface|SystemResourceInterface $resource)`
    returns the extractor responsible for the resource. All extractors are
    asked in the order of their :php:`#[AsTextExtractor]` priority, whichever
    interface they implement: a structured extractor matches if it supports
    the MIME type of the resource, any other extractor if its
    :php:`canExtractText()` accepts the FAL file. A MIME type mapped by
    :php:`mimeTypeCompatibility` is only considered if no extractor matches.
-   :php:`extract(FileInterface|SystemResourceInterface $resource): ?ExtractedText`
    extracts the text with the extractor returned by
    :php:`findTextExtractor()`. The text of an extractor implementing only
    :php:`TextExtractorInterface` is wrapped into an :php:`ExtractedText`
    object. A file reference is resolved to its original file.
-   :php:`getSupportedFileExtensions(): array` returns the file extensions
    known for the MIME types declared by all registered structured text
    extractors, for consumers that offer file types for selection.

The :php:`PlainTextExtractor` shipped with TYPO3 Core implements both
interfaces and declares the MIME type :php:`text/plain`.

The interface :php:`TextExtractorInterface`, the method
:php:`TextExtractorRegistry->getTextExtractor()` and the attribute
:php:`#[AsTextExtractor]` are unchanged, so existing text extractors and their
consumers keep working. Both interfaces stay supported in TYPO3 v15, because
existing consumers call :php:`getTextExtractor()` and expect a
:php:`TextExtractorInterface`, while :php:`findTextExtractor()` and
:php:`extract()` serve extractors of both interfaces.

New text extractors should implement :php:`StructuredTextExtractorInterface`.
Implement :php:`TextExtractorInterface` in addition only if the extractor
shall also be returned by :php:`getTextExtractor()`, as the extractors shipped
with TYPO3 do.

Impact
======

Consumers of text extractors, such as the search indexing of
:composer:`typo3/cms-indexed-search`, can use the title, description and
keywords of a file, for example to weight them higher than the body text, and
can extract the text of files that are not part of a FAL storage. Extension
authors can implement :php:`StructuredTextExtractorInterface` to provide these
values, and to make the file types they support visible to consumers.

..  index:: FAL, PHP-API, ext:core
