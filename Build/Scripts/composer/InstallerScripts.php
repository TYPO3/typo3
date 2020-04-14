<?php

declare(strict_types=1);
namespace TYPO3\CMS\Composer\Scripts;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2018 Helmut Hummel <info@helhum.io>
 *  All rights reserved
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the text file GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use Composer\Script\Event;
use Symfony\Component\Filesystem\Filesystem as SymfonyFilesystem;
use TYPO3\CMS\Composer\Plugin\Util\Filesystem;

class InstallerScripts
{
    public static function enableCommitMessageHook(Event $event)
    {
        $symfonyFilesystem = new SymfonyFilesystem();
        $filesystem = new Filesystem();
        try {
            $filesystem->copy('Build/git-hooks/commit-msg', '.git/hooks/commit-msg');
            if (!is_executable('.git/hooks/commit-msg')) {
                $symfonyFilesystem->chmod('.git/hooks/commit-msg', 0755);
            }
        } catch (\Symfony\Component\Filesystem\Exception\IOException $e) {
            $event->getIO()->writeError('<warning>Exception:enableCommitMessageHook:' . $e->getMessage() . '</warning>');
        }
    }

    public static function enablePreCommitHook(Event $event)
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            return;
        }
        $symfonyFilesystem = new SymfonyFilesystem();
        $filesystem = new Filesystem();
        try {
            $filesystem->copy('Build/git-hooks/unix+mac/pre-commit', '.git/hooks/pre-commit');
            if (!is_executable('.git/hooks/pre-commit')) {
                $symfonyFilesystem->chmod('.git/hooks/pre-commit', 0755);
            }
        } catch (\Symfony\Component\Filesystem\Exception\IOException $e) {
            $event->getIO()->writeError('<warning>Exception:enablePreCommitHook:' . $e->getMessage() . '</warning>');
        }
    }

    public static function disablePreCommitHook(Event $event)
    {
        $filesystem = new Filesystem();
        $filesystem->remove('.git/hooks/pre-commit');
    }
}
