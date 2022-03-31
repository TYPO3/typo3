.. include:: /Includes.rst.txt

====================================================================================
Important: #94697 - Quote database identifiers when used instead of globally upfront
====================================================================================

See :issue:`94697`

Description
===========

When using :php:`TCA` keys that contain SQL fragments like :php:`foreign_table_where`,
:php:`MM_table_where` and :php:`search.andWhere`, it is important to use a special syntax
for SQL field names to stay DBAL compatible.

See :doc:`#81751 <../8.7.x/Important-81751-DbalCompatibleQuotingInTca>` for details.
It boils down to: Use :sql:`{#colPos}=0` instead of :sql:`colPos=0` to stay DBAL
compatible. The core then takes care field names are properly quoted for the
specific DBMS that is used.

This quoting preparation has been performed during TCA cache warmup until now.
This had the main disadvantage that this early boostrap step already needs a
working database connection. The core however plans to introduce features to
allow cache warmups as separate step in CI/CD systems. Those usually don't have
the target database available, and in general it's ugly that an early warmup
needs a database connection.

Therefore, the field name quoting of SQL fragments is now no longer performed
during TCA cache warmup, but instead directly done in places where those
TCA keys are used to create the final queries.

Since extensions might rely on identifiers within these settings being properly
quoted, a feature flag called `runtimeDbQuotingOfTcaConfiguration` is introduced
to revert to the old behaviour with TYPO3 v11.

Extension authors who access these TCA properties, which is quite unlikely,
can use the feature flag to support both variants to ensure compatibility
between TYPO3 v10, v11 and TYPO3 v12.

Starting with TYPO3 v12.0, this feature flag will be enabled at all times.

.. index:: Database, TCA, ext:core
