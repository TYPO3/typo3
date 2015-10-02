plugin.tx_form {
	settings {
			# registeredValidators
			# Used by: frontend, wizard (not implemented right now)
			# Overwritable by user: FALSE
			#
			# The array holds all available validators.
			# "displayName" is planned for the form wizard (not implemented right now).
		registeredValidators {
			alphabetic {
				displayName = Alphabetic
				className = TYPO3\CMS\Form\Domain\Validator\AlphabeticValidator
			}

			alphanumeric {
				displayName = Alphanumeric
				className = TYPO3\CMS\Form\Domain\Validator\AlphanumericValidator
			}

			between {
				displayName = Between
				className = TYPO3\CMS\Form\Domain\Validator\BetweenValidator
			}

			date {
				displayName = Date
				className = TYPO3\CMS\Form\Domain\Validator\DateValidator
			}

			digit {
				displayName = Digit
				className = TYPO3\CMS\Form\Domain\Validator\DigitValidator
			}

			email {
				displayName = Email address
				className = TYPO3\CMS\Form\Domain\Validator\EmailValidator
			}

			equals {
				displayName = Equals
				className = TYPO3\CMS\Form\Domain\Validator\EqualsValidator
			}

			fileallowedtypes {
				displayName = Allowed mimetypes for file
				className = TYPO3\CMS\Form\Domain\Validator\FileAllowedTypesValidator
			}

			filemaximumsize {
				displayName = Maximum size for file (bytes)
				className = TYPO3\CMS\Form\Domain\Validator\FileMaximumSizeValidator
			}

			fileminimumsize {
				displayName = Minimum size for file (bytes)
				className = TYPO3\CMS\Form\Domain\Validator\FileMinimumSizeValidator
			}

			float {
				displayName = Float
				className = TYPO3\CMS\Form\Domain\Validator\FloatValidator
			}

			greaterthan {
				displayName = Greater than
				className = TYPO3\CMS\Form\Domain\Validator\GreaterThanValidator
			}

			inarray {
				displayName = In array
				className = TYPO3\CMS\Form\Domain\Validator\InArrayValidator
			}

			integer {
				displayName = Integer
				className = TYPO3\CMS\Form\Domain\Validator\IntegerValidator
			}

			ip {
				displayName = Ip address
				className = TYPO3\CMS\Form\Domain\Validator\IpValidator
			}

			length {
				displayName = Length
				className = TYPO3\CMS\Form\Domain\Validator\LengthValidator
			}

			lessthan {
				displayName = Less than
				className = TYPO3\CMS\Form\Domain\Validator\LessThanValidator
			}

			regexp {
				displayName = Regular Expression
				className = TYPO3\CMS\Form\Domain\Validator\RegExpValidator
			}

			required {
				displayName = Required
				className = TYPO3\CMS\Form\Domain\Validator\RequiredValidator
			}

			uri {
				displayName = Uniform Resource Identifier
				className = TYPO3\CMS\Form\Domain\Validator\UriValidator
			}
		}
	}
}