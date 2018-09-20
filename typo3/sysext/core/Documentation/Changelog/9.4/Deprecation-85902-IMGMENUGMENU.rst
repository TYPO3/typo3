.. include:: ../../Includes.txt

===================================
Deprecation: #85902 - IMGMENU/GMENU
===================================

See :issue:`85902`

Description
===========

Rendering a Hierarchical Menu via TypoScript previously allowed various rendering methods, namely textual (`TMENU`),
but also rendering menu items as images (`GMENU`) or as a image map (`IMGMENU`). Both graphical possibilities
have been marked as deprecated, as it is considered bad practice building websites
nowadays - images with a fixed width, and text within images has various drawbacks in terms of accessibility and
responsive renderings.

The following PHP classes have been marked as deprecated:

* :php:`TYPO3\CMS\Frontend\ContentObject\Menu\GraphicalMenuContentObject`
* :php:`TYPO3\CMS\Frontend\ContentObject\Menu\ImageMenuContentObject`

The related TypoScript menu objects `GMENU` and `GMENUITEM` as well as `IMGMENU` and `IMGMENUITEM` have been
marked as deprecated.

On top the following TypoScript options within a MENU item, regarding TMENU have been marked as deprecated:

* imgNamePrefix
* imgNameNotRandom

The following TMENU item properties should not be used anymore.

* RO_chBgColor
* beforeImg
* beforeImgTagParams
* beforeImgLink
* beforeROImg
* RO
* afterImg
* afterImgTagParams
* afterImgLink
* afterROImg

The following item states have been marked as deprecated ("RO" for "rollover" in graphics-related items).

* IFSUBRO
* ACTRO
* ACTIFSUBRO
* CURRO
* CURIFSUBRO
* USRRO
* USERDEF1RO
* USERDEF2RO

The following previously public properties are now marked as internal and trigger a PHP :php:`E_USER_DEPRECATED` error,
partly due to preparations of refactoring the PHP code once GMENU functionality is removed, and partly
due to the highly connected functionality within the PHP classes:

* :php:`TYPO3\CMS\Frontend\ContentObject\Menu\AbstractMenuContentObject->menuNumber`
* :php:`TYPO3\CMS\Frontend\ContentObject\Menu\AbstractMenuContentObject->entryLevel`
* :php:`TYPO3\CMS\Frontend\ContentObject\Menu\AbstractMenuContentObject->spacerIDList`
* :php:`TYPO3\CMS\Frontend\ContentObject\Menu\AbstractMenuContentObject->doktypeExcludeList`
* :php:`TYPO3\CMS\Frontend\ContentObject\Menu\AbstractMenuContentObject->alwaysActivePIDlist`
* :php:`TYPO3\CMS\Frontend\ContentObject\Menu\AbstractMenuContentObject->imgNamePrefix`
* :php:`TYPO3\CMS\Frontend\ContentObject\Menu\AbstractMenuContentObject->imgNameNotRandom`
* :php:`TYPO3\CMS\Frontend\ContentObject\Menu\AbstractMenuContentObject->debug`
* :php:`TYPO3\CMS\Frontend\ContentObject\Menu\AbstractMenuContentObject->GMENU_fixKey`
* :php:`TYPO3\CMS\Frontend\ContentObject\Menu\AbstractMenuContentObject->MP_array`
* :php:`TYPO3\CMS\Frontend\ContentObject\Menu\AbstractMenuContentObject->conf`
* :php:`TYPO3\CMS\Frontend\ContentObject\Menu\AbstractMenuContentObject->mconf`
* [not scanned] :php:`TYPO3\CMS\Frontend\ContentObject\Menu\AbstractMenuContentObject->tmpl`
* :php:`TYPO3\CMS\Frontend\ContentObject\Menu\AbstractMenuContentObject->sys_page`
* [not scanned] :php:`TYPO3\CMS\Frontend\ContentObject\Menu\AbstractMenuContentObject->id`
* :php:`TYPO3\CMS\Frontend\ContentObject\Menu\AbstractMenuContentObject->nextActive`
* :php:`TYPO3\CMS\Frontend\ContentObject\Menu\AbstractMenuContentObject->menuArr`
* :php:`TYPO3\CMS\Frontend\ContentObject\Menu\AbstractMenuContentObject->hash`
* :php:`TYPO3\CMS\Frontend\ContentObject\Menu\AbstractMenuContentObject->result`
* :php:`TYPO3\CMS\Frontend\ContentObject\Menu\AbstractMenuContentObject->rL_uidRegister`
* :php:`TYPO3\CMS\Frontend\ContentObject\Menu\AbstractMenuContentObject->INPfixMD5`
* :php:`TYPO3\CMS\Frontend\ContentObject\Menu\AbstractMenuContentObject->I`
* :php:`TYPO3\CMS\Frontend\ContentObject\Menu\AbstractMenuContentObject->WMresult`
* :php:`TYPO3\CMS\Frontend\ContentObject\Menu\AbstractMenuContentObject->WMfreezePrefix`
* :php:`TYPO3\CMS\Frontend\ContentObject\Menu\AbstractMenuContentObject->WMmenuItems`
* :php:`TYPO3\CMS\Frontend\ContentObject\Menu\AbstractMenuContentObject->WMsubmenuObjSuffixes`
* :php:`TYPO3\CMS\Frontend\ContentObject\Menu\AbstractMenuContentObject->WMextraScript`
* :php:`TYPO3\CMS\Frontend\ContentObject\Menu\AbstractMenuContentObject->WMcObj`
* :php:`TYPO3\CMS\Frontend\ContentObject\Menu\AbstractMenuContentObject->alternativeMenuTempArray`
* :php:`TYPO3\CMS\Frontend\ContentObject\Menu\AbstractMenuContentObject->nameAttribute`

