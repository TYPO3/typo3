.. include:: /Includes.rst.txt

.. _important-98475-1664482965:

================================================
Important: #98475 - Unsigned "pid" table columns
================================================

See :issue:`98475`

Description
===========

When upgrading to TYPO3 v12, the install tool database analyzer will change
the column field :sql:`pid` of various tables to :sql:`UNSIGNED`: They no longer
accept negative values.

Negative pids were needed until TYPO3 v10 in combination with workspaces, the Core
nowadays only inserts rows with positive integers or 0 (zero) as pid value. An
upgrade wizard took care of updating affected rows when upgrading to v10, current
databases shouldn't contain such rows anymore.

.. index:: Database, ext:core
