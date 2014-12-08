====================================================================
Breaking: #63687 - Web=>Functions=>Wizards moved to legacy extension
====================================================================

Description
===========

Within the "Web" => "Functions" module there is a nested layer called "Wizards" where "Sort pages" and
"Bulk-create new pages" resided until TYPO3 CMS 7.1. These are now moved directly underneath "Web" => "Functions",
so the "Wizards" module function becomes obsolete, and with it the whole extension called "func_wizards" that provided
this nested layer. The module function "Wizards" has been moved to the legacy extension "compatibility6". The
extension "func_wizards" has been completely removed from the core.

Impact
======

The existing "Functions" provided by the TYPO3 CMS Core are now directly dependant and hooked into "Web" => "Functions".
Any extensions using "Web" => "Functions" => "Wizards" will not show up anymore.


Affected installations
======================

TYPO3 CMS 7 installations need compatibility6 extension loaded if old extensions are still hook into
"Web" => "Functions" => "Wizards".

Migration
=========

Any extension hooking into "Web" => "Functions" => "Wizards" need to be adapted. In their ext_tables.php the
"insertModuleFunciton" call does not need to have a 5th parameter given. The compatibility6 extension is then not
needed anymore.
