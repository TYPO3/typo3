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
key. On the one hand, this improves readability in corresponding TypoScript
configurations, since those aliases / identifiers can be used instead of
the fully-qualified class name, while also providing dependency injection out
of the box. On the other hand, this allows improving and enhancing the
functionality of data processors in the future by automatically adding
tagged processors to a registry.

Tagging a data processor in the :file:`Configuration/Services.yaml` file:

..  code-block:: yaml

    Vendor\MyExt\DataProcessing\AwesomeProcessor:
      tags:
        - { name: 'data.processor', identifier: 'awesome' }

Usage in TypoScript:

..  code-block:: typoscript

    dataProcessing.10 = awesome

All data processors shipped by TYPO3 are already tagged and can therefore
be used with their alias / identifier in your TypoScript configuration:

..  code-block:: typoscript

    # Default with fully-qualified class name (still supported):
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

..  note::

    The standard service aliasing mechanism is still supported. However,
    it is recommended to tag the data processors instead, because this will
    automatically add them to the internal :php:`DataProcessorRegistry`,
    enabling dependency injection by default. Otherwise the service would need
    to be set :yaml:`public`.

..  note::

    It might be that your data processor should not be shared. In such case
    you need to set the :yaml:`shared: false` tag attribute for the service.

Impact
======

Data processors can now be tagged with the :yaml:`data.processor` tag. This
allows to define an alias / identifier, which can then be used instead of
the fully-qualified class name, e.g. in TypoScript configurations.

.. index:: TypoScript, Frontend, ext:frontend
