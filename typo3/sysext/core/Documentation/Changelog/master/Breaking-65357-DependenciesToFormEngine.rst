=============================================
Breaking: #65357 - Dependencies to FormEngine
=============================================

Description
===========

A bigger refactoring of FormEngine classes and its sub classes broke a number
of pubic properties and a couple of methods of the FormEngine class.

Ignored properties
------------------

FormEngine->$defaultInputWidth
FormEngine->$minimumInputWidth
FormEngine->$maxInputWidth
FormEngine->$form_largeComp
FormEngine->$form_rowsToStylewidth
FormEngine->$defaultMultipleSelectorStyle
FormEngine->$charsPerRow
FormEngine->$RTEenabled_notReasons
FormEngine->$RTEenabled
FormEngine->$disableRTE
FormEngine->$backPath
FormEngine->$formName
FormEngine->$palFieldArr
FormEngine->$commentMessages
FormEngine->$edit_docModuleUpload
FormEngine->$isPalettedoc
FormEngine->$paletteMargin
FormEngine->$cachedTSconfig_fieldLevel
FormEngine->$transformedRow
FormEngine->$globalShowHelp
FormEngine->$doPrintPalette
FormEngine->$enableClickMenu
FormEngine->$enableTabMenu
FormEngine->$form_additionalTextareaStyleWidth
FormEngine->$edit_showFieldHelp
FormEngine->$clientInfo
FormEngine->$savedSchemes
FormEngine->$additionalJS_pre
FormEngine->$cachedTSconfig
FormEngine->$defaultLanguageData
FormEngine->$printNeededJS
FormEngine->$clipObj
EditDocumentController->$disHelp
InlineElement->$fObj
SuggestElement->$suggestCount
SuggestElement->$TCEformsObj
DataPreprocessor->$disableRTE

Other property changes
----------------------

FormEngine->$allowOverrideMatrix is now protected
SuggestElement->class is now protected


Changed user functions and hooks
--------------------------------

TCA: If format of type=none is set to user, the configured userFunc no longer gets an instance of FormEngine
as parent object, but an instance of NoneElement.

TCA: Wizards configured as "userFunc" now receive a dummy FormEngine object with empty properties instead
of the real instance.


Breaking methods
----------------

FormEngine->renderWizards()
FormEngine->dbFileIcons()
FormEngine->getClipboardElements()
SuggestElement->init()

Breaking interface changes
--------------------------

The type hint to FormEngine as $pObj had to be removed on the DatabaseFileIconsHookInterface.
This hook is no longer given an instance of FormEngine.


Impact
======

Affected properties are removed or deprecated and have no effect anymore. This
shouldn't be a big problem in most cases since most properties were for internal
handling.

Affected methods will throw an exception and stop working in case they are called.


Affected installations
======================

Instances with extensions that operate on TYPO3\CMS\Backend\Form\FormEngine
are likely to be affected.


Migration
=========

Refactor calling code to not use those methods anymore.