
.. include:: /Includes.rst.txt

=======================================================================
Feature: #66697 - Add uppercamelcase and lowercamelcase to stdWrap.case
=======================================================================

See :issue:`66697`

Description
===========

To make it possible to change a value from underscored to "UpperCamelCase" or "lowerCamelCase" the options
`uppercamelcase` and `lowercamelcase` are added to `stdWrap.case`.

.. code-block:: typoscript

	tt_content = CASE
	tt_content {
		key {
			field = CType
		}

		my_custom_ctype =< lib.userContent
		my_custom_ctype {
			file = EXT:site_base/Resources/Private/Templates/SomeOtherTemplate.html
			settings {
				extraParam = 1
			}
		}

		default =< lib.userContent
		default {
			file = TEXT
			file.field = CType
			file.stdWrap.case = uppercamelcase
			file.wrap = EXT:site_base/Resources/Private/Templates/|.html
		}
	}


.. index:: TypoScript, Frontend
