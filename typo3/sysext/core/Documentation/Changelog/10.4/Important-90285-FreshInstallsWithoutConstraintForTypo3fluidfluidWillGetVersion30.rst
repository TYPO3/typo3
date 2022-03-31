.. include:: /Includes.rst.txt

================================================================================================
Important: #90285 - Fresh installs without constraint for typo3fluid/fluid will get version 3.0+
================================================================================================

See :issue:`90285`

Description
===========

Projects which have no dependencies that add a constraint on the maximum allowed version of Fluid
will in the future download and install ``typo3fluid/fluid:^3``.

The TYPO3 core is fully compatible with both major versions of Fluid and lets you choose between
version ``2.6+`` or ``3.0+`` by constraining your project dependencies. However, some projects based
on TYPO3 may contain Fluid templates or dependencies which are not compatible with Fluid 3.0, yet
neglect to declare a maximum version constraint for Fluid - since until the release of version 3.0,
the only/highest major version was 2.6 and ``composer install`` would therefore always select version
``^2.6`` as it was the only option.

If your project has no maximum version constraint and contains Fluid templates which are incompatible
with version ``3.0+`` you will therefore need to take one of the following actions:

* Either declare a maximum version constraint for ``typo3fluid/fluid:^2`` in the root project
  ``composer.json`` or any dependency of the project that you control, and perform ``composer update``.
* Or execute ``composer req typo3fluid/fluid:^2`` in the project directory to make the project itself
  declare the maximum version constraint.

.. index:: Fluid, ext:fluid
