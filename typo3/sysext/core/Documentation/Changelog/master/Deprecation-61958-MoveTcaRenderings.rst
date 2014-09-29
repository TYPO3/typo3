===========================================
Deprecation: #61958 - TCA rendering methods
===========================================

Description
===========

The following methods of the class \TYPO3\CMS\Backend\Form\FormEngine are deprecated:

 * getSingleField_typeInput
 * getSingleField_typeText
 * getSingleField_typeCheck
 * getSingleField_typeRadio
 * getSingleField_typeSelect
 * getSingleField_typeGroup
 * getSingleField_typeNone
 * getSingleField_typeFlex
 * getSingleField_typeUnknown
 * getSingleField_typeUser

Each method is moved into a designated class inside \TYPO3\CMS\Backend\Form\Element to clean up the FormEngine class.


Impact
======

If a 3rd party extension calls the mentioned methods directly, a deprecation log will be created.

Affected installations
======================

All installations which call the mentioned methods.

Migration
=========

Every call of a 3rd party extension to the mentioned method must be changed to use the new classes.