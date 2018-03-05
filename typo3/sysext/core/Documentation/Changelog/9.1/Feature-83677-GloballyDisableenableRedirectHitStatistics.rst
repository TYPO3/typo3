.. include:: ../../Includes.txt

=================================================================
Feature: #83677 - Globally disable/enable redirect hit statistics
=================================================================

See :issue:`83677`

Description
===========

The redirects module provides the optional feature to count hits on redirects.
On most installations this will probably be achieved using analytics tools such as
Google Analytics, Piwik or alike - for those not using any other ways of measuring,
counting hits can be enabled with a feature switch.


Impact
======

Redirect hit counting can be enabled by setting

.. code-block:: php

	'SYS' => [
		'features' => [
			'redirects.hitCount' => true
		],
	],

Be aware that every hit on a redirect will result in an additional SQL `UPDATE` query.

.. index:: Database, Frontend, LocalConfiguration
