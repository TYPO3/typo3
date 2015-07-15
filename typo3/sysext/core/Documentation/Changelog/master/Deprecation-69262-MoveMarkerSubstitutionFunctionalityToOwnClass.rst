=========================================================================
Deprecation: #69262 - Move marker substitution functionality to own class
=========================================================================

Description
===========

The marker substitution functionality has been moved from core/Classes/Html/HtmlParser.php to it's own class core/Classes/Utility/MarkerUtility.php

The following methods within HtmlParser have been marked as deprecated.

.. code-block:: php

	HtmlParser::getSubpart()
	HtmlParser::substituteSubpart()
	HtmlParser::substituteSubpartArray()
	HtmlParser::substituteMarker()
	HtmlParser::substituteMarkerArray()
	HtmlParser::substituteMarkerAndSubpartArrayRecursive()


Impact
======

Any usage of these methods will throw a deprecation warning.


Affected Installations
======================

Extensions that call these PHP methods directly.


Migration
=========

Change the use statement from TYPO3\CMS\Core\Html\HtmlParser to TYPO3\CMS\Core\Utility\MarkerUtility and change the class name of the static function calls from HtmlParser to MarkerUtility.

.. code-block:: php

	MarkerUtility::getSubpart()
	MarkerUtility::substituteSubpart()
	MarkerUtility::substituteSubpartArray()
	MarkerUtility::substituteMarker()
	MarkerUtility::substituteMarkerArray()
	MarkerUtility::substituteMarkerAndSubpartArrayRecursive()
