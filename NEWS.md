TYPO3 CMS 6.2 - WHAT'S NEW
==========================

TYPO3 is an open source PHP based web content management system released
under the GNU GPL. TYPO3 is copyright (c) 1999-2013 by Kasper Skaarhoj.

This document provides information about what is new in the 6.2 release
of TYPO3. An up-to-date version of this document also containing links to
further in depth information can be found here:

http://wiki.typo3.org/TYPO3_CMS_6.2

System requirement changes
--------------------------

Minimum PHP version requirement raised to PHP 5.3.7. Please upgrade PHP first,
if you plan to update from an older TYPO3 installation to 6.2!

PHP 5.4 or later is recommended for improved performance.

Consult INSTALL.md for complete system requirements.

Changes and Improvements
------------------------

### Removed and moved components

* Removed directory t3lib and PHP constant PATH_t3lib
* Moved ExtJS- & JavaScript files from t3lib to typo3


### General

* SpriteGenerator now supports high density sprites

* New default value for cookieHttpOnly setting

The session cookies "fe_typo_user" and "be_typo_user" now have set the
HttpOnly attribute by default.  This will make it harder to steal the cookie
by XSS attacks.

* Frontend Cookie now only set when needed, not set by default anymore

The cookie "fe_typo_user" set in the frontend by each request, is now only
being set if the session data is used via $TSFE->fe_user->setKey('ses')
so it can be used for shopping baskets for non-logged-in users
out-of-the-box without hacking the default behaviour of setting the
cookie.
The previous behaviour always set the "fe_typo_user" cookie, but changed
the session ID on each request, until it was fixated by a user login.
The superfluous option "dontSetCookie" is now ineffective as the cookie
is not set anymore by default.


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

* Ajax API addition

New API has been added to register an Ajax handler for the backend.
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::registerAjaxHandler('TxMyExt::process', 'Vendor\\Ext\\AjaxHandler->process');

Along with that, URLs to all registered handlers will be
published to JavaScript inline settings and can be looked up
by providing the Ajax ID:

var ajaxUrl = TYPO3.settings.ajaxUrls['TxMyExt::process'];

Registering an Ajax script the "old" way by just adding it to TYPO3_CONF_VARS has been deprecated,
but no deprecation log is been written and the handler still work in a backwards compatible way.


#### CSS Styled Content

* Removed deprecated DB fields

There are 5 DB fields in tt_content that haven't been used in TYPO3 since
version 4.0, and were disabled by default when using CSS Styled Content.

The DB fields are
  - text_align
  - text_face
  - text_size
  - text_color
  - text_properties

The fields have been removed from the code and are removed by the
DB Compare after upgrading.


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

* Re-ordered menu items in cache toolbar

With grouped caching (see above) items in the menu bar of the TYPO3 Backend
have been re-arranged and renamed to reflect the impact of the icons.

 - "Flush frontend caches" clears all caches marked with the group "pages".
 This includes clearing the previous "cache_hash", "cache_pages" and
 "cache_pagesection", which affects links, TypoScript, fully-cached pages and
 cached page elements.

 - "Flush general caches" clears all caches inside the groups "all" and "pages"
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

* Typoscript compatibility

For new installations TYPO3 CMS 6.2 now uses the new format of tt_content and page
records. If you're upgrading from a previous version the FrontendContentAdapter is
activated, which converts those records back to the old format for you on the fly.

If you manage to change your Typoscript to use the new format you should consider
deactivating the Adapter with the Install Tool option [FE][activateContentAdapter]
as the Adapter really slows down the system.

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

* Change in Hook TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['pageLoadedFromCache']

Previously $row['cache_data'] was a serialized array. To avoid double serializing and unserializing,
from now on $row['cache_data'] is just reconstituted as array when fetching from cache.

* No backward compatibility for classes inheriting localPageTree or localFolderTree

Backwards compatibility for extensions that inherit from one of the classes
localPageTree, localFolderTree, rtefoldertree, rtepagetree, tbe_foldertree or tbe_pagetree
is dropped.


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

* Date view helper uses configured default format

The fluid date view helper now uses $GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy']
as fallback format instead of hardcoded Y-m-d if no explicit format is given as
argument. This may change the output of dates from Y-m-d to d-m-y.


### System categories

* Activated by default

Pages and content elements are now categorizable by default.

* New menu types

The "Special Menus" content element type now offers the possibility to display
a list of categorized pages or content elements.

* Category fields are excluded by default

Category fields are created as exclude field (TCA) by default.
If you're upgrading don't forget to add the permission for users.
