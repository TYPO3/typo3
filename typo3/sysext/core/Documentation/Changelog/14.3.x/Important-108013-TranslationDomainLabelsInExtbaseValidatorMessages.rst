..  include:: /Includes.rst.txt

..  _important-108013-1794664302:

============================================================================
Important: #108013 - Translation domain labels in Extbase validator messages
============================================================================

See :issue:`108013`

Description
===========

Error messages of Extbase validators are translated only if they are label
references. Until now only messages starting with :php:`LLL:` were treated as
such, all other strings were used as plain text. A message given in the
translation domain syntax, for example
:php:`my_extension.messages:validator.error`, therefore appeared untranslated.

This applies to the :php:`message` option of validators extending
:php-short:`\TYPO3\CMS\Extbase\Validation\Validator\AbstractValidator` and to
the messages of Symfony constraints decorated by
:php-short:`\TYPO3\CMS\Extbase\Validation\Validator\ConstraintDecoratingValidator`.

A string is now handled as a label reference if it starts with :php:`LLL:` or
if it has the form :php:`<domain>:<key>` and the domain resolves to an
existing label resource. Any other string, including plain messages containing
a colon, stays untouched.

A plain message that happens to look like :php:`<domain>:<key>` of an installed
extension is translated from now on and has to be changed. If the domain
exists but the key does not, the reference itself is used as message instead of
an empty string. This also applies to unknown :php:`LLL:` references.

..  index:: PHP-API, ext:extbase
