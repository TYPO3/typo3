.. include:: /Includes.rst.txt

=======================================================================
Feature: #82354 - Add possibility to get a label in a specific language
=======================================================================

See :issue:`82354`

Description
===========

The extbase related LocalizationUtility now supports retrieving the localization
of a key in a different language than the initialized language of the given user.
This allows for instance rendering a text in french while the users language is
german. This feature works in the frontend and backend of TYPO3 and supports
to retrieve the labels via an extension name or an explicit specified
`LLL:path/locallang.xlf:label` key.

The ViewHelper :html:`<f:translate />` and the utility :php:`LocalizationUtility::translate()`
do support now two new optional Parameters `languageKey` and `alternativeLanguageKeys`
to control the output language.

Hint: The `alternativeLanguageKeys` will be used "reversed" (this behaviour was not changed here but could be confusing).
So if the `alternativeLanguageKeys` is defined with "fr,de", then "de" will used before "fr".


Basic Usage
===========

.. code-block:: php

    \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('someKey', 'extensionName', [], 'dk');


.. code-block:: html

   <f:translate key="someKey" languageKey="dk" />


.. index:: Fluid, PHP-API
