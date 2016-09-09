
.. include:: ../../Includes.txt

=========================================
Breaking: #63846 - FormEngine refactoring
=========================================

See :issue:`63846`

Description
===========

FormEngine is the core code structure that renders a record view in the backend. Basically everything
that is displayed if elements from page or list module are edited is done by this code.

The main implementation was done thirteen years ago and was never touched on a deep code structure level
until now. The according patches were huge and move the whole code to a new level. Stuff like that can
not be done without impact on extensions that use this code.


Impact
======

TCA changes
-----------

* Keys `_PADDING`, `_VALIGN` and `DISTANCE` of `TCA['aTable']['columns']['aField']['config']['wizards']`
  have been removed and have no effect anymore.

* Key `TCA['aTable']['ctrl']['mainPalette']` has been dropped and has no effect anymore.

TSconfig changes
----------------

* Key `mod.web_layout.tt_content.fieldOrder` has been dropped and has no effect anymore.

* Key `TCEFORM.aTable.aField.linkTitleToSelf` has been dropped and has no effect anymore.


Code level
----------

Methods and properties from FormEngine are not available anymore. Classes like `InlineElement` are gone.
New structures like a factory for elements and container have been introduced.
While not too many extensions in the wild hook or code with FormEngine, those that do will probably throw
fatal errors after upgrade. The hook `getSingleFieldClass` has been removed altogether.

Changed user functions and hooks
--------------------------------

* TCA: If format of type `none` is set to `user`, the configured userFunc no longer gets an instance of `FormEngine`
  as parent object, but an instance of `NoneElement`.

* TCA: Wizards configured as `userFunc` now receive a dummy `FormEngine` object with empty properties instead
  of the real instance.

* Hooks no longer get the key `form_type`. Use `type` instead.

* Hook `getSingleFieldClass` has been dropped and no longer called.

Breaking interface changes
--------------------------

* The type hint to `FormEngine` as `$pObj` has been removed on the `DatabaseFileIconsHookInterface`.
  This hook is no longer given an instance of `FormEngine`.

* Method `init()` of `InlineElementHookInterface` has been removed. Classes that implement this interface will
  no longer get `init()` called.


Affected installations
======================

For most instances, the overall impact is rather low or they are not affected at all. Some very
rarely used TCA and TSconfig options have been dropped, those will do no harm. Instances are usually only affected
if loaded extensions do fancy stuff with FormEngine with hooks or other related code.

TYPO3 CMS 7 installations with extensions using or hooking into FormEngine and its related classes are
likely to break. TCA elements of type user may break. Instances using these parts will quickly show
fatal errors at testing. It may help to search for `FormEngine` or `t3lib_tceForms` below the `typo3conf/ext`
directory to find affected instances.


Migration
=========

Adapt the extension code. The majority of methods were for internal core usage only, but still public. Please
use the existing API to solve needs on FormEngine.
