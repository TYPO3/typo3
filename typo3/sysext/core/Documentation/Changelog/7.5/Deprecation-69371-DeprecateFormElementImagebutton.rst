
.. include:: /Includes.rst.txt

==================================================
Deprecation: #69371 - ext:Form element IMAGEBUTTON
==================================================

See :issue:`69371`

Description
===========

Form element `IMAGEBUTTON` of class `TYPO3\CMS\Form\Domain\Model\Element\ImagebuttonElement` has been marked as deprecated.
The related attribute `scr` of class `TYPO3\CMS\Form\Domain\Model\Attribute\SrcAttribute` has been marked as deprecated.

Impact
======

The element `IMAGEBUTTON` should not be used any longer because it is outdated and will be removed with TYPO3 CMS 8.


Affected Installations
======================

All installations which use the form element `IMAGEBUTTON`.
All installations which use a form typoscript like this:

.. code-block:: typoscript

	10 = IMAGEBUTTON
	10 {
		label = Image button
		src = /typo3conf/ext/someExt/some/picture.png
		value = value
	}

Migration
=========

No Migration is planned.


.. index:: PHP-API, TypoScript, ext:form
