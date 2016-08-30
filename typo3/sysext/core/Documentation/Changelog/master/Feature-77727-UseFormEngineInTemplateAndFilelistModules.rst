=================================================================
Feature: #77727 - Use FormEngine in Template and Filelist modules
=================================================================

Description
===========

The backend modules "Template" and "Filelist" have been migrated
to use the FormEngine for rendering the forms.


Impact
======

The class ``\TYPO3\CMS\Tstemplate\Controller\TypoScriptTemplateInformationModuleFunctionController`` received a new hook
:php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/tstemplate_info/class.tx_tstemplateinfo.php']['preOutputProcessingHook']`
which takes two parameters:
* :php:`$parameters` (array)
* :php:`$pObj` (TypoScriptTemplateInformationModuleFunctionController)

The ``formData`` element of the array ``$parameters`` contains the form structure for FormEngine, which may be
extended in a hook.

The TCA for t3editor fields has received a new configuration option ``ajaxSaveType`` required for saving hooks.
