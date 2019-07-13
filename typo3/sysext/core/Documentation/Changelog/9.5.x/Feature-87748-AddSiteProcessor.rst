.. include:: ../../Includes.txt

===================================
Feature: #87748 - Add SiteProcessor
===================================

See :issue:`87748`

Description
===========

A new Site Processor :php:`TYPO3\CMS\Frontend\DataProcessing\SiteProcessor` has been introduced which can be used to fetch data from the site entity.

.. code-block:: typoscript

   tt_content.mycontent.20 = FLUIDTEMPLATE
   tt_content.mycontent.20 {
      file = EXT:myextension/Resources/Private/Templates/ContentObjects/MyContent.html

      dataProcessing.10 = TYPO3\CMS\Frontend\DataProcessing\SiteProcessor
      dataProcessing.10 {
         as = site
      }
   }

In the Fluid template the properties of the site entity can be accessed

.. code-block:: html

   <p>{site.rootPageId}</p>
   <p>{site.someCustomConfiguration}</p>

.. index:: Fluid, Frontend, ext:frontend
