..  include:: /Includes.rst.txt

..  _breaking-111521-1795723709:

===============================================
Breaking: #111521 - getData type global removed
===============================================

See :issue:`111521`

Description
===========

The TypoScript :ref:`getData <t3tsref:data-type-gettext>` type
:typoscript:`global`, which allowed reading arbitrary PHP global variables
such as :php:`$GLOBALS['TYPO3_CONF_VARS']` via TypoScript, has been removed.

It is a leftover from times when the frontend state was kept in global
variables like :php:`$GLOBALS['TSFE']`. This state is nowadays available via
request attributes, the Context API and the site configuration, which can be
accessed with dedicated getData types.

Impact
======

Using :typoscript:`data = global : ...` or :typoscript:`{global : ...}` with
:typoscript:`insertData` no longer resolves a value and returns an empty
string. No error or deprecation notice is raised.

Affected installations
======================

Installations using the getData type :typoscript:`global` in TypoScript, for
example in :typoscript:`stdWrap.data`, :typoscript:`insertData` or the
:typoscript:`TEXT` content object.

Migration
=========

Use the dedicated getData types for the information needed:

*   :typoscript:`request` for request attributes, query parameters and the
    parsed body
*   :typoscript:`context` for the Context API aspects, such as the date,
    the frontend user or the language
*   :typoscript:`site`, :typoscript:`siteLanguage` and
    :typoscript:`siteSettings` for the site configuration
*   :typoscript:`getIndpEnv` and :typoscript:`getEnv` for server and
    environment information

Values from :php:`$GLOBALS['TYPO3_CONF_VARS']` that are needed in the frontend
should be provided as site settings instead.

..  code-block:: typoscript
    :caption: EXT:my_sitepackage/Configuration/Sets/MySet/setup.typoscript

    # Before
    page.10 = TEXT
    page.10.data = global : TYPO3_CONF_VARS|SYS|sitename

    # After, with the site setting "mySitepackage.siteName"
    page.10 = TEXT
    page.10.data = siteSettings : mySitepackage.siteName

If no dedicated getData type fits, register an event listener for
:php:`\TYPO3\CMS\Frontend\ContentObject\Event\AfterGetDataResolvedEvent` to
provide the value.

..  index:: TypoScript, Frontend, NotScanned, ext:frontend
