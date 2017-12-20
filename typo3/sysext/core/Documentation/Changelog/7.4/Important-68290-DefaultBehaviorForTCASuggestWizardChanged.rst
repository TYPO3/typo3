
.. include:: ../../Includes.txt

===================================================================
Important: #68290 - Default behavior for TCA suggest wizard changed
===================================================================

See :issue:`68290`

Description
===========

The suggest wizard by default searches in the whole word instead only the beginning now. This might have performance
implications for large sites with a lot of records and/or sites that have a lot of tables that are searched, as the
search is done with a `LIKE "%searchterm%"`.

To switch back to the old behavior add `searchWholePhrase = FALSE` to the config of the suggest wizard.

Example to reset it for `page.shortcut`:

.. code-block:: php

	'shortcut' => array(
		'config' => array(
			'type' => 'group',
			'internal_type' => 'db',
			'allowed' => 'pages',
			'size' => '1',
			'maxitems' => '1',
			'minitems' => '0',
			'show_thumbs' => '1',
			'wizards' => array(
				'suggest' => array(
					'type' => 'suggest',
					'default' => array(
						'additionalSearchFields' => 'nav_title, alias, url',
						'searchWholePhrase' => FALSE
					)
				)
			)
		)
	),


.. index:: TCA, Backend
