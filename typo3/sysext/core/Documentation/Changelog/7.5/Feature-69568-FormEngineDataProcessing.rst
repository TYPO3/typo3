
.. include:: ../../Includes.txt

============================================
Feature: #69568 - FormEngine data processing
============================================

See :issue:`69568`

Description
===========

Warning: The `FormEngine` data structure will change in the future and extensions must
not rely on array or class structures at the moment.

The FormEngine construct to render records has been split to two main parts where the first
data processing part takes care of gathering and processing all data needed for the second part
to render final form data.

The data processing is done via `FormDataCompiler` that returns a data array that can be given
to the outer most render container. The array contains all main data required by the
render part like final `TCA` as well as the processed database row.

Extensions can change the data processing by registering additional items in the data processing
chain. For casual `TCA` based database records, the `FormDataGroup` `TcaDatabaseRecord` is
used to define relevant data provider within
`$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['formDataGroup']['tcaDatabaseRecord']`. Additional
data provider can be added at specific positions using the `depends` and `before` keywords
relative to other providers.


.. index:: PHP-API, Backend, TCA
