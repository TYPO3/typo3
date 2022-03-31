
.. include:: /Includes.rst.txt

============================================================
Breaking: #62038 - Deprecated DocumentTemplate functionality
============================================================

See :issue:`62038`

Description
===========

The Backend/DocumentTemplate class contains various options that have no effect in the core anymore:

:code:`$doc->JScodeLibArray`
:code:`$doc->docType` (as rendering is always as HTML5 by default)
:code:`$doc->inDocStyles` (use inDocStylesArray)
:code:`$doc->inDocStyles_TBEstyle` (now used as inDocStylesArray[tbeStyle]
:code:`$doc->charset` (always utf-8)

The methods :code:`$doc->formatTime()` and :code:`$doc->menuTable()` have also
been deprecated as they are not used anymore.

Impact
======

Extensions that still use the properties of DocumentTemplate will not see any changes in the output
of the code anymore.


Affected installations
======================

A TYPO3 instance is affected if a 3rd party extension uses the unused variables.


Migration
=========

The variables can be removed safely, any modifications is possible via hooks in DocumentTemplate.


.. index:: PHP-API, Backend
