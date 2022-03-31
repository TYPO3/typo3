
.. include:: /Includes.rst.txt

============================================================================
Deprecation: #69401 - Adopt ext:form to support the Extbase/ Fluid MVC stack
============================================================================

See :issue:`69401`

Description
===========

Form is now based on Extbase/ Fluid for frontend rendering. Therefore
all TypoScript based layout settings have been marked as deprecated. Using the
following code is not recommended anymore:

.. code-block:: typoscript

	10 = FORM
	10 {
		layout {
			containerWrap = <div><elements /></div>
			elementWrap = <div><element /></div>
		}
	}

Impact
======

All `.layout` TypoScript properties should not be used anymore. Backward
compatibility algorithms will be removed with TYPO3 CMS 8.


Affected Installations
======================

All installations using `.layout` TypoScript properties.


Migration
=========

Move away from `.layout` TypoScript properties and move to Fluid based
templating.


.. index:: TypoScript, ext:form
