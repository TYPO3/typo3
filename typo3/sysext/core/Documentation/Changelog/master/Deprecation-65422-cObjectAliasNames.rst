============================================================
Deprecation: #65422 - Alias cObjects COBJ_ARRAY and CASEFUNC
============================================================

Description
===========

The cObject aliases COBJ_ARRAY (alias for COA) and CASEFUNC (alias for CASE) are moved to the compatibility6 extension.
The use of these aliases are marked for deprecation.


Impact
======

Any usage of TypoScript using COBJ_ARRAY and CASEFUNC will not work anymore unless the compatibility6 extension is
installed.


Affected installations
======================

All installations with TypoScript in COBJ_ARRAY and CASEFUNC.


Migration
=========

Use COA instead of COBJ_ARRAY and CASE instead of CASEFUNC in all TypoScript code.
