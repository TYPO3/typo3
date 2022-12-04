.. include:: /Includes.rst.txt

.. _feature-98914-1666689687:

=================================================
Feature: #98914 - TypoScript as request attribute
=================================================

See :issue:`98914`

Description
===========

The TYPO3 frontend middleware chain now sets up the request
attribute :php:`frontend.typoscript`. This is an instance of
:php:`\TYPO3\CMS\Core\TypoScript\FrontendTypoScript` and contains
the calculated TypoScript :php:`settings` (formerly "constants") and
sometimes :php:`setup`, depending on page cache status.

When a content object or plugin (plugins are content objects as well) needs the
current TypoScript, it can retrieve it using this API:

..  code-block:: php

    // New substitution of $GLOBALS['TSFE']->tmpl->setup
    $frontendTypoScriptSetupArray = $request->getAttribute('frontend.typoscript')->getSetupArray();

The :php:`FrontendTypoScript` attribute contains some more getters:

* :php:`getSettingsTree()`: The TypoScript settings as object tree. This tree is still
  a bit experimental in TYPO3 v12 and should only be used if really needed for now, it
  may still change. It is marked internal at the moment.

  The constants tree is *always* set up in frontend requests: It is needed early for page
  cache determination, content objects can expect it to be set.

* :php:`getFlatSettings()`: The TypoScript settings as flat array. Example TypoScript:

  ..  code-block:: typoscript

      mySettings {
          foo = fooValue
          bar = barValue
      }

  Result array:

  ..  code-block:: php

      $flatSettings = [
          'mySettings.foo' => 'fooValue',
          'mySettings.bar' => 'barValue',
      ];

  The settings array is *always* set up in frontend requests: It is needed early for page
  cache determination, content objects can expect it to be set.

* :php:`getSetupTree()`: The TypoScript setup as object tree. This tree is still
  a bit experimental in TYPO3 v12 and should only be used if really needed for now, it
  may still change. It is marked internal at the moment.

  The setup tree is only set up, if a frontend request could not be satisfied from page
  cache and a full page content calculation is required, or if a page cache does exist,
  but contains :typoscript:`USER_INT` or :typoscript:`COA_INT` that have to be calculated
  for each call. Effectively, when a content object rendering is called, the object can
  expect the setup object tree to be set.

* :php:`getSetupArray()`: An array representation of the setup tree. This is identical to
  the old :php:`TYPO3\CMS\Core\TypoScript\TemplateService->setup` that was usually accessed
  using :php:`$GLOBALS['TSFE']->tmpl->setup`.

  This is the main API to retrieve frontend TypoScript for now. Content objects do receive
  the current request from the rendering chain and can retrieve the full TypoScript this way,
  if needed. Note that content objects also retrieve the "local" content object configuration
  already, an access to the full TypoScript in general is only needed in seldom cases.

  The setup array is only set up if a frontend request could not be satisfied from page
  cache and a full page content calculation is required, or if a page cache does exist
  but contains :typoscript:`USER_INT` or :typoscript:`COA_INT` that have to be calculated
  for each call. Effectively, when a content object rendering is called, the object can
  expect the setup object tree to be set.


Impact
======

This is a substitution especially of the deprecated :php:`TYPO3\CMS\Core\TypoScript\TemplateService`,
typical old calls were :php:`TypoScriptFrontendController->tmpl` or :php:`$GLOBALS['TSFE']->tmpl`,
often reading the :php:`setup` property using :php:`tmpl->setup` to grab the current TypoScript
setup array. These calls should be avoided, the :php:`TemplateService` and the :php:`tmpl`
property will be removed in TYPO3 v13.


.. index:: Frontend, PHP-API, TypoScript, ext:frontend
