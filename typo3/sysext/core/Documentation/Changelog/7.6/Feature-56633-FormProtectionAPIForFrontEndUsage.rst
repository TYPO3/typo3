
.. include:: ../../Includes.txt

========================================================
Feature: #56633 - Form protection API for frontend usage
========================================================

See :issue:`56633`

Description
===========

As of now frontend plugins needed to implement CSRF protection on their own. This change introduces a new
class to allow usage of the FormProtection (CSRF protection) API in the frontend.

Usage is the same as in backend context:

.. code-block:: php

	$formToken = \TYPO3\CMS\Core\FormProtection\FormProtectionFactory::get()
		->getFormProtection()->generateToken('news', 'edit', $uid);


	if ($dataHasBeenSubmitted
		&& \TYPO3\CMS\Core\FormProtection\FormProtectionFactory::get()->validateToken(
			\TYPO3\CMS\Core\Utility\GeneralUtility::_POST('formToken'),
			'User setup',
			'edit'
		)
	) {
		// Processes the data.
	} else {
		// Create a flash message for the invalid token or just discard this request.
	}


Impact
======

FormProtection API can now also be used in frontend context.


.. index:: PHP-API, Frontend
