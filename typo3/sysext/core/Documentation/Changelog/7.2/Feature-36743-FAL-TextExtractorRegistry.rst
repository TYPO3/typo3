
.. include:: /Includes.rst.txt

============================================================
Feature: #36743 - Registry for adding text extractor classes
============================================================

See :issue:`36743`

Description
===========

Text extraction from files is a complex task. Thus it would be un-wise to
implement that over and over again if needed. By providing a registry, text
extraction services can be provided to other extensions.

It is expected that there won't ever be a lot of implementations for text
extractors.
The core ships with an extractor for plain text files (.txt file extension).

When asking the registry to provide a text extractor for a file it will "ask"
the registered text extractors whether they can read the file. The first text
extractor returning TRUE will be returned and can then be used to actually
read/extract text from the file.

Every registered text extractor class needs to implements the
TextExtractorInterface with the following methods:

- canExtractText() gets a file reference and returns TRUE if the text extractor
  can extract text from that file. How this is determined is up to the text
  extractor, f.e. by using MIME type or file extension as indicators.
- extractText() gets a file reference and is expected to return the file's text
  content as string.

It is possible to register your own text extractor classes in the
ext_localconf.php of an extension.

Examples
--------

Text extractor registration

.. code-block:: php

	$textExtractorRegistry = \TYPO3\CMS\Core\Resource\TextExtraction\TextExtractorRegistry::getInstance();
	$textExtractorRegistry->registerTextExtractor(
		\TYPO3\CMS\Core\Resource\TextExtraction\PlainTextExtractor::class
	);


Usage

.. code-block:: php

	$textExtractorRegistry = \TYPO3\CMS\Core\Resource\TextExtraction\TextExtractorRegistry::getInstance();
	$extractor = $textExtractorRegistry->getTextExtractor($file);
	if ($extractor !== NULL) {
		$content = $extractor->extractText($file);
	}


Impact
======

The registry on its own doesn't do anything. It provides a facility in the core
that allows extensions to provide text extraction services to be used by other
extensions.


.. index:: PHP-API, FAL
