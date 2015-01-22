==============================================================
Deprecation: #25112 - Deprecate TypoScript property "andWhere"
==============================================================

Description
===========

The select-property "andWhere" is deprecated.

Impact
======

A deprecation message is logged for every usage of the property.


Affected installations
======================

All installations or extensions using the select-property "andWhere".


Migration
=========

Use the properties "where" or "markers" instead.

.. code-block:: typoscript

	page.30 = CONTENT
	page.30 {
		table = tt_content
		select {
			pidInList = this
			orderBy = sorting
			where {
				dataWrap = sorting>{field:sorting}
			}
		}
	}
	page.60 = CONTENT
	page.60 {
		table = tt_content
		select {
			pidInList = 73
			where = header != ###whatever###
			orderBy = ###sortfield###
			markers {
				whatever.data = GP:first
				sortfield.value = sor
				sortfield.wrap = |ting
			}
		}
	}
