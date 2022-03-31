
.. include:: /Includes.rst.txt

=====================================================================
Feature: #57632 - Include inline language label files with TypoScript
=====================================================================

See :issue:`57632`

Description
===========

It is now possible to add inline language label files with TypoScript.

Usage
-----
If you want to include inline labels from a XLF file, you have to specify that
file in your TypoScript with a custom key in the new `inlineLanguageLabelFiles`
section. In addition to the file you can configure three optional parameters:

* `selectionPrefix`: Only label keys that start with this prefix will be included (default: '')
* `stripFromSelectionName`: A string that will be removed from any included label key (default: '')
* `errorMode`: Error mode if the file could not be found: 0 - syslog entry, 1 - do nothing, 2 - throw an exception (default: 0)

Example
-------

.. code-block:: typoscript

	page = PAGE
	page.inlineLanguageLabelFiles {
		someLabels = EXT:myExt/Resources/Private/Language/locallang.xlf
		someLabels.selectionPrefix = idPrefix
		someLabels.stripFromSelectionName = strip_me
		someLabels.errorMode = 2
	}

Output in the HTML head:

.. code-block:: javascript

	var TYPO3 = TYPO3 || {};
	TYPO3.lang = {"firstLabel":[{"source":"first Label","target":"erstes Label"}],"secondLabel":[{"source":"second Label","target":"zweites Label"}]};


.. index:: TypoScript, JavaScript, Frontend
