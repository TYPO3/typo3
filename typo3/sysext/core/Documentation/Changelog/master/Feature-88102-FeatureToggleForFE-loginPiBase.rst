.. include:: ../../Includes.txt

=====================================================
Feature: #88102 - Feature toggle for FE-login pi base
=====================================================

See :issue:`88102`

Description
===========

This feature toggle is used to deactivate pibased felogin form code.

.. code-block:: php

	'SYS' => [
		'features' => [
			'felogin.pibase' => false
		],
	],

.. index:: Frontend, LocalConfiguration, ext:felogin
