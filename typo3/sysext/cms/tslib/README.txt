The "tslib".

The "tslib" contains PHP4-classes that generates the standard TypoScript based frontend for TYPO3.
The library depends on t3lib/ and is a part of the "cms" extension.

This directory (cms/tslib/) should be symlinked (or moved to) the site root so it is available from there as "tslib/"

IMPORTANT NOTICE about using the library:
This directory must NEVER be used directly from this location inside the "cms" extension! It should always be used from the site-root and only by frontend code! It must be possible for non-symlink installations to remove the library from the "cms" extension to save space.
The reason for having it here is because it a) logically belongs to the "cms" extension and b) having it here makes it possible to update it through the EM (Extension Manager).

