.. include:: ../../Includes.txt

============================================================================
Feature: #79121 - Implement hook in typolink for modification of page params
============================================================================

See :issue:`79121`

Description
===========

A new hook has been implemented in ContentObjectRenderer::typoLink for links to pages. With this 
hook you can modify the link configuration, for example enriching it with additional parameters or 
meta data from the page row.


Impact
======

You can now register a hook via:

.. code-block:: php

    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typolinkProcessing']['typolinkModifyParameterForPageLinks'][] = \Your\Namespace\Hooks\MyBeautifulHook::class;

Your hook has to implement `TypolinkModifyLinkConfigForPageLinksHookInterface` with its method 
:php:`modifyPageLinkConfiguration(array $linkConfiguration, array $linkDetails, array $pageRow)`.
In :php:`$linkConfiguration` you get the configuration array for the link - this is what your hook 
can modify and **has to** return.
:php:`$linkDetails` contains additional information for your link and :php:`$pageRow` is the full
database row of the page.

For more information as to which configuration options may be changed, see TSRef_.

Example implementation:
-----------------------

.. code-block:: php

    public function modifyPageLinkConfiguration(array $linkConfiguration, array $linkDetails, array $pageRow) : array
    {
        $linkConfiguration['additionalParams'] .= $pageRow['myAdditionalParamsField'];
        return $linkConfiguration;
    }

.. _TSRef: https://docs.typo3.org/typo3cms/TyposcriptReference/Functions/Typolink/Index.html

.. index:: PHP-API, Backend
