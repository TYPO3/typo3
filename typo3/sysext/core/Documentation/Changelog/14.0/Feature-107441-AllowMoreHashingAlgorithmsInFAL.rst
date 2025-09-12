..  include:: /Includes.rst.txt

..  _feature-107441-1757657446:

=======================================================
Feature: #107441 - Allow more hashing algorithms in FAL
=======================================================

See :issue:`107441`

Description
===========

Previously, FAL's :php:`LocalDriver` only supported `md5` and `sha1` as hashing
algorithms. While this may be good enough, it might be necessary to use different
hashing algorithms, depending on the use-case.

The method :php:`LocalDriver->hash()` is now able to use any hashing algorithm
that is registered in PHP itself by building a :php:`HashContext` object and
updating it by streaming the file content.


Impact
======

FAL's :php:`LocalDriver` can now make use of different hashing algorithms, e.g.
`crc32`, `sha256` and many more.

..  index:: FAL, PHP-API, ext:core
