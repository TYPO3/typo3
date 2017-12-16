
.. include:: ../../Includes.txt

===============================================
Feature: #63207 - Split buttons into two groups
===============================================

See :issue:`63207`

Description
===========

The action buttons in Web>List for pages and content elements have been split into two groups to organize
the actions as primary and secondary actions. Primary actions are common RUD actions:
- Show
- Edit
- Hide
- Delete
- Move up/down

Secondary actions keep any other action. If "Extended view" is disabled, the primary actions are now
still displayed, the secondary action are collapsed but can be expanded by clicking the expand trigger.


Impact
======

Existing hooks will work like before. If an action is added to one of the two sections, the icon
on rootlevel must be reset, please see Migration_.


Migration
=========

.. code-block:: php

	unset($cells['edit']);
	$cells['primary']['edit'] = '<a class="btn btn-default"><span class="t3-icon fa fa-trash"></span></a>';
	$cells['secondary']['edit'] = '<a class="btn btn-default"><span class="t3-icon fa fa-trash"></span></a>';`

Icons, that are not added into a section, will be sorted into the secondary group.
