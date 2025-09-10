..  include:: /Includes.rst.txt

..  _deprecation-107436-1736639846:

===========================================
Deprecation: #107436 - Localization Parsers
===========================================

See :issue:`107436`

Description
===========

Due to the migration to Symfony Translation components
(see :ref:`feature-107436-1736639846`), the following
localization parser classes have been deprecated:

* :php:`\TYPO3\CMS\Core\Localization\Parser\AbstractXmlParser`
* :php:`\TYPO3\CMS\Core\Localization\Parser\LocalizationParserInterface`
* :php:`\TYPO3\CMS\Core\Localization\Parser\XliffParser`

The global configuration option :php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['lang']['parser']`
has been removed and replaced with :php:`$GLOBALS['TYPO3_CONF_VARS']['LANG']['loader']`.

Impact
======

Using any of the mentioned parser classes will raise a deprecation level
log entry and will stop working in TYPO3 v15.0. The extension scanner will
report usages as **strong** match.

Affected installations
======================

Instances using the deprecated localization parser classes directly or
configuring custom parsers via the removed global configuration option.

Migration
=========

Replace usage of the deprecated parser classes with Symfony Translation
loaders. Use the new :php:`\TYPO3\CMS\Core\Localization\Loader\XliffLoader`
class for XLIFF file processing.

The Symfony Translator and its loaders are now responsible for file parsing
and should be used instead of the deprecated TYPO3 parsers.

For custom localization needs, implement Symfony Translation loader
interfaces instead of the deprecated TYPO3 parser interfaces.

Configuration changes:

.. code-block:: php

    // Before
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['lang']['parser']['xlf'] = \TYPO3\CMS\Core\Localization\Parser\XliffParser::class;

    // After
    $GLOBALS['TYPO3_CONF_VARS']['LANG']['loader']['xlf'] = \TYPO3\CMS\Core\Localization\Loader\XliffLoader::class;

Please note: This functionality only affects internal handling of translation
files ("locallang" files). The public API of the localization system remains
unchanged.

..  index:: PHP-API, FullyScanned, ext:core
