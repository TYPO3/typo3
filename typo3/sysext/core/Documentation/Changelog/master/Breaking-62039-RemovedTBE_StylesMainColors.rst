=================================================
Breaking: #62039 - Removed TBE_STYLES[mainColors]
=================================================

Description
===========

The values within $TBE_STYLES[mainColors] are redundant and can be completely defined via CSS nowadays. The
corresponding PHP leftovers are removed from the core and have no effect anymore.


Impact
======

Setting the variables within $TBE_STYLES[mainColors] and using the $doc->bgColor* and $doc->hoverColor properties
of DocumentTemplate have no effect anymore.


Affected installations
======================

Any installation using an extension that is overriding skin info via $TBE_STYLES[mainColors].


Migration
=========

Use CSS directly to modify the appearance of the Backend.
