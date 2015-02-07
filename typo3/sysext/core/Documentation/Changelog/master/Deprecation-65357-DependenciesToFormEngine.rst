================================================
Deprecation: #65357 - Dependencies to FormEngine
================================================

Description
===========

A bigger refactoring of FormEngine classes and its sub classes deprecated
a number of properties and methods.

Deprecated methods
------------------

FormEngine->getSingleField_typeNone_render()
FormEngine->formMaxWidth()
FormEngine->elName()
FormEngine->formatValue()
FormEngine->procItems()
FormEngine->getIcon()
FormEngine->getIconHtml()
FormEngine->initItemArray()
FormEngine->addItems()
FormEngine->setTSconfig()
FormEngine->addSelectOptionsToItemArray()
FormEngine->addSelectOptionsToItemArray_makeModuleData()
FormEngine->foreignTable()
FormEngine->optionTagStyle()
FormEngine->extractValuesOnlyFromValueLabelList()
FormEngine->overrideFieldConf()
FormEngine->getLanguageIcon()
FormEngine->getClickMenu()
EditDocumentController->tceformMessages()

Renamed classes
---------------

\TYPO3\CMS\Backend\Form\Element\SuggestElement renamed to \TYPO3\CMS\Backend\Form\Wizard\SuggestWizard
\TYPO3\CMS\Backend\Form\Element\SuggestDefaultReceiver renamed to \TYPO3\CMS\Backend\Form\Wizard\SuggestWizardDefaultReceiver
\TYPO3\CMS\Backend\Form\Element\VaueSlider renamed to \TYPO3\CMS\Backend\Form\Wizard\ValueSliderWizard


Impact
======

Methods listed here will still work, but are deprecated.


Affected installations
======================

Instances with extensions that operate on TYPO3\CMS\Backend\Form\FormEngine
are likely to be affected.


Migration
=========

Methods listed here are moved around to different classes or fully obsolete.
Take a look at the deprecation notices within the class structure to find
out on how to adapt code.