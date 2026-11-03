..  include:: /Includes.rst.txt

..  _important-111329-1793705407:

======================================================================================
Important: #111329 - Extbase returns records without default language in fallback mode
======================================================================================

See :issue:`111329`

Description
===========

Records can be created directly in a language other than the default language,
without a record in the default language they are translated from
(:sql:`l10n_parent = 0`). These records are often called "floating" records.

For site languages with :yaml:`fallbackType: fallback`, Extbase repositories
did not return such records, whereas the TypoScript content object
:typoscript:`CONTENT` and
:php-short:`\TYPO3\CMS\Core\Domain\Repository\PageRepository` did. For site
languages with :yaml:`fallbackType: strict`, both already returned them.

Extbase queries now also include these records for
:yaml:`fallbackType: fallback` (:php:`LanguageAspect::OVERLAYS_MIXED`), so
Extbase plugins and TypoScript return the same records. This also applies to
the result of :php:`QueryInterface::count()`.

Extension authors whose plugins run on sites with
:yaml:`fallbackType: fallback` should check whether records without a default
language record now appear in their output, for example in lists or
paginations. If these records must not be shown, exclude them in the
repository query, for example with a constraint on the translation parent
field.

..  index:: Frontend, ext:extbase
