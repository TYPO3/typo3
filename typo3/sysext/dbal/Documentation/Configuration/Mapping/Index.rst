.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../Includes.txt



.. _mapping:

->mapping
^^^^^^^^^

Contains mapping of tablename and fields in a table. Notice that
entering any configuration for a table might affect performance since
translation is needed before results are returned or queries executed.

Mapping is totally transparent for applications inside TYPO3 and
mapping is independent of handler type - the translation goes on
between these two spheres.

Mapping can work as a work-around for reserved field- or table names.


.. _maptablename:

mapTableName
""""""""""""

.. container:: table-row

	Key
		mapTableName

	Datatype
		string

	Description
		Real, physical tablename for the table


.. _mapfieldnames-fieldname:

mapFieldNames[fieldname]
""""""""""""""""""""""""

.. container:: table-row

	Key
		mapFieldNames[fieldname]

	Datatype
		string

	Description
		Real, physical fieldname in the table.



.. _mapping-example:

Example
"""""""

.. code-block:: php
	:linenos:

	$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['dbal']['mapping'] = array(
	    'sys_note' => array(
	        'mapTableName' => 'SysNoteTable',
	        'mapFieldNames' =>  array(
	            'uid' => 'uid999',
	            'pid' => 'pid999',
	            'deleted' => 'deleted999',
	            'tstamp' => 'tstamp999',
	            'crdate' => 'crdate999',
	            'cruser' => 'cruser999',
	            'author' => 'author999',
	            'email' => 'email999',
	            'subject' => 'subject999',
	            'message' => 'message999',
	            'personal' => 'personal999',
	            'category' => 'category999'
	        )
	    ),
	    '_tt_content' => array(
	        'mapTableName' => 'tt_content999',
	        'mapFieldNames' => array(
	            'bodytext' => 'bodytext999',
	            'header' => 'header999',
	            'image' => 'image999',
	            'pid' => 'pid999',
	            'sorting' => 'sorting999',
	        )
	    )
	);

In this example two classic TYPO3 tables have been mapped; the
``sys_note`` table (from the ``sys_note`` extension) and the
``tt_content`` table (Content Elements).

According to this mapping example the ``sys_note`` table in the database
(or whatever data source) is actually named ``SysNoteTable`` and all
fields are actually named differently; with "...999" after (this is
just an example).

When you try to make a look up in the ``sys_note`` like

.. code-block:: sql

	SELECT uid FROM sys_note WHERE uid=123

then this is transformed into

.. code-block:: sql

	SELECT uid999 FROM SysNoteTable WHERE uid999=123

before executed. And the result row which will be ``array('uid999' => 123)`` will be transformed back to
``array('uid' => 123)`` before you receive it inside of TYPO3.

.. warning::
	Mapping tables to two different databases on localhost
	will most likely only work if ``[SYS][no_pconnect]`` is set in
	``$TYPO3_CONF_VARS``. Otherwise PHP will, regardless of DBAL maintaining
	different links for the databases, use the wrong one.
