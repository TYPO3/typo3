
.. include:: ../../Includes.txt

=======================================================
Breaking: #75708 - Always store <p> tags in DB from RTE
=======================================================

See :issue:`75708`

Description
===========

When transforming HTML data from the Rich Text Editor to the database, the RteHtmlParser removed <p> tags from
lines when there were no attributes for the <p> tags, otherwise they were kept as <p> tags with
their attributes.

The transformation now always keeps <p> tags within the content in order to minimize the transformation overhead
between the RTE and the database.


Impact
======

Every time an RTE field is edited, the <p> tags are now stored inside the database when saving the content.


Affected Installations
======================

All installations using RTE fields or RteHtmlParser transformations.


Migration
=========

An upgrade wizard inside the Install Tool (coming until 8.1) will make sure that any database RTE field is converted.

.. index:: Database, Backend, RTE