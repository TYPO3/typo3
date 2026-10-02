..  include:: /Includes.rst.txt

..  _important-110864-1790781786:

======================================================================
Important: #110864 - Copying records no longer inherits their language
======================================================================

See :issue:`110864`

Description
===========

The TCA setting `copyAfterDuplFields` of the table `tt_content` no
longer contains the field `sys_language_uid`. It now only contains
`colPos`.

Previously, copying a content element and pasting it after another record
copied the language of that preceding record onto the new element. A content
element set to "All languages" (`-1`) turned into a German element when it
was pasted after a German one, and vice versa.

The following rule applies to :php:`\TYPO3\CMS\Core\DataHandling\DataHandler`
when copying records:

*   A copy keeps the language of the record it was created from.
*   The language of a copy is only changed when the caller says so
    explicitly, for example by passing a language in the update data of a
    paste command, or by using the dedicated localization commands
    `localize` and `copyToLanguage`.
*   The language is never derived from the position the record is pasted to.

The Backend already follows this rule: the page module sends the language of
the target column explicitly when pasting.

Extensions that need the previous behavior for their own tables can keep
listing `sys_language_uid` in their own `copyAfterDuplFields`
setting. For `tt_content`, it has to be passed explicitly in the
update data of the copy command.

..  index:: TCA, ext:core, ext:frontend
