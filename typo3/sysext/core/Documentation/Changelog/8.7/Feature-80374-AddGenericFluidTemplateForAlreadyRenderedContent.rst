.. include:: /Includes.rst.txt

=========================================================================
Feature: #80374 - Add generic fluid template for already rendered content
=========================================================================

See :issue:`80374`

Description
===========

To provide better support for content elements where the content itself is not
processed by fluid we introduce a new generic template, to make it easy to
benefit from the universal layouts of fluid styled content.

The generic template only wrapps already generated html that have been assigned
to the variable `content`. This eliminates the need for extensions to provide
custom templates to wrap their external rendered content to achieve the same
behaviour as other fluid styled content elements.

Template
--------

.. code-block:: html

   <html xmlns:f="http://typo3.org/ns/TYPO3/CMS/Fluid/ViewHelpers" data-namespace-typo3-fluid="true">
   <f:layout name="Default" />
   <f:section name="Main">
      <f:comment>This templates is used to provide necessary functionality for external processed content and could be used across multiple sources, for example the frontend login content element.</f:comment>
      {content -> f:format.raw()}
   </f:section>
   </html>

Example Usage
-------------

.. code-block:: typoscript

   tt_content.mycontent =< lib.contentElement
   tt_content.mycontent {
      templateName = Generic
      variables {
         content = USER_INT
         content {
            userFunc = ACME\ContentExtension\Controller\SuperController->main
         }
      }
   }

.. index:: Fluid, TypoScript, Frontend
