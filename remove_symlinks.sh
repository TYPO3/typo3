#!/bin/sh

# This procedure is only needed if your CVS client cannot understand symlinks and you need to remove them.


# Remove symlink for tslib:
rm tslib

# Go to typo3/ folder:
cd typo3/

# Remove symlinks for t3lib/ and other things:
rm t3lib
rm thumbs.php
rm gfx

# Finally, go to the t3lib/fonts/ dir:
cd ../t3lib/fonts/

# Remove two symlinks to fonts:
rm verdana.ttf
rm arial.ttf

