
.. include:: /Includes.rst.txt

=====================================================
Feature: #20555 - Strip empty HTML tags in HtmlParser
=====================================================

See :issue:`20555`

Description
===========

A new functionality is introduced in the HtmlParser that allows the stripping of empty HTML tags.

It can be used in the Frontend by using the :ref:`HTMLparser <t3tsref:htmlparser>` TypoScript
configuration of :ref:`stdWrap <t3tsref:stdwrap-htmlparser>`:

.. code-block:: typoscript

	stdWrap {

		# If this is set all empty tags are stripped, unless a list of tags is provided below.
		HTMLparser.stripEmptyTags = 1

		# This setting can be used to filter the tags that should be stripped if they are empty.
		HTMLparser.stripEmptyTags.tags = h2, h3
	}

It is also possible to use it in the
:ref:`HTMLparser_rte or HTMLparser_db <t3coreapi:transformations-tsconfig-processing-htmlparser>`
in Page TSconfig:

.. code-block:: typoscript

	# For rtehtmlarea we need to use the entry parser because otherwise the p tags will
	# be converted to linebreaks during the RTE transformation.
	RTE.default.proc.entryHTMLparser_db {
		stripEmptyTags = 1
		stripEmptyTags.tags = p

		# Since rtehtmlarea adds non breaking spaces in empty <p> tags we need to
		# tell the parser that &nbsp; should be treated as an empty string:
		stripEmptyTags.treatNonBreakingSpaceAsEmpty = 1
	}

.. tip::

	Please note that the HTMLparser will strip all unknown tags by default. If you **only** want
	to strip empty tags, you need to set `keepNonMatchedTags` to TRUE or configure the allowed tags:

.. code-block:: typoscript

	stdWrap {
		HTMLparser.keepNonMatchedTags = 1
		HTMLparser.stripEmptyTags = 1
		HTMLparser.stripEmptyTags.tags = h2, h3
	}


Impact
======

If the configuration is not set, the HtmlParser behaves like before so there is no
impact to existing systems (unless they already have used the stripEmptyTags setting
for whatever reason).


.. index:: PHP-API, RTE, TypoScript, TSConfig, Backend, Frontend
