..  include:: /Includes.rst.txt

..  _important-111287-1793349163:

===============================================================================
Important: #111287 - Office documents detected as container format are accepted
===============================================================================

See :issue:`111287`

Description
===========

Office Open XML (`docx`, `xlsx`, `pptx`, …) and OpenDocument files (`odt`,
`ods`, `odp`) are ZIP archives, legacy Office files (`doc`, `xls`, `ppt`, …)
are OLE compound files. Depending on the libmagic version and on the
application that wrote a file, PHP only detects the container format, such as
`application/zip`, `application/x-ole-storage`, `application/CDFV2` or
`application/vnd.ms-office`.

With the feature :php:`security.system.enforceFileExtensionMimeTypeConsistency`
enabled, which is the default, such files were rejected on upload, for example
with "Mime-type "application/zip" not allowed for file extension "docx"".

:php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['FileInfo']['mimeTypeCompatibility']`
now maps these container types to the concrete Office MIME type by the file
extension, for the following extensions:

*   `application/zip`: `docx`, `docm`, `dotx`, `dotm`, `xlsx`, `xlsm`, `xlsb`,
    `xltx`, `xltm`, `xlam`, `pptx`, `pptm`, `ppsx`, `ppsm`, `potx`, `potm`,
    `ppam`, `sldx`, `sldm`, `odt`, `ods`, `odp`
*   `application/x-ole-storage`, `application/CDFV2` and
    `application/vnd.ms-office`: `doc`, `dot`, `xls`, `xlt`, `ppt`, `pps`

The mapping applies to the file abstraction layer, which stores the concrete
MIME type in the file index, and to the upload consistency checks of the
:php:`\TYPO3\CMS\Core\Resource\Service\ResourceConsistencyService` and the
Extbase :php:`\TYPO3\CMS\Extbase\Validation\Validator\FileExtensionMimeTypeConsistencyValidator`.

Impact on upload validation
---------------------------

A file detected as one of these container types is accepted if its file
extension is one of the mapped extensions. libmagic detects the concrete Office
type by the content of the container, never by the file name, so a plain ZIP
archive named `report.docx` was rejected before on every system. It now passes
the consistency check, as does any ZIP archive or OLE compound file under one
of the mapped extensions. This adds no capability for an attacker: the content
detection of ZIP containers is already satisfied by putting a
`[Content_Types].xml` entry first in the archive, and the allowed file
extensions and :php:`$GLOBALS['TYPO3_CONF_VARS']['BE']['fileDenyPattern']` are
unchanged. Other combinations are still rejected, for example a ZIP archive
named `report.pdf` or `drawing.odg`.

Files which are already in the file index keep their stored MIME type, such as
`application/zip`, until the index entry is updated. The consistency check on
renaming such a file uses the stored MIME type, so renaming still fails. The
index entry is updated with the concrete Office type when the modification
time of the file changes and the storage is indexed again, for example by the
scheduler task "File Abstraction Layer: Update storage index", or when the file
is replaced.

Installations which do not accept this can remove the entries in
:file:`config/system/additional.php`:

..  code-block:: php
    :caption: config/system/additional.php

    unset(
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['FileInfo']['mimeTypeCompatibility']['application/zip'],
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['FileInfo']['mimeTypeCompatibility']['application/x-ole-storage'],
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['FileInfo']['mimeTypeCompatibility']['application/CDFV2'],
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['FileInfo']['mimeTypeCompatibility']['application/vnd.ms-office'],
    );

..  index:: FAL, LocalConfiguration, ext:core
