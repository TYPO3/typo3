<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Backend\ViewHelpers\Uri;

use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

/**
 * Use this ViewHelper to provide 'create new record' links.
 * The ViewHelper will pass the command to FormEngine.
 *
 * The table argument is mandatory, it decides what record is to be created.
 *
 * The pid argument will put the new record on this page, if 0 given it will
 * be placed to the root page.
 *
 * The uid argument accepts only negative values. If this is given, the new
 * record will be placed (by sorting field) behind the record with the uid.
 * It will end up on the same pid as this given record, so the pid must not
 * be given explicitly by pid argument.
 *
 * An exception will be thrown, if both uid and pid are given.
 * An exception will be thrown, if the uid argument is not a negative integer.
 *
 * To edit records, use the editRecordViewHelper
 *
 * = Examples =
 *
 * <code title="Uri to create a new record of a_table after record 17 on the same pid">
 * <be:uri.newRecord table="a_table" returnUrl="foo/bar" uid="-17"/>
 * </code>
 * <output>
 *  /typo3/index.php?route=/record/edit&edit[a_table][-17]=new&returnUrl=foo/bar
 * </output>
 *
 * <code title="Uri to create a new record of a_table on root page">
 * <be:uri.newRecord table="a_table" returnUrl="foo/bar""/>
 * </code>
 * <output>
 *  /typo3/index.php?route=/record/edit&edit[a_table][]=new&returnUrl=foo/bar
 * </output>
 *
 * <code title="Uri to create a new record of a_table on page 17">
 * <be:uri.newRecord table="a_table" returnUrl="foo/bar" pid="17"/>
 * </code>
 * <output>
 *  /typo3/index.php?route=/record/edit&edit[a_table][-17]=new&returnUrl=foo/bar
 * </output>
 */
class NewRecordViewHelper extends AbstractTagBasedViewHelper
{
    use CompileWithRenderStatic;

    public function initializeArguments()
    {
        $this->registerArgument('uid', 'int', 'uid < 0 will insert the record after the given uid', false);
        $this->registerArgument('pid', 'int', 'the page id where the record will be created', false);
        $this->registerArgument('table', 'string', 'target database table', true);
        $this->registerArgument('returnUrl', 'string', '', false, '');
    }

    /**
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     *
     * @return string
     * @throws \TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext): string
    {
        if ($arguments['uid'] && $arguments['pid']) {
            throw new \InvalidArgumentException('Can\'t handle both uid and pid for new records', 1526136338);
        }
        if (isset($arguments['uid']) && $arguments['uid'] >= 0) {
            throw new \InvalidArgumentException('Uid must be negative integer, ' . $arguments['uid'] . ' given', 1526136362);
        }

        if (empty($arguments['returnUrl'])) {
            $arguments['returnUrl'] = GeneralUtility::getIndpEnv('REQUEST_URI');
        }

        $params = [
            'edit' => [$arguments['table'] => [$arguments['uid'] ?? $arguments['pid'] ?? 0 => 'new']],
            'returnUrl' => $arguments['returnUrl']
        ];
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        return (string)$uriBuilder->buildUriFromRoute('record_edit', $params);
    }
}
