
.. include:: ../../Includes.txt

======================================================================================================
Breaking: #72837 - RTE transformations: Allow div sections by default and remove font-specific parsing
======================================================================================================

See :issue:`72837`

Description
===========

The TSconfig `RTE.default.proc` options `preserveDIVSections` and `allowedFontColors` for transforming data between the Rich
Text Editor and the database have been removed.

The `preserveDIVSections` option is now built-in by default "always on", and DIV tags are always treated as block elements.

Special handling for `<font>` tags is done via the regular tag processing options like any other tag.


Impact
======

Setting the TSconfig option `RTE.default.proc.preserveDIVSections = 0` or `RTE.default.proc.allowedFontColors` will have no effect anymore.


Affected Installations
======================

Any installation using custom TSconfig configurations for the RTE and using `RTE.default.proc.preserveDIVSections` set to 0 or  `RTE.default.proc.allowedFontColors` to any value.


Migration
=========

If DIV HTML elements should not treated like block elements, the RTE option `RTE.proc.blockElementList` can be manually
customized to not include DIV elements.

If the option `allowedFontColors` is still needed, the existing functionality can be achieved by using the `keepTags` functionality to sort out correct values for a property.

.. index:: TSConfig, Backend, RTE
