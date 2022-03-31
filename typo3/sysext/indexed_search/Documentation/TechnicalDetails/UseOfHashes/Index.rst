.. include:: /Includes.rst.txt



.. _use-of-hashes:

Use of hashes
^^^^^^^^^^^^^

The hashes used are md5 hashes where the first 7 chars are converted
into an integer which is used as the hash in the database. This is
done in order to save space in the database, thus using only 4 bytes
and not a varchar of 32 bytes. It's estimated that a hash of 7 chars
(32) is sufficient (originally 8, but at some point PHP changed
behavior with hexdec-function so that where originally a 32 bit value
was input half the values would be negative, they were suddenly
positive all of them. That would require a similar change of the
fields in the database. To cut it simple, the length was reduced to 7,
all being positive then).

