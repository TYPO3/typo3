<?php

declare(strict_types=1);
namespace TYPO3\CMS\Styleguide\TcaDataGenerator;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Find matching field generator class instance
 */
class FieldGeneratorResolver
{
    /**
     * List of field generators to be called for values.
     * Order is important: Each class is called top-bottom until one returns
     * true on match(), then generate() is called on it.
     *
     * @var array
     */
    protected $fieldValueGenerators = [
        // dbType = date / datetime have ['config']['default'] set, so match them before general ConfigDefault
        FieldGenerator\TypeInputEvalDateDbTypeDate::class,
        FieldGenerator\TypeInputEvalDatetimeDbTypeDatetime::class,

        // Use value from ['config']['default'] if given
        FieldGenerator\ConfigDefault::class,

        // Specific type=input generator
        FieldGenerator\TypeInputMax4::class,
        FieldGenerator\TypeInputEvalAlphanum::class,
        FieldGenerator\TypeInputEvalDate::class,
        FieldGenerator\TypeInputEvalDatetime::class,
        FieldGenerator\TypeInputEvalDouble2::class,
        FieldGenerator\TypeInputEvalEmail::class,
        FieldGenerator\TypeInputEvalInt::class,
        FieldGenerator\TypeInputEvalIsIn::class,
        FieldGenerator\TypeInputEvalMd5::class,
        FieldGenerator\TypeInputEvalNum::class,
        FieldGenerator\TypeInputEvalRequiredTrimDate::class,
        FieldGenerator\TypeInputEvalTime::class,
        FieldGenerator\TypeInputEvalTimesec::class,
        FieldGenerator\TypeInputEvalUpper::class,
        FieldGenerator\TypeInputEvalYear::class,
        FieldGenerator\TypeInputWizardColorPicker::class,
        FieldGenerator\TypeInputWizardLink::class,
        FieldGenerator\TypeInputWizardSelect::class,
        FieldGenerator\TypeInputDynamicTextWithRecordUid::class,
        FieldGenerator\TypeInputForceL10nParent::class,
        // General type=input generator
        FieldGenerator\TypeInput::class,

        FieldGenerator\TypeTextDefaultExtrasRichtext::class,
        FieldGenerator\TypeTextFormatDatetime::class,
        FieldGenerator\TypeTextFormatT3editor::class,
        FieldGenerator\TypeTextMax30::class,
        FieldGenerator\TypeTextWizardSelect::class,
        FieldGenerator\TypeTextWizardTable::class,
        // General type=text generator
        FieldGenerator\TypeText::class,

        // General type=check generator
        FieldGenerator\TypeCheck::class,
        // General type=radio generator
        FieldGenerator\TypeRadio::class,

        // General type=none generator
        FieldGenerator\TypeNoneFormatDateTime::class,
        FieldGenerator\TypeNone::class,

        // General type=passthrough generator
        FieldGenerator\TypePassthrough::class,
        // General type=user generator
        FieldGenerator\TypeUser::class,

        // type=group
        FieldGenerator\TypeGroupFal::class,
        FieldGenerator\TypeGroupAllowedBeUsersBeGroups::class,
        FieldGenerator\TypeGroupAllowedBeUsers::class,
        FieldGenerator\TypeGroupAllowedStaticdata::class,
        FieldGenerator\TypeGroupAllowedPages::class,
        FieldGenerator\TypeGroupAllowedSysFiles::class,

        // type=folder
        FieldGenerator\TypeFolder::class,

        // type=select
        FieldGenerator\TypeSelectRenderTypeSingleForeignTable::class,
        FieldGenerator\TypeSelectRenderTypeSingleForeignTableForType::class,
        FieldGenerator\TypeSelectRenderTypeMultipleForeignTableStaticData::class,
        FieldGenerator\TypeSelectRenderTypeSelectTree::class,
        FieldGenerator\TypeSelect::class,

        FieldGenerator\TypeInlineFalSelectSingle12Foreign::class,
        FieldGenerator\TypeInlineFal::class,
        FieldGenerator\TypeInlineExpandsingle::class,
        FieldGenerator\TypeInlineUsecombination::class,

        // type=imageManipulation
        FieldGenerator\TypeImageManipulation::class,

        // General type=inline for simple 1:n parent child relations
        FieldGenerator\TypeInline1n::class,

        // General type=flex generator
        FieldGenerator\TypeFlex::class,
    ];

    /**
     * Resolve a generator class and return its instance.
     * Either returns an instance of FieldGeneratorInterface or throws exception
     *
     * @param array $data Criteria data
     * @return FieldGeneratorInterface
     * @throws GeneratorNotFoundException|Exception
     */
    public function resolve(array $data): FieldGeneratorInterface
    {
        $generator = null;
        foreach ($this->fieldValueGenerators as $fieldValueGenerator) {
            $generator = GeneralUtility::makeInstance($fieldValueGenerator);
            if (!$generator instanceof FieldGeneratorInterface) {
                throw new Exception(
                    'Field value generator ' . $fieldValueGenerator . ' must implement FieldGeneratorInterface',
                    1457693564
                );
            }
            if ($generator->match($data)) {
                break;
            }
            $generator = null;
        }
        if (is_null($generator)) {
            throw new GeneratorNotFoundException(
                'No generator found',
                1457873493
            );
        }
        return $generator;
    }
}
