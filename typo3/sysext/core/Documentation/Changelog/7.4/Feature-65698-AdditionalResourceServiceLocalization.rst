
.. include:: ../../Includes.txt

===========================================================================
Feature: #65698 - Additional localization files in backend workspace module
===========================================================================

See :issue:`65698`

Description
===========

The AdditionalResourceService of the workspace module in the backend is extended
by the functionality to register custom localization files that are forwarded to
the PageRenderer in the end. This way, labels can be accessed in JavaScript using
the TYPO3.l10n.localize() function for instance.

.. code-block:: php

	\TYPO3\CMS\Workspaces\Service\AdditionalResourceService::getInstance()->addLocalizationResource(
		'EXT:my_extension/Resources/Private/Language/locallang.xlf'
	);
