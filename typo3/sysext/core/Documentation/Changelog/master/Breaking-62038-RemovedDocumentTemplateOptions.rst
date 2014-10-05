============================================================
Breaking: #62038 - Deprecated DocumentTemplate functionality
============================================================

Description
===========

The Backend/DocumentTemplate class contains various options that have no effect in the core anymore:

$doc->JScodeLibArray
$doc->docType (as rendering is always as HTML5 by default)
$doc->inDocStyles (use inDocStylesArray)
$doc->inDocStyles_TBEstyle (now used as inDocStylesArray[tbeStyle]
$doc->charset (always utf-8)

The methods $doc->formatTime() and $doc->menuTable() has also
been deprecated as they are not in use anymore.

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
