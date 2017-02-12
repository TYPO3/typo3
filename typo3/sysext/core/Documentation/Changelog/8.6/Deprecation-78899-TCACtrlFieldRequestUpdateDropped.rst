.. include:: ../../Includes.txt

==========================================================
Deprecation: #78899 - TCA ctrl field requestUpdate dropped
==========================================================

See :issue:`78899`

Description
===========

The :code:`TCA` :code:`ctrl` configuration option :code:`['ctrl']['requestUpdate']` has been dropped.
This option was often used together with :code:`displayCond` fields to re-evaluate display conditions
if referenced fields changed their value. Typically, a "Refresh required" popup is raised to the editor
in those cases, if the editor did not disable that.
The field has been moved and is now located within the :code:`['columns']` section of the single field
as :code:`'onChange' => 'reload'`.


Impact
======

The field is just moved from :code:`ctrl` section to the single field `columns` section. An automatic
TCA migration does that and logs deprecation messages.


Affected Installations
======================

All :code:`TCA` tables that use :code:`requestUpdate` in :code:`ctrl` section.


Migration
=========

Monitor the deprecation log for according messages, remove the :code:`ctrl` field and
add :code:`'onChange' => 'reload'` to fields listed in :code:`requestUpdate` parallel to :code:`label`
and :code:`config` section of the field in question. The option can be added to multiple fields.

.. index:: Backend, PHP-API, TCA