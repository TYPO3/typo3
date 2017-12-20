
.. include:: ../../Includes.txt

===========================================================
Feature: #59712 - Additional params for HTMLparser userFunc
===========================================================

See :issue:`59712`

Description
===========

It is now possible to supply additional parameters to a userFunc of the HTMLparser:

::

	myobj = TEXT
	myobj.value = <a href="/" class="myclass">MyText</a>
	myobj.HTMLparser.tags.a.fixAttrib.class {
		userFunc = Tx\MyExt\Myclass->htmlUserFunc
		userFunc.myparam = test1
	}

By default only the value of the processed attribute is passed to the userFunc
as the first parameter:

::

	function htmlUserFunc($attributeValue, HtmlParser $htmlParser) {
		// $attributeValue is set to the current attribute value "myclass"
	}

When additional options are provided as described above, these options will be
passed in the first function parameter as an array. The attribute value is passed
in the array with the ``attributeValue`` array key.

::

	function htmlUserFunc(array $params, HtmlParser $htmlParser) {
		// $params['attributeValue'] contains the current attribute value "myclass".
		// $params['myparam'] is set to "test" in the current example.
	}


Impact
======

If additional parameters are provided to the HTMLparser userFunc setting the first parameter
passed to the called function changes from a string with the attribute value to an array
containing the attributeValue key and all additional settings.

This has an impact to all installations where additional parameters are used in the userFunc
setting of the HTMLparser.


.. index:: PHP-API, RTE, TypoScript, Frontend
