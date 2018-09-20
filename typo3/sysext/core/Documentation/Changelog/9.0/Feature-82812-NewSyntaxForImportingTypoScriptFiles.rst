.. include:: ../../Includes.txt

===========================================================
Feature: #82812 - New syntax for importing TypoScript files
===========================================================

See :issue:`82812`

Description
===========

A new syntax for importing external TypoScript files has been introduced, which acts as a preprocessor
before the actual parsing (Condition evaluation) is made.

It's main purpose is ease the use of TypoScript includes and make it easier for integrators and
frontend developers to work with distributed TypoScript files. The syntax is inspired by SASS
imports and works as follows:

.. code-block:: typoscript

	# Import a single file
	@import 'EXT:myproject/Configuration/TypoScript/randomfile.typoscript'
	
	# Import multiple files in a single directory, sorted by file name
	@import 'EXT:myproject/Configuration/TypoScript/*.typoscript'
	
	# Import all files in a directory
	@import 'EXT:myproject/Configuration/TypoScript/'
	
	# It's possible to omit the file ending, then "typoscript" is automatically added
	@import 'EXT:myproject/Configuration/TypoScript/'

The main benefits of `@import` over using `<INCLUDE_TYPOSCRIPT>` are:
- Less error-prone when adding statements to TypoScript
- Easier to read what should be included (files, folders - instead of `FILE:` and `DIR:` syntax)
- @import is more speaking than a pseudo-XML syntax

The following rules apply:
- If multiple files are found, the file name is important in which order the files (sorted
alphabetically by filename)
- Recursive inclusion of files (@import within @import is possible)
- It is not possible to use a condition as possible with <INCLUDE_TYPOSCRIPT condition=""> as its
sole purpose is to include files, which happens before the actual real condition matching happens,
and the INCLUDE_TYPOSCRIPT condition syntax is a conceptual mistake, and should be avoided.
- Both `<INCLUDE_TYPOSCRIPT>` and `@import` can work side-by-side in the same project
- If a directory is included, it will not include files recursively
- Quoting of the filename is necessary, both double quotes (") and single tickst (') can be used

The syntax is designed to stay, and @import is not intended to be extended with more logic in the
future. However, it may be possible that TypoScript will get more preparsing logic via the @ annotation.

Under the hood, Symfony Finder is used to detect files. This makes globbing (* syntax) possible.

.. index:: TypoScript, Frontend
