..  include:: /Includes.rst.txt

..  _important-89325-1792047896:

==============================================================
Important: #89325 - Integrity check reports shadowed redirects
==============================================================

See :issue:`89325`

Description
===========

The redirects module allows multiple redirects with the same source host and
source path. Only one of them is ever used, all others are never matched, which
was not visible to editors.

The command :bash:`redirects:checkintegrity` now additionally marks such
redirects with the new integrity status :php:`shadowed`, available as
:php:`\TYPO3\CMS\Redirects\Utility\RedirectConflict::SHADOWED`. They are
highlighted in the redirects module and can be filtered by that status.

A redirect is considered shadowed when another enabled redirect is matched
first for every request the redirect itself would match, at any time it would
be active. This takes into account:

*   the source host, where an empty source host equals :php:`*`,
*   the source path, ignoring a trailing slash,
*   the options "Is regular expression" and "Respect GET parameters", where
    regular expressions are only compared literally,
*   the start and end time of both redirects.

Disabled redirects are never reported, and redirects that only overlap for a
part of their active time are not reported either. A redirect for a specific
host is not considered shadowed by one for any host :php:`*`, since the
specific host is matched first.

Running the command after an update may therefore mark existing redirects as
conflicting. Remove or adjust them to resolve the conflict, the status is reset
on the next run of the command.

..  index:: Backend, CLI, ext:redirects
