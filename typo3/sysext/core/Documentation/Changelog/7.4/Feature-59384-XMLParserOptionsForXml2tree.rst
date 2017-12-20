
.. include:: ../../Includes.txt

===================================================
Feature: #59384 - XML parser options for xml2tree()
===================================================

See :issue:`59384`

Description
===========

`GeneralUtility::xml2tree()` gets an optional parameter: an array that can hold options for the parser.
Those will simply be passed through to the PHP-function xml_parser_set_option().

.. code-block:: php

	GeneralUtility::xml2tree($xmlData, 999, array(XML_OPTION_SKIP_WHITE => 1));


Impact
======

It's just an optional parameter. If you don't specify it, simply no additional initialisation of the XML-parser will be done.


.. index:: PHP-API, Backend
