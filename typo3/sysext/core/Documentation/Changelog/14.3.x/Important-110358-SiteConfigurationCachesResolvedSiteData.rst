..  include:: /Includes.rst.txt

..  _important-110358-1785732863:

=================================================================
Important: #110358 - Site configuration caches resolved site data
=================================================================

See :issue:`110358`

Description
===========

TYPO3 now caches all data needed to create :php:`Site` entity objects in the
"core" cache: the site configuration enriched with route enhancers provided
by site sets, the composed site settings, information about invalid sets, as
well as the contents of the optional per-site files :file:`csp.yaml`,
:file:`setup.typoscript`, :file:`constants.typoscript` and
:file:`page.tsconfig`. Previously, this data was recomputed and re-read from
the file system on every request.

The :php:`Site` entity objects themselves are still created at runtime once
per request: Expressions of configured base variants (:yaml:`baseVariants`)
continue to be evaluated per request and environment as before.

Any change performed via the TYPO3 backend (:guilabel:`Site Management`
modules) or via the :php:`SiteWriter` API invalidates this cache
automatically.

However, changes applied directly to the files :file:`csp.yaml`,
:file:`setup.typoscript`, :file:`constants.typoscript` or
:file:`page.tsconfig` within a site configuration folder (for example
:file:`config/sites/<identifier>/`) now require flushing all caches to
become active - as was already the case for :file:`config.yaml` and
:file:`settings.yaml` before.

..  index:: PHP-API, YAML, ext:core, NotScanned
