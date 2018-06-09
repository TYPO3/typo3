.. include:: ../../Includes.txt

===================================================================
Feature: #84650 - Introduce fluid data processor for language menus
===================================================================

See :issue:`84650`

Description
===========

This feature introduces a new :ts:`LanguageMenuProcessor` for Fluid based
language menus based on the languages defined for the current site.

Options
-------

:`if`:         TypoScript if condition
:`languages`:  A list of comma separated language IDs (e.g. 0,1,2) to use for
               the menu creation or `auto` to load from site languages
:`as`:         The variable to be used within the result

Example TypoScript configuration
--------------------------------

.. code-block:: typoscript

   10 = TYPO3\CMS\Frontend\DataProcessing\LanguageMenuProcessor
   10 {
      languages = auto
      as = languageNavigation
   }


Example Fluid-Template
----------------------

.. code-block:: html

   <f:if condition="{languageNavigation}">
      <ul id="language" class="language-menu">
         <f:for each="{languageNavigation}" as="item">
            <li class="{f:if(condition: item.active, then: 'active')}{f:if(condition: item.available, else: ' text-muted')}">
               <f:if condition="{item.available}">
                  <f:then>
                     <a href="{item.link}" hreflang="{item.hreflang}" title="{item.navigationTitle}">
                        <span>{item.navigationTitle}</span>
                     </a>
                  </f:then>
                  <f:else>
                     <span>{item.navigationTitle}</span>
                  </f:else>
               </f:if>
            </li>
         </f:for>
      </ul>
   </f:if>

.. index:: Fluid, TypoScript, Frontend
