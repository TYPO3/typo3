import { resolve, join } from 'path'
import { readdirSync } from 'fs'

/**
 * Helper function to build rollup bundling configuration for all existing
 * CKEditor translations
 */
export function buildConfigForTranslations() {
  const translationPath = resolve('./ckeditorLocales/')
  const translationFiles = readdirSync(translationPath)
  const configuration = []

  for (const translationFile of translationFiles) {
    configuration.push({
      input: join(translationPath, translationFile),
      output: {
        compact: true,
        file: `../typo3/sysext/rte_ckeditor/Resources/Public/Contrib/translations/${translationFile}`,
        format: 'es',
      },
    })
  }

  return configuration
}
