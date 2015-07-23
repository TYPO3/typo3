=============================================
Breaking: #69568 - FormEngine related classes
=============================================

Description
===========

The following classes have been removed:

* ``\TYPO3\CMS\Backend\Form\DataPreprocessor``
* ``\TYPO3\CMS\Backend\Form\FormEngine``
* ``\TYPO3\CMS\Backend\Form\FlexFormsHelper``

The following hook has been removed:

* ``$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tceforms.php']['getMainFieldsClass']``


Impact
======

Code instantiating these classes will fatal.


Affected Installations
======================

A rather low number of extensions should be affected by this change. Searching for the
above class names should reveal them.


Migration
=========

The methods and classes have been moved to different classes and solutions.
Extensions need adaptions.

The hook ``getMainFieldsClass`` was substituted with a much more fine grained and flexible API.
Use ``FormDataProvider`` to change data given to the render engine of FormEngine from now on.