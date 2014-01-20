TYPO3 CMS 6.2 - WHAT'S NEW
==========================

TYPO3 is an open source PHP based web content management system released
under the GNU GPL. TYPO3 is copyright (c) 1999-2013 by Kasper Skaarhoj.

This document provides information about what is new in the 6.2 release
of TYPO3. An up-to-date version of this document also containing links to
further in depth information can be found here:

http://wiki.typo3.org/TYPO3_6.2

System requirement changes
--------------------------

Minimum PHP version requirement raised to PHP 5.3.7. Please upgrade PHP first,
if you plan to update from an older TYPO3 installation to 6.2!

PHP 5.4 or later is recommended for improved performance.

Consult INSTALL.md for complete system requirements.

Changes and Improvements
------------------------

### Removed and moved components

* Removed PHP constant PATH_t3lib
* Moved ExtJS- & JavaScript files from t3lib to typo3

### General

* SpriteGenerator now supports high density sprites

* New default value for cookieHttpOnly setting

The session cookies "fe_typo_user" and "be_typo_user" now have set the
HttpOnly attribute by default.  This will make it harder to steal the cookie
by XSS attacks.

### Logging

* Logging API PSR-3 compliance

The logger of the Logging API now complies with the PSR-3 standard of the
PHP Framework Interop Group: http://www.php-fig.org/psr/3/

### Backend

* Categorization API improvements

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::makeCategorizable() can now
be used multiple times on the same table to add more than one category field.
The options array (the fourth parameter) now can contain a 'label' to set a
custom label for each category field.


#### Caching

* Caching behaviour by newly introduced grouping parameter

Most caches used in TYPO3 CMS are now based on the FLOW caching framework. The
caching framework is now used for class loading, Extbase-internals, most page-
related caches, and for the configuration cache. Some caches are system-related
caches that only need to be flushed and rebuilt when the core is updated or
an extension is (un-)installed. **The functionality of "Clear all caches" thus
does not include the system-related caches anymore** - these can be cleared by
"Clear configuration cache" or DataHandler->clear_cacheCmd('system') if the
user has the according permissions. Each cache can be configured to be in one or
multiple groups in its configuration parameters. Custom groups can be defined
and cleared manually.
All extension maintainers are encouraged to switch their own caching mechanisms
to the caching framework and use the API instead of using hooks within TCEmain,
as the clearing via TCEmain would only be triggered if going through
the TCEmain calls (not via Extbase e.g.).

* Re-ordered backend menu items
With grouped caching (see above) items in the menu bar of the TYPO3 Backend
have been re-arranged and renamed to reflect the impact of the icons.

 - "Flush frontend caches" clears all caches marked with the group "pages".
 This includes clearing the previous "cache_hash", "cache_pages" and
 "cache_pagesection", which affects links, TypoScript, fully-cached pages and
 cached page elements.

 - "Flush all caches" clears all caches inside the groups "all" and "pages"
 as well as additional database tables registered via hooks in TCEmain. However
 the system-related caches are NOT flushed.

 - "Flush system caches" clears all system-related caches, which is the class
 loading cache, configuration cache (previously known as temp_CACHED_* files)
 and some other extbase-related class caches. The symbol is now disabled
 by default, even for admins, and can be enabled by setting the userTSconfig
 option "options.clearCache.system=1", and is also always enabled using
 the Application Context / TYPO3_CONTEXT Environment Option "Development".
 Additionally, clearing system caches can be done via the Install Tool, they
 are automatically flushed when an extension is being activated/uninstalled.

All hooks within TCEmain still work as expected. However, the use of
clear_cacheCmd with the parameter "temp_cached" is discouraged with
the introduction of the group "system".


### Frontend

* Minor API change in \TYPO3\CMS\Frontend\ContentObjectRenderer->getTreeList()

getTreeList() got some cleanup and slightly changed its return result. Former
versions sometimes returned a trailing comma which is not the case anymore.

Before:
getTreeList(42, 4) // get pids for pageId 42, 4 levels deep
result: '0, 22, 11, 4,'

After:
getTreeList(42, 4)
result: '0, 22, 11, 4'

* Removal of HTML Tidy and its options

The possibility to use the external tool HTML Tidy that is used to clean up
incomplete HTML when a frontend page is rendered was removed from the TYPO3
Core. Its functionality is now provided by the TER extension "Tidy".
The extension works with the same options as before.


### Administration / Customization

* Content-length header (TypoScript setting config.enableContentLengthHeader)
  is now enabled by default

### Extbase

* Recursive object validation

Validation of object structures in extbase is now done recursively. If a tree
of objects is created by the new property mapper, not only the top level object
is validated, but all objects.

* Allow empty validation

In order to make a property required you now need to add the NotEmptyValidator
to your property. The return value of validators is now optional.

### Fluid

* Image view helper does not render title tag by default

In previous versions of fluid the image view helper always rendered the
title attribute. If not set, the value of the required alt attribute was set as
title.
This fallback was removed with version 6.2. If not specifically set, title
is not rendered anymore.

Example:
  Fluid Tag
    <f:image src="{file}" alt="Alt-Attribute" />
  will render
    <img src="fileadmin/xxxx.jpg" alt="Alt-Attribute" />
  and not
    <img src="fileadmin/xxxx.jpg" alt="Alt-Attribute" title="Alt-Attribute" />

### System categories

* Activated by default

Pages and content elements are now categorizable by default.

* New menu types

The "Special Menus" content element type now offers the possibility to display
a list of categorized pages or content elements.
