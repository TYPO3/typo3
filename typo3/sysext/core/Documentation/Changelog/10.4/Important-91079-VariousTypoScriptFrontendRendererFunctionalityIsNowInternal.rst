.. include:: /Includes.rst.txt

====================================================================================
Important: #91079 - Various TypoScriptFrontendRenderer functionality is now internal
====================================================================================

See :issue:`91079`

Description
===========

TypoScriptFrontendController has methods and properties which
are marked as "@internal" in TYPO3 v10.

They are still used in TYPO3 v10 from within TYPO3 Core, but
extension authors should use the actual APIs directly.

The properties

* :php:`TypoScriptFrontendController->sPre`
* :php:`TypoScriptFrontendController->pSetup`
* :php:`TypoScriptFrontendController->all`

are related to unpacking TypoScript details related
to a page object in TypoScript and to its caching part,
this is now officially marked as internal - if needed,
TemplateService should be queried directly. These properties
will likely be removed in future TYPO3 versions, in order to
decouple TypoScript Parsing from the global `TSFE` object.

The properties

* :php:`TypoScriptFrontendController->additionalJavaScript`
* :php:`TypoScriptFrontendController->additionalCSS`
* :php:`TypoScriptFrontendController->JSCode`
* :php:`TypoScriptFrontendController->inlineJS`

and the method :php:`TypoScriptFrontendController->setJS()` are
marked as internal. The AssetCollector API and the PageRenderer
can be used instead, and TYPO3 Core will move towards these
APIs completely internally.

The property :php:`TypoScriptFrontendController->indexedDocTitle`
is now marked as internal as the PageTitle API is in place since
TYPO3 v9 LTS.

.. index:: Frontend, ext:frontend
