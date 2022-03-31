
.. include:: /Includes.rst.txt

=====================================================================================
Feature: #64535 - IRRE: Suppress and override useCombination warning via TCA settings
=====================================================================================

See :issue:`64535`

Description
===========

When using `useCombination=TRUE` there is always a FlashMessage warning displayed.
It is now possible to override the default warning message with a custom message or
to suppress the FlashMessage completely via TCA setting.

Example to suppress `useCombination` warning message:

.. code-block:: php

	$GLOBALS['TCA']['tx_demo_domain_model_demoinline']['columns']['irre_records']['config'] = array(
		'foreign_types_combination' => array(
			'1' => array(
				'showitem' => 'title'
			)
		)
		'appearance' => array(
			'suppressCombinationWarning' => TRUE
			'useCombination' => TRUE
		)
	)

Example to override `useCombination` warning message:

.. code-block:: php

	$GLOBALS['TCA']['tx_demo_domain_model_demoinline']['columns']['irre_records']['config'] = array(
		'foreign_types_combination' => array(
			'1' => array(
				'showitem' => 'title'
			)
		)
		'appearance' => array(
			'overwriteCombinationWarningMessage' => 'LLL:EXT:demo/Resources/Private/Language/locallang_db.xlf:tx_demo_domain_model_demoinline.irre_records.useCombinationWarning'
			'useCombination' => TRUE
		)
	)


.. index:: TCA, Backend
