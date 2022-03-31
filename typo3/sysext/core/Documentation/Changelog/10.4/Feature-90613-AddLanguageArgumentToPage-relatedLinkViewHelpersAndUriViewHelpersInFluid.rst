.. include:: /Includes.rst.txt

===================================================================================================
Feature: #90613 - Add language argument to page-related LinkViewHelpers and UriViewHelpers in Fluid
===================================================================================================

See :issue:`90613`

Description
===========

A new argument :html:`language` is added to the following Fluid ViewHelpers:

* :html:`<f:link.typolink>`
* :html:`<f:link.page>`
* :html:`<f:uri.typolink>`
* :html:`<f:uri.page>`

They are responsible for linking to a page, and are using TypoLink functionality
under-the-hood.


Examples
--------

A Link to page with ID 13 but with language 3 - no matter what language the
current page is:


.. code-block:: html

   <f:link.page pageUid="13" language="3">Go to french version of about us page</f:link.page>


Creating a language menu:

.. code-block:: html

   <ul>
     <li>
       <f:link.typolink parameter="current" language="3">Current page in french</f:link.typolink>
     </li>
     <li>
       <f:link.typolink parameter="current" language="4">Current page in german</f:link.typolink>
     </li>
     <li>
       <f:link.typolink parameter="current" language="5">Current page in spanish</f:link.typolink>
     </li>
   </ul>


Impact
======

The new argument allows to force a language when linking to a specific page,
making it consistent with the TypoLink option added in site handling for TYPO3 v9:

https://docs.typo3.org/m/typo3/reference-typoscript/main/en-us/Functions/Typolink.html#language

This Fluid option should be used instead of adding a `L` parameter to
`additionalParameters` argument to make linking to a specific language possible.
In general, using of the magic GET variable `L` is discouraged.

.. index:: Fluid, ext:fluid
