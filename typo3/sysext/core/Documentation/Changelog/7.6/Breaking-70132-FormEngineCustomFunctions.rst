
.. include:: ../../Includes.txt

==============================================
Breaking: #70132 - FormEngine custom functions
==============================================

See :issue:`70132`

Description
===========

Due to the refactoring of the backend FormEngine code the "low end" extension API to manipulate data has
changed. Affected are especially the `type=user` `TCA` element, any `userFunc` configured in
`TCA` as well as the `itemsProcFunc` to manipulate single items in select, group and other types.

In general data given to those custom functions has changed and extensions that rely on this data may
fail. For instance, if a `itemsProcFunc` was defined for a field within a flex form, the `row`
array argument contained the full parent database row in the past. This is no longer the case and
the parent database row is now transferred as `flexParentDatabaseRow`. In other cases data previously
handed over to custom functions may no longer be available at all.


Impact
======

Custom functions receive less or different options than before and may stop working.


Affected Installations
======================

Extensions using the `TCA` with `type=user` fields, extensions using `TCA` with `userFunc` and
extensions  using `itemsProcFunc`.


Migration
=========

Developers using this API have to debug the data given to custom functions and adapt accordingly.

If the data given is not sufficient it is possible to register own element classes with the
`NodeFactory` or to manipulate data by adding a custom `FormDataProvider`. While the current
API will be mostly stable throughout further TYPO3 CMS 7 LTS patch releases, it may however happen
that the given API and data breaks again with the development of the TYPO3 CMS 8 path to make the
FormEngine code more powerful and reliable in the end.


.. index:: PHP-API, TCA
