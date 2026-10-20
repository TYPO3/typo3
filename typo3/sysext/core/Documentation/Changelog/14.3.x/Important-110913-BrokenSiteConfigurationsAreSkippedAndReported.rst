..  include:: /Includes.rst.txt

..  _important-110913-1792506655:

========================================================================
Important: #110913 - Broken site configurations are skipped and reported
========================================================================

See :issue:`110913`

Description
===========

A site configuration that could not be loaded rendered the whole installation
inaccessible: frontend and backend failed with an exception as soon as the
site configuration cache was rebuilt. A typical cause is an
environment variable that resolves to an empty string, turning
:yaml:`base: 'https://%env(HOST)%/'` into the malformed URL
:samp:`https:///`.

Site configurations that cannot be loaded are now skipped and collected as
invalid sites instead. This covers malformed entry points (:yaml:`base`, base
variants and language bases), missing language locales, a missing
:yaml:`rootPageId`, syntax errors, invalid placeholders and missing imports in
:file:`config.yaml`, :file:`settings.yaml` or :file:`csp.yaml`. All other
sites keep working.

A site configuration folder without a :yaml:`rootPageId` used to be ignored
silently. It is now reported as an invalid site.

Other exceptions, for example thrown by an event listener of
:php:`\TYPO3\CMS\Core\Configuration\Event\SiteConfigurationLoadedEvent`,
are not caught and still fail the request as before.

Invalid sites are reported in these places:

*   The :guilabel:`Site Management > Sites` module lists them in the new
    section "Failing site configurations", with the error message and, if
    known, the configuration option causing it. Their root pages no longer
    offer to create a new site configuration.

*   Editing an invalid site is possible as long as its :file:`config.yaml` can
    be parsed. The form shows the error together with the affected field, for
    example "Available Languages for this Site › German › Entry Point".

*   The new command :bash:`site:validate` validates all sites, or a single site
    given by its identifier. It reports the errors of invalid sites and fails
    with a non-zero exit code if a site is invalid. Next to the entry point
    used in the current context, it validates the :yaml:`base` and the base of
    every base variant on its own, so a malformed entry point is found even if
    its condition does not match on the command line. Environment variables
    are resolved from the environment of the command, which can differ from
    the one of the web server. Validation reads the configuration files without
    updating the shared site configuration cache. This allows to verify the
    site configuration during deployment:

    ..  code-block:: bash

        vendor/bin/typo3 site:validate
        vendor/bin/typo3 site:validate main

*   The command :bash:`site:list` shows a warning listing the invalid sites and
    still succeeds.

*   A warning is logged for each invalid site when it is detected. Errors
    while loading the configuration files are logged once when the site
    configuration cache is built. Errors of entry points and locales are
    evaluated per request, as base variants depend on the context, and are
    logged on each request.

As an invalid site is not available, a request to its domain may now be
handled by another site with a matching, less specific entry point (for
example :yaml:`base: '/'`) instead of failing.

Pages of an invalid site that is nested within the page tree of another site
are not resolved to that other site. Looking up the site of such a page fails
with a :php:`\TYPO3\CMS\Core\Exception\SiteNotFoundException`, as it does
for pages without any site.

Invalid values for entry points and locales are reported by the new
:php:`\TYPO3\CMS\Core\Exception\InvalidSiteConfigurationException`, which
extends :php:`\InvalidArgumentException` and is thrown when creating a
:php:`\TYPO3\CMS\Core\Site\Entity\Site` object. Its public property
:php:`configurationPath` names the affected option, for example
:php:`languages.1.base`.

..  index:: Backend, CLI, YAML, ext:core
