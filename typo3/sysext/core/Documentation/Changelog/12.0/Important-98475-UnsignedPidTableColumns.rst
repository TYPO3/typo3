.. include:: /Includes.rst.txt

.. _important-98475-1664482965:

================================================
Important: #98475 - Unsigned "pid" table columns
================================================

See :issue:`98475`

Description
===========

When upgrading to TYPO3 core v12, the install tool database analyzer will change
the column field :sql:`pid` of various tables to :sql:`UNSIGNED`: They no longer
accept negative values.

Negative pid's were needed until core v10 in combination with workspaces, the core
nowadays only inserts rows with positive integers or 0 (zero) as pid value. An
upgrade wizard took care of updating affected rows when upgrading to v10, current
databases shouldn't contain such rows anymore.


.. index:: Database, ext:core
