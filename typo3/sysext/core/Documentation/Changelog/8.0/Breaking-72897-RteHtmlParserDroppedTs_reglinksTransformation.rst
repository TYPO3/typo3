
.. include:: /Includes.rst.txt

====================================================================
Breaking: #72897 - RteHtmlParser: Dropped ts_reglinks transformation
====================================================================

See :issue:`72897`

Description
===========

The RTE transformation mode `ts_reglinks` which transforms relative links to absolute links when using the Rich Text Editor
has been removed, along with the according PHP method `RteHtmlParser->TS_reglinks()`.


Impact
======

Using the transformation mode, set via TCA or TSconfig (`RTE.default.proc.mode = ts_reglinks`) will have no effect anymore.

Calling the PHP method will result in a PHP fatal error.


Affected Installations
======================

Any installation using obsolete transformation mode or special RTE transformations.

.. index:: TSConfig, Backend, RTE
