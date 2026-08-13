..  include:: /Includes.rst.txt

..  _important-83788-1787910205:

==========================================================
Important: #83788 - Select field filters ignore diacritics
==========================================================

See :issue:`83788`

Description
===========

The client side filters of a :php:`selectMultipleSideBySide` and of a
:php:`selectTree` field compared the typed text against the labels exactly as
both were written. Typing "creme" therefore did not find an option labelled
"Crème brûlée", and a category tree did not narrow to a category whose title
carries an accent.

Both the filter text and the labels are now folded before being compared: the
value is lowercased, NFD splits an accented character into its base character
plus a combining mark, and the marks of the combining diacritical marks block
are removed.

What is removed are the marks a script writes optionally: the combining
diacritical marks of Latin, Greek and Cyrillic, the Hebrew points and the
Arabic harakat. An Arabic label carrying its harakat is therefore found when it
is typed without them, and the alef variants fold onto the plain alef.

The marks that Thai, Devanagari and Japanese write obligatorily are kept,
because there a mark separates one word from another rather than accenting it.
Removing them would fold the Japanese for "school" and for "cuckoo" into one
value and leave a Thai filter narrowing nothing.

The Latin letters that Unicode does not decompose are spelled out instead, so
that an alphabet folds as a whole rather than by half: "ß" is compared as "ss",
"æ" as "ae", "ø" as "o", "ł" as "l", "þ" as "th" and so on. Typing "strasse"
therefore finds "Straße", and "lodz" finds "Łódź". Only the spellings that are
conventional are applied -- the ones that are a matter of language, such as the
German "ü" to "ue", are not, because the decomposition to "u" already settles
them.

Since NFD also decomposes precomposed Hangul syllables into jamo, and both sides
of the comparison are folded the same way, a Korean syllable now matches inside
another one sharing its leading jamo.

Characters that a reader cannot see are no longer a reason for a label not to
match: a soft hyphen and the zero width characters are dropped, and a no-break
space and the other space variants are compared as a plain space. Both arrive in
labels by pasting, and an editor has no way of telling that one is there.

Both steps live in the new module :js:`@typo3/core/utility/text-normalizer`,
which exports a :js:`TextNormalizer` class with the static methods
:js:`foldCaseAndDiacritics()` and :js:`normalizeInvisibleCharacters()`. That
module is internal: it exists so that the filters of the backend treat their
input the same way, and it is not a public JavaScript API for extensions yet.

Impact
======

Editors searching a long option list or a large category tree find an entry
whether or not they type its accents, in both directions: "creme" finds
"Crème brûlée" and "crème" finds "Cremeschnitte".

The same comparison backs the predefined filter values of the
:php:`multiSelectFilterItems` TCA option, which are matched through the very
same code path. A filter value configured with a diacritic in order to isolate
exactly the accented options now also matches the unaccented ones.

..  index:: Backend, JavaScript, TCA, ext:backend, ext:core
