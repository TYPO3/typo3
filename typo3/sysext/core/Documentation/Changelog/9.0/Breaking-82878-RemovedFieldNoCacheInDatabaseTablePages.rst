.. include:: /Includes.rst.txt

=====================================================================
Breaking: #82878 - Removed field "no_cache" in database table "pages"
=====================================================================

See :issue:`82878`

Description
===========

The database field :sql:`pages.no_cache` has been removed from TYPO3 Core. This option allowed editors
to completely disable all frontend caching functionality of a specific page.


Impact
======

Having this option previously set on a specific page will now use caching when rendering this page.


Affected Installations
======================

Existing installations having this option set in their database.

This can easily be checked via a SQL query: :sql:`SELECT uid, pid, title, FROM pages WHERE deleted=0
AND pid>=0 AND no_cache=1;`.


Migration
=========

The "no cache" option which should be avoided or otherwise used carefully by integrators via
TypoScript through :typoscript:`config.no_cache = 1` in conjunction with a condition on a per-page basis.

However, it is better to set a very low cache timeout, or investigate why caching is configured
wrongly in an extension or plugin.

Also, use the following SQL query to quickly check if your installation is even using this option at all.
If not, it is not necessary to migrate anything:

.. code-block:: sql

   SELECT uid,title FROM pages WHERE no_cache = 1


.. index:: Database, Frontend, NotScanned
