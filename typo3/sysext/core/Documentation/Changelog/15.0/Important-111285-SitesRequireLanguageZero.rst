..  include:: /Includes.rst.txt

..  _important-111285-1793311928:

========================================================
Important: #111285 - Sites require language 0 as default
========================================================

See :issue:`111285`

Description
===========

TYPO3 expects every site to configure a language with ID `0`, which is used
as the default language of the site. The position of this language in the
site configuration does not matter.

The :guilabel:`System > Sites` module now shows an error for sites without
a language `0` in the site overview and in the site details, and refuses to
save a site configuration without a language `0`. The command
:bash:`vendor/bin/typo3 site:validate` reports such sites as an error of the
configuration option `languages`.

Sites without a language `0` keep working in the frontend: the first
configured language is used as default language. Add a language with ID `0`
to the configuration of such sites, either by editing the site in the
:guilabel:`System > Sites` module, which adds the language to the form, or
by editing the :file:`config/sites/<identifier>/config.yaml` file.

..  index:: Backend, YAML, ext:backend
