
.. include:: ../../Includes.txt

================================================
Breaking: #63310 - Web=>Functions=>Wizards moved
================================================

See :issue:`63310`

Description
===========

The two module functions "Create Pages" and "Sort Pages" located within the extensions "wizard_crpages" and
"wizard_sortpages" were located under Web => Functions => Wizards. This structure is now simplified as the wizards
are moved one level up in Web => Functions.

Impact
======

Any options set via TSconfig for these wizards for the module menu have changed. The existing options don't work
anymore.

Affected installations
======================

Any installation using TSconfig like "web_func.menu.wiz" needs to be adapted.

Migration
=========

The respective options "web_func.menu.wiz" have been moved to "web_func.menu.functions".
