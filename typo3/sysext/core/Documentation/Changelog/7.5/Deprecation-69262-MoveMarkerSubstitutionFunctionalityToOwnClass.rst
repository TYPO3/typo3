
.. include:: ../../Includes.txt

=========================================================================
Deprecation: #69262 - Move marker substitution functionality to own class
=========================================================================

See :issue:`69262`

Description
===========

The marker substitution functionality has been moved from `core/Classes/Html/HtmlParser.php` to it's own
class `core/Classes/Service/MarkerBasedTemplateService.php`

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

Any usage of these methods will trigger a deprecation log entry.


Affected Installations
======================

Extensions that call these PHP methods directly.


Migration
=========

Change the use statement from `TYPO3\CMS\Core\Html\HtmlParser` to `TYPO3\CMS\Core\Service\MarkerBasedTemplateService`
and create an instance of this service class.
The methods are not static anymore, but named as before.

.. code-block:: php

	$templateService = GeneralUtility::makeInstance(MarkerBasedTemplateService::class);
	$templateService->getSubpart()
	$templateService->substituteSubpart()
	$templateService->substituteSubpartArray()
	$templateService->substituteMarker()
	$templateService->substituteMarkerArray()
	$templateService->substituteMarkerAndSubpartArrayRecursive()


.. index:: PHP-API
