.. include:: /Includes.rst.txt

.. _breaking-110966-1791465024:

===================================================================
Breaking: #110966 - Remove UTF8filesystem and systemLocale settings
===================================================================

See :issue:`110966`

Description
===========

The configuration options
:php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['UTF8filesystem']` and
:php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['systemLocale']` have been removed.

Both options existed so that PHP string functions process UTF-8 file names
correctly. To achieve that, TYPO3 temporarily switched :php:`LC_CTYPE` to the
configured system locale around calls to :php:`basename()`, :php:`dirname()`,
:php:`pathinfo()` and :php:`escapeshellarg()`.

The PHP manual still describes :php:`basename()` and :php:`dirname()` as
locale aware. The PHP source code shows that this no longer matters for
TYPO3:

*  :php:`dirname()` is implemented by ``zend_dirname()``, which searches for
   the directory separator byte by byte. It does not evaluate the locale at
   all.
*  :php:`basename()` is implemented by ``php_basename()``. Since PHP 8.1, it
   searches for the directory separator byte by byte as well, as long as the
   current locale is "ASCII compatible". PHP considers a locale ASCII
   compatible if it uses a single-byte encoding (such as :php:`C`,
   :php:`POSIX` or ISO-8859-1) or UTF-8. Only for other multibyte
   encodings, such as Shift-JIS, Big5 or GBK, PHP walks the string character
   by character with ``mblen()``. In these encodings, the second byte of a
   character can be :php:`0x5C`, which is the directory separator on Windows.
   In UTF-8, all bytes of a multibyte character are :php:`0x80` or higher, so
   they never match a directory separator.
*  :php:`pathinfo()` is based on these two functions.

This was verified with PHP 8.2, 8.4 and 8.5 on glibc: all three functions
return the same, correct results for UTF-8 paths in the :php:`C`,
:php:`C.UTF-8`, :php:`en_US.UTF-8` and :php:`de_DE.UTF-8` locales.

:php:`escapeshellarg()` is different. Its implementation
``php_escape_shell_arg()`` calls ``mblen()`` for every byte and silently
skips all bytes that are invalid in the current locale. In the :php:`C`
locale of glibc, which is ASCII, every byte of :php:`0x80` or higher is
invalid, so :file:`äöü.txt` turns into :file:`.txt`. The previous code only
prevented this if both options were configured, and EXT:indexed_search
called :php:`escapeshellarg()` directly. PHP-FPM commonly runs in the
:php:`C` locale, for example when started by systemd.

:php:`\TYPO3\CMS\Core\Utility\CommandUtility::escapeShellArgument()` and
:php:`\TYPO3\CMS\Core\Utility\CommandUtility::escapeShellArguments()` now quote
arguments byte by byte, independent of the current locale: the argument is
wrapped in single quotes, and each single quote is replaced by :php:`'\''`.
This is what :php:`escapeshellarg()` does on POSIX systems, without dropping
bytes. On Windows, :php:`escapeshellarg()` is still used. Arguments containing
null bytes are rejected with an :php:`\InvalidArgumentException`. The shell
calls of EXT:indexed_search and of the install tool use these methods as well.

:php:`\TYPO3\CMS\Core\Utility\PathUtility::basename()`,
:php:`\TYPO3\CMS\Core\Utility\PathUtility::dirname()` and
:php:`\TYPO3\CMS\Core\Utility\PathUtility::pathinfo()` no longer change the
locale and have been deprecated, see
:ref:`deprecation-110966-1791471928`.

File names are always stored with their Unicode characters (normalized to NFC).
The install tool checks for both options have been removed. Both options are
removed from :file:`config/system/settings.php` automatically when the install
tool is accessed.

Impact
======

The local driver no longer transliterates non-ASCII characters of uploaded or
renamed files. Before this change, :file:`Übersicht.pdf` was stored as
:file:`Uebersicht.pdf` if :php:`UTF8filesystem` was disabled. It is now stored
as :file:`Übersicht.pdf`. Characters that are not allowed in file names are
still replaced by underscores. Existing files are not renamed.

New installations have enabled :php:`UTF8filesystem` by default since TYPO3
v12, so they already behave this way.

Reading one of the two options from :php:`$GLOBALS['TYPO3_CONF_VARS']` raises
an "Undefined array key" warning.

Affected installations
======================

*  Installations that have :php:`UTF8filesystem` disabled. This is usually the
   case for installations upgraded from TYPO3 v11 or earlier that never changed
   the option.
*  Installations with extensions that read one of the two options.

Migration
=========

Remove any code that reads the two options.

To keep transliterating file names to ASCII, register a listener for
:php:`\TYPO3\CMS\Core\Resource\Event\SanitizeFileNameEvent`:

..  code-block:: php
    :caption: EXT:my_extension/Classes/EventListener/AsciiFileName.php

    namespace MyVendor\MyExtension\EventListener;

    use TYPO3\CMS\Core\Attribute\AsEventListener;
    use TYPO3\CMS\Core\Charset\CharsetConverter;
    use TYPO3\CMS\Core\Resource\Event\SanitizeFileNameEvent;

    final readonly class AsciiFileName
    {
        public function __construct(
            private CharsetConverter $charsetConverter,
        ) {}

        #[AsEventListener]
        public function __invoke(SanitizeFileNameEvent $event): void
        {
            $fileName = $this->charsetConverter->utf8_char_mapping($event->getFileName());
            $event->setFileName((string)preg_replace('/[^.\w-]/', '_', $fileName));
        }
    }

.. index:: FAL, LocalConfiguration, PHP-API, NotScanned, ext:core