The following methods have changed visibility:

* :php:`TYPO3\CMS\Frontend\ContentObject\Menu\AbstractMenuContentObject->subMenu()`
* :php:`TYPO3\CMS\Frontend\ContentObject\Menu\AbstractMenuContentObject->link()`
* :php:`TYPO3\CMS\Frontend\ContentObject\Menu\AbstractMenuContentObject->procesItemStates()`
* :php:`TYPO3\CMS\Frontend\ContentObject\Menu\AbstractMenuContentObject->changeLinksForAccessRestrictedPages()`
* :php:`TYPO3\CMS\Frontend\ContentObject\Menu\AbstractMenuContentObject->isNext()`
* :php:`TYPO3\CMS\Frontend\ContentObject\Menu\AbstractMenuContentObject->isActive()`
* :php:`TYPO3\CMS\Frontend\ContentObject\Menu\AbstractMenuContentObject->isCurrent()`
* :php:`TYPO3\CMS\Frontend\ContentObject\Menu\AbstractMenuContentObject->isSubMenu()`
* :php:`TYPO3\CMS\Frontend\ContentObject\Menu\AbstractMenuContentObject->isItemState()`
* :php:`TYPO3\CMS\Frontend\ContentObject\Menu\AbstractMenuContentObject->accessKey()`
* :php:`TYPO3\CMS\Frontend\ContentObject\Menu\AbstractMenuContentObject->userProcess()`
* :php:`TYPO3\CMS\Frontend\ContentObject\Menu\AbstractMenuContentObject->setATagParts()`
* :php:`TYPO3\CMS\Frontend\ContentObject\Menu\AbstractMenuContentObject->getPageTitle()`
* :php:`TYPO3\CMS\Frontend\ContentObject\Menu\AbstractMenuContentObject->getMPvar()`
* :php:`TYPO3\CMS\Frontend\ContentObject\Menu\AbstractMenuContentObject->getDoktypeExcludeWhere()`
* :php:`TYPO3\CMS\Frontend\ContentObject\Menu\AbstractMenuContentObject->getBannedUids()`
* :php:`TYPO3\CMS\Frontend\ContentObject\Menu\AbstractMenuContentObject->menuTypoLink()`

* :php:`TYPO3\CMS\Frontend\ContentObject\Menu\GraphicalMenuContentObject->extProc_RO()`
* :php:`TYPO3\CMS\Frontend\ContentObject\Menu\GraphicalMenuContentObject->extProc_init()`
* :php:`TYPO3\CMS\Frontend\ContentObject\Menu\GraphicalMenuContentObject->extProc_beforeLinking()`
* :php:`TYPO3\CMS\Frontend\ContentObject\Menu\GraphicalMenuContentObject->extProc_afterLinking()`
* :php:`TYPO3\CMS\Frontend\ContentObject\Menu\GraphicalMenuContentObject->extProc_beforeAllWrap()`
* :php:`TYPO3\CMS\Frontend\ContentObject\Menu\GraphicalMenuContentObject->extProc_finish()`

* :php:`TYPO3\CMS\Frontend\ContentObject\Menu\TextMenuContentObject->getBeforeAfter()`
* :php:`TYPO3\CMS\Frontend\ContentObject\Menu\TextMenuContentObject->extProc_init()`
* :php:`TYPO3\CMS\Frontend\ContentObject\Menu\TextMenuContentObject->extProc_beforeLinking()`
* :php:`TYPO3\CMS\Frontend\ContentObject\Menu\TextMenuContentObject->extProc_afterLinking()`
* :php:`TYPO3\CMS\Frontend\ContentObject\Menu\TextMenuContentObject->extProc_beforeAllWrap()`
* :php:`TYPO3\CMS\Frontend\ContentObject\Menu\TextMenuContentObject->extProc_finish()`

The following functionality has been marked as deprecated as well:

* :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->setJS('mouseOver')`


Impact
======

Instantiating any of the deprecated PHP classes, or calling any of the PHP methods will trigger a
PHP :php:`E_USER_DEPRECATED` error, as well as setting any of the previously public properties.

Using `GMENU` or `IMGMENU` or any of the TypoScript settings will also raise a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

TYPO3 installations using `GMENU` or `IMGMENU` TypoScript functionality, roll-over functionality within menus
in Frontend, or image-related functionality related to `HMENU`, or extending `HMENU` with their custom menus.


Migration
=========

Migrate to `TMENU` by using "before" and "after" functionality to effectively render images with `GIFBUILDER`.

.. index:: Frontend, TypoScript, PartiallyScanned, ext:frontend
