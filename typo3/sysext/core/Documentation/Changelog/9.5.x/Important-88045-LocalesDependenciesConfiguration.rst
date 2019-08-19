.. include:: ../../Includes.txt

======================================================
Important: #88045 - Locales dependencies configuration
======================================================

See :issue:`88045`

Description
===========

Due to a bug in :php:`TYPO3\CMS\Extbase\Utility\LocalizationUtility` the configured dependencies for
a (custom) locale were not taken into account.

One could circumvent this bug by declaring the following configuration as a workaround:

.. code-block:: php

   $GLOBALS['TYPO3_CONF_VARS']['SYS']['localization']['locales']['dependencies'] = [
      'de_AT' => [
         ['de']
      ]
   ];

Since this bug now has been fixed, installations using these kinds of workaround need to update
their configuration as described in the `official documentation`_.

.. _`official documentation`: https://docs.typo3.org/m/typo3/reference-coreapi/9.5/en-us/ApiOverview/Internationalization/ManagingTranslations.html#custom-languages

.. index:: Frontend, ext:extbase
