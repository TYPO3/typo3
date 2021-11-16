.. include:: /Includes.rst.txt

.. _feature-96005-1660340104:

===============================================================
Feature: #96005 - Allow tagging and aliasing of data processors
===============================================================

See :issue:`96005`

Description
===========

It is now possible to set an alias / identifier for data processors by
tagging them with the :yaml:`data.processor` tag in the
:file:`Configuration/Services.yaml` file and defining the :yaml:`identifier`
key. This on the one hand improves readability in corresponding TypoScript
configurations, since those aliases / identifiers can be used instead of
the FQCN, while also providing DI out of the box. On the other hand this
allows improving and enhancing data processor functionality in the future
due to automatically adding of tagged processors to a registry.

Tagging a data processor in the :file:`Configuration/Services.yaml` file:

.. code-block:: yaml

  Vendor\MyExt\DataProcessing\AwesomeProcessor:
    tags:
      - { name: 'data.processor', identifier: 'awesome' }

Usage in TypoScript:

.. code-block:: typoscript

    dataProcessing.10 = awesome

All data processors shipped by TYPO3 are already tagged and therefore
can also be used with their alias / identifier in your TypoScript configuration:

.. code-block:: typoscript

    # Default with FQCN (still supported):
    dataProcessing {
        10 = TYPO3\CMS\Frontend\DataProcessing\CommaSeparatedValueProcessor
        20 = TYPO3\CMS\Frontend\DataProcessing\DatabaseQueryProcessor
        30 = TYPO3\CMS\Frontend\DataProcessing\FilesProcessor
        40 = TYPO3\CMS\Frontend\DataProcessing\FlexFormProcessor
        50 = TYPO3\CMS\Frontend\DataProcessing\GalleryProcessor
        60 = TYPO3\CMS\Frontend\DataProcessing\LanguageMenuProcessor
        70 = TYPO3\CMS\Frontend\DataProcessing\MenuProcessor
        80 = TYPO3\CMS\Frontend\DataProcessing\SiteProcessor
        90 = TYPO3\CMS\Frontend\DataProcessing\SiteLanguageProcessor
        100 = TYPO3\CMS\Frontend\DataProcessing\SplitProcessor
    }

    # New alternative using the alias / identifier:
    dataProcessing {
        10 = comma-separated-value
        20 = database-query
        30 = files
        40 = flex-form
        50 = gallery
        60 = language-menu
        70 = menu
        80 = site
        90 = site-language
        100 = split
    }

.. note::

    The standard service aliasing mechanism is still supported. However,
    it is recommanded to tag the data processors instead, because this will
    automatically add them to the internal :php:`DataProcessorRegistry`,
    enabling DI by default. Otherwise the service would need to be set
    :yaml:`public`.

Impact
======

Data processors can now be tagged with the :yaml:`data.processor` tag. This
allows to define an alias / identifier, which can then be used instead of
the fully-qualified class name in e.g. TypoScript configurations.

.. index:: TypoScript, Frontend, ext:frontend
