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
