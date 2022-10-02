.. include:: /Includes.rst.txt

.. _feature-96800:

===========================================
Feature: #96800 - Add SiteLanguageProcessor
===========================================

See :issue:`96800`

Description
===========

A new Data Processor :php:`SiteLanguageProcessor` has been introduced, which
can be used to fetch the properties of the current SiteLanguage within Fluid
Templates in TYPO3 Frontend rendering:

..  code-block:: typoscript

    tt_content.mycontent.20 = FLUIDTEMPLATE
    tt_content.mycontent.20 {
       file = EXT:myextension/Resources/Private/Templates/ContentObjects/MyContent.html

       dataProcessing.10 = TYPO3\CMS\Frontend\DataProcessing\SiteLanguageProcessor
       dataProcessing.10 {
          as = language
       }
    }

In the Fluid template the properties of the SiteLanguage entity can be accessed:

..  code-block:: html

    <p>{language.languageId}</p>
    <p>{language.customValue}</p>

.. index:: Fluid, Frontend, TypoScript, ext:frontend
