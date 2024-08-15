.. include:: /Includes.rst.txt

.. _feature-99203-1704401590:

===========================================================================
Feature: #99203 - Streamline FE/versionNumberInFilename to 'EXT:' resources
===========================================================================

See :issue:`99203`

Description
===========

Local resources are currently not "cache-busted", for example, have no version
in URL. TypoScript has no possibility to add the cache buster. When replacing
them a new filename must be used (which feels little hacky).

getText "asset" to cache-bust assets in TypoScript
--------------------------------------------------

..  code-block:: typoscript
    :caption: EXT:my_extension/Configuration/TypoScript/setup.typoscript
    :emphasize-lines: 3

    page.20 = TEXT
    page.20 {
        value = { asset : EXT:core/Resources/Public/Icons/Extension.svg }
        insertData = 1
    }

..  code-block:: text
    :caption: Result

    typo3/sysext/core/Resources/Public/Icons/Extension.svg?1709051481

Cache-busted assets with the :html:`<f:uri.resource>` ViewHelper
----------------------------------------------------------------

..  code-block:: html
    :caption: EXT:my_extension/Resources/Private/Template/MyTemplate.html
    :emphasize-lines: 3

    <f:uri.resource
        path="EXT:core/Resources/Public/Icons/Extension.svg"
        useCacheBusting="true"
    />

..  code-block:: text
    :caption: Comparison

    Before: typo3/sysext/core/Resources/Public/Icons/Extension.svg
    Now: typo3/sysext/core/Resources/Public/Icons/Extension.svg?1709051481

The ViewHelper argument :html:`useCacheBusting` is enabled by default.

Depending on :php:`$GLOBALS['TYPO3_CONF_VARS']['FE']['versionNumberInFilename']`
the cache buster is applied as query string or embedded in the filename.

Impact
======

Local resources now can have a cache buster to easily replace them without
changing the filename.

.. index:: Fluid, Frontend, ext:frontend
