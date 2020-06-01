<?php
namespace TYPO3\CMS\Styleguide\Tests\Acceptance\Support;

use TYPO3\TestingFramework\Core\Acceptance\Step\FrameSteps;/**
 * Inherited Methods
 * @method void wantToTest($text)
 * @method void wantTo($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method void pause()
 * @SuppressWarnings(PHPMD)
*/
class AcceptanceTester extends \Codeception\Actor
{
    use _generated\AcceptanceTesterActions;
    use FrameSteps;

    /**
     * Define custom actions here
     */
}
