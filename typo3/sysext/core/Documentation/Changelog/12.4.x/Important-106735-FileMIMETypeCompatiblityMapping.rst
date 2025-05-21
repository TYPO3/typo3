..  include:: /Includes.rst.txt

..  _important-106735-1748270977:

========================================================
Important: #106735 - File MIME Type compatiblity mapping
========================================================

See :issue:`106735`

Description
===========

With :issue:`106240` mime type hardening has been established in order to ensure
that file extensions of uploaded files and their contents are consistent in
order to avoid sneaking in malicious files with faked file extensions or to
bypass file extension limitations.

Since PHP file detection methods can not reliable detect all IANA defined MIME
types, mime-db based heuristics are now applied to map generic MIME types like
text/plain to text/csv for `*.csv` files.

This mapping has been made adjustable for MIME types via
:php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['FileInfo']['mimeTypeCompatibility']`
where for each generic MIME type (as detected by PHP MIME type detection) a
map from file extension to allowed concrete MIME type can be supplied.


..  code-block:: php
    :caption: Configure a custom MIME type to be mapped from a detected generic type

    // Example that is already shipped with TYPO3, a *.jfif file that is
    // detected as image/jpeg is mapped to image/pjpeg, which is the
    // defined MIME type per IANA and enforced by the FAL persistence layer.
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['FileInfo']['mimeTypeCompatibility']['image/jpeg']['jfif'] =
        'image/pjpeg';

    // Generic example, which allows a file ending in `*.foo` that is detected
    // to contain text/plain contents to be mapped to the MIME type text/x-foo,
    // other contents (e.g. if the file contains binary data) will not be mapped
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['FileInfo']['mimeTypeCompatibility']['text/plain']['foo'] =
        'text/x-foo';


..  index:: FAL, ext:core
