<?php
declare(strict_types=1);

namespace TYPO3\CMS\Frontend\AdminPanel;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

class CacheModule extends AbstractModule
{
    /**
     * Creates the content for the "cache" section ("module") of the Admin Panel
     *
     * @return string HTML content for the section. Consists of a string with table-rows with four columns.
     */
    public function getContent(): string
    {
        $output = [];
        if ($this->getBackendUser()->uc['TSFE_adminConfig']['display_cache']) {
            $output[] = '<div class="typo3-adminPanel-form-group">';
            $output[] = '  <div class="typo3-adminPanel-form-group-checkbox">';
            $output[] = '    <input type="hidden" name="TSFE_ADMIN_PANEL[cache_noCache]" value="0" />';
            $output[] = '    <label for="cache_noCache">';
            $output[] = '      <input type="checkbox" id="cache_noCache" name="TSFE_ADMIN_PANEL[cache_noCache]" value="1"' .
                        ($this->getBackendUser()->uc['TSFE_adminConfig']['cache_noCache'] ? ' checked="checked"' : '') .
                        ' />';
            $output[] = '      ' . $this->extGetLL('cache_noCache');
            $output[] = '    </label>';
            $output[] = '  </div>';
            $output[] = '</div>';

            $levels = $this->getBackendUser()->uc['TSFE_adminConfig']['cache_clearCacheLevels'];
            $output[] = '<div class="typo3-adminPanel-form-group">';
            $output[] = '  <label for="cache_clearCacheLevels">';
            $output[] = '    ' . $this->extGetLL('cache_clearLevels');
            $output[] = '  </label>';
            $output[] = '  <select id="cache_clearCacheLevels" name="TSFE_ADMIN_PANEL[cache_clearCacheLevels]">';
            $output[] = '    <option value="0"' . ((int)$levels === 0 ? ' selected="selected"' : '') . '>';
            $output[] = '      ' . $this->extGetLL('div_Levels_0');
            $output[] = '    </option>';
            $output[] = '    <option value="1"' . ($levels == 1 ? ' selected="selected"' : '') . '>';
            $output[] = '      ' . $this->extGetLL('div_Levels_1');
            $output[] = '    </option>';
            $output[] = '    <option value="2"' . ($levels == 2 ? ' selected="selected"' : '') . '>';
            $output[] = '      ' . $this->extGetLL('div_Levels_2');
            $output[] = '    </option>';
            $output[] = '  </select>';
            $output[] = '</div>';

            $output[] = '<div class="typo3-adminPanel-form-group">';
            $output[] = '  <input type="hidden" name="TSFE_ADMIN_PANEL[cache_clearCacheId]" value="' .
                        $GLOBALS['TSFE']->id .
                        '" />';
            $output[] = '  <input class="typo3-adminPanel-btn typo3-adminPanel-btn-default" type="submit" value="' .
                        $this->extGetLL('update') .
                        '" />';
            $output[] = '</div>';
            $output[] = '<div class="typo3-adminPanel-form-group">';
            $output[] = '  <input class="typo3-adminPanel-btn typo3-adminPanel-btn-default" type="submit" name="TSFE_ADMIN_PANEL[action][clearCache]" value="' .
                        $this->extGetLL('cache_doit') .
                        '" />';
            $output[] = '</div>';
        }
        return implode('', $output);
    }

    /**
     * @inheritdoc
     */
    public function getIdentifier(): string
    {
        return 'cache';
    }

    /**
     * @inheritdoc
     */
    public function getLabel(): string
    {
        return $this->extGetLL('cache');
    }

    /**
     * @inheritdoc
     */
    public function showFormSubmitButton(): bool
    {
        return true;
    }
}
