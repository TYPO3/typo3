plugin.tx_form {
	settings {
			# registeredFilters
			# Used by: frontend, wizard (not implemented right now)
			# Overwritable by user: FALSE
			#
			# The array holds all available filters.
			# "displayName" is planned for the wizard (not implemented right now).
		registeredFilters {
			alphabetic {
				displayName = Alphabetic
				className = TYPO3\CMS\Form\Domain\Filter\AlphabeticFilter
			}

			alphanumeric {
				displayName = Alphanumeric
				className = TYPO3\CMS\Form\Domain\Filter\AlphanumericFilter
			}

			currency {
				displayName = Currency
				className = TYPO3\CMS\Form\Domain\Filter\CurrencyFilter
			}

			digit {
				displayName = Digit
				className = TYPO3\CMS\Form\Domain\Filter\DigitFilter
			}

			integer {
				displayName = Integer
				className = TYPO3\CMS\Form\Domain\Filter\IntegerFilter
			}

			lowercase {
				displayName = Lowercase
				className = TYPO3\CMS\Form\Domain\Filter\LowerCaseFilter
			}

			regexp {
				displayName = Regular Expression
				className = TYPO3\CMS\Form\Domain\Filter\RegExpFilter
			}

			removexss {
				displayName = Remove XSS
				className = TYPO3\CMS\Form\Domain\Filter\RemoveXssFilter
			}

			stripnewlines {
				displayName = Strip New Lines
				className = TYPO3\CMS\Form\Domain\Filter\StripNewLinesFilter
			}

			titlecase {
				displayName = Titlecase
				className = TYPO3\CMS\Form\Domain\Filter\TitleCaseFilter
			}

			trim {
				displayName = Trim
				className = TYPO3\CMS\Form\Domain\Filter\TrimFilter
			}

			uppercase {
				displayName = Uppercase
				className = TYPO3\CMS\Form\Domain\Filter\UpperCaseFilter
			}
		}
	}
}