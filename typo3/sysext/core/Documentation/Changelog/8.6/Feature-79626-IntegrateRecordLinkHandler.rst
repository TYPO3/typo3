.. include:: /Includes.rst.txt

===============================================
Feature: #79626 - Integrate record link handler
===============================================

See :issue:`79626`

Description
===========

The functionality of EXT:linkhandler has been integrated into the core. It enables editors to link to single records.

The configuration consists of the following parts:

**PageTsConfig** is used to create a new tab in the LinkBrowser to be able to select records:

.. code-block:: typoscript

    TCEMAIN.linkHandler.anIdentifier {
        handler = TYPO3\CMS\Recordlist\LinkHandler\RecordLinkHandler
        label = LLL:EXT:extension/Resources/Private/Language/locallang.xlf:link.customTab
        configuration {
            table = tx_example_domain_model_item
        }
        scanBefore = page
    }

The following optional configuration is available:

- :typoscript:`configuration.hidePageTree = 1`: Hide the page tree in the link browser
- :typoscript:`configuration.storagePid = 1`: Let the link browser start with the given page
- :typoscript:`configuration.pageTreeMountPoints = 123,456`: Mount the given pages instead of the regular page tree

You can position your own handlers in order as defined in https://docs.typo3.org/typo3cms/extensions/core/latest/Changelog/7.6/Feature-66369-AddedLinkBrowserAPIs.html


**TypoScript** is used to generate the actual link in the frontend

.. code-block:: typoscript

    config.recordLinks.anIdentifier {
        // Do not force link generation when the record is hidden
        forceLink = 0

        typolink {
            parameter = 123
            additionalParams.data = field:uid
            additionalParams.wrap = &tx_example_pi1[item]=|&tx_example_pi1[controller]=Item&tx_example_pi1[action]=show
            useCacheHash = 1
        }
    }

.. index:: Backend, Frontend, PHP-API, TSConfig, TypoScript
