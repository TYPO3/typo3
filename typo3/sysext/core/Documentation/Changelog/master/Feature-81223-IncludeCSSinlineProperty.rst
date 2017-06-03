.. include:: ../../Includes.txt

=================================================
Feature: #81223 - includeCSS.inline property
=================================================

See :issue:`81223`

Description
===========

A property :ts:`.inline` has been added to :ts:`page.includeCSS`. 
If :ts:`.inline` is set, the content of the css-file is inlined using <style>-tags.


Impact
======

Example: 

.. code-block:: typoscript
	page.includeCSS {
		inline = EXT:test/Resources/Public/Css/inline.css
		inline {
			inline = 1
			forceOnTop = 1
			media = all
		}
		other = EXT:test/Resources/Public/Css/other.css
	}


Some notes on the implementation:

External files are not inlined.
The inline-css is compressed if config.compressCss is set.
Most other properties (.allWrap, .disableCompression, .forceOnTop, .if, .media, .title) work even if '.inline' is set. 
If '.import' and '.inline' are both set , the file is loaded via @import.

.. index:: Frontend, TypoScript
