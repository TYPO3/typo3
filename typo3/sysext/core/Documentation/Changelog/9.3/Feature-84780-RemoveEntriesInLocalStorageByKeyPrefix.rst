.. include:: /Includes.rst.txt

==============================================================
Feature: #84780 - Remove entries in localStorage by key prefix
==============================================================

See :issue:`84780`

Description
===========

The localStorage wrapper :js:`TYPO3/CMS/Backend/Storage/Client` is now capable of removing entries in the localStorage
by a specific key prefix.


Impact
======

The method :js:`Client.unsetByPrefix()` takes the prefix as argument. As all keys are internally namespaced with a :js:`t3-`
prefix, this must be omitted in the requested prefix.

Example:

.. code-block:: javascript

	function foo() {
	    // Removes any localStorage entry whose key starts with "t3-bar-"
	    Client.unsetByPrefix('bar-');
	}

.. index:: Backend, JavaScript, ext:backend
