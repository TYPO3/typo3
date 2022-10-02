.. include:: /Includes.rst.txt

.. _breaking-97126:

========================================================
Breaking: #97126 - Remove TCEforms array key in FlexForm
========================================================

See :issue:`97126`

Description
===========

As a result of :doc:`#97126 <../12.0/Deprecation-97126-TCEformsRemovedInFlexForm>`
the `TCEforms` key has been removed from the FlexForm array. Code, that deals
with the FlexForm result array directly and accesses this key, may break.

Impact
======

In rare cases, where custom extensions deal with the parsed FlexForm array
structure directly and relying on the presence of the :php:`TCEforms` key, an
undefined array key warning may appear and the logic won't work any longer.

Affected Installations
======================

All installations, which deal with parsed FlexForm arrays directly and using the
:php:`TCEforms` key.

This **may** happen when:

*  Using :php:`FlexFormTools->parseDataStructureByIdentifier()` directly
*  Using or extending a :php:`FormDataProvider` and accessing the :php:`$result['processedTca']` array

.. note::

    Since a long time, the :php:`TCEforms` key has already been removed in the
    :php:`TcaFlexPrepare` FormDataProvider. It is advised to set this provider
    as a dependency, when relying on prepared FlexForm TCA, in custom providers.

Migration
=========

Search your PHP code for the presence of the string `TCEforms` inside of arrays
and remove it. In case you need to support two TYPO3 versions simultaneously,
check if the key exists or not and adjust your array access accordingly.

Real world example from EXT:news:

Before:

..  code-block:: php

    if (!empty($categoryRestriction) && isset($structure['sheets']['sDEF']['ROOT']['el']['settings.categories'])) {
        $structure['sheets']['sDEF']['ROOT']['el']['settings.categories']['TCEforms']['config']['foreign_table_where'] = $categoryRestriction . $structure['sheets']['sDEF']['ROOT']['el']['settings.categories']['TCEforms']['config']['foreign_table_where'];
    }

After:

..  code-block:: php

    if (!empty($categoryRestriction) && isset($structure['sheets']['sDEF']['ROOT']['el']['settings.categories'])) {
        $structure['sheets']['sDEF']['ROOT']['el']['settings.categories']['config']['foreign_table_where'] = $categoryRestriction . $structure['sheets']['sDEF']['ROOT']['el']['settings.categories']['config']['foreign_table_where'];
    }

Supporting both TYPO3 v11 and v12+:

..  code-block:: php

    if (!empty($categoryRestriction) && isset($structure['sheets']['sDEF']['ROOT']['el']['settings.categories'])) {
        if (isset($structure['sheets']['sDEF']['ROOT']['el']['settings.categories']['TCEforms'])) {
            $structure['sheets']['sDEF']['ROOT']['el']['settings.categories']['TCEforms']['config']['foreign_table_where'] = $categoryRestriction . $structure['sheets']['sDEF']['ROOT']['el']['settings.categories']['TCEforms']['config']['foreign_table_where'];
        } else {
            $structure['sheets']['sDEF']['ROOT']['el']['settings.categories']['config']['foreign_table_where'] = $categoryRestriction . $structure['sheets']['sDEF']['ROOT']['el']['settings.categories']['config']['foreign_table_where'];
        }
    }

.. index:: FlexForm, TCA, NotScanned, ext:core
