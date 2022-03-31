.. include:: /Includes.rst.txt

===========================================
Important: #94492 - Introduce SVG Sanitizer
===========================================

See :issue:`94492`

Description
===========

SVG sanitization behavior of extension `t3g/svg-sanitizer <https://packagist.org/packages/t3g/svg-sanitizer>`__
has been introduced into TYPO3 core. Actual processing is done by low-level sanitization package
`enshrined/svg-sanitize <https://packagist.org/packages/enshrined/svg-sanitize>`__ by Daryll Doyle.

Introduced aspects
------------------

* handle :php:`GeneralUtility::upload_copy_move` invocations
* handle FAL action events `file-add`, `file-replace`, `set-content`
* provide upgrade wizard, sanitizing all SVG files in storages that
  are using :php:`\TYPO3\CMS\Core\Resource\Driver\LocalDriver`

Custom usage
------------

.. code-block:: php

   $sanitizer = new \TYPO3\CMS\Core\Resource\Security\SvgSanitizer();
   $sanitizer->sanitizeFile($sourcePath, $targetPath);
   $svg = $sanitizer->sanitizeContent($svg);

Basically this change enforces following public service announcements
concerning SVG files, to enhance these security aspects per default:

* `TYPO3-PSA-2020-003: Mitigation of Cross-Site Scripting Vulnerabilities in File Upload Handling <https://typo3.org/security/advisory/typo3-psa-2020-003>`__
* `TYPO3-PSA-2019-010: Cross-Site Scripting Vulnerabilities in File Upload Handling <https://typo3.org/security/advisory/typo3-psa-2019-010>`__

.. index:: Backend, FAL, Frontend, ext:core
