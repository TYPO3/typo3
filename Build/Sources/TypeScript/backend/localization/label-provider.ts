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

import { IntlMessageFormat, type PrimitiveType, type FormatXMLElementFn, type Formatters } from 'intl-messageformat';
import type { DateConfiguration } from '@typo3/backend/type/date-configuration';
import { DateTime } from 'luxon';

type ChunkCallback = (chunks: unknown[]) => unknown;
type NamedParameters = Record<string, PrimitiveType | ChunkCallback>;
type SprintfParameters = Array<string|number>;

type TemplatedParameters<Type, T> = {
  [Property in keyof Type]: Type[Property] extends ChunkCallback
    // Map the ChunkCallback (that contains `unknown` type) to allow `T`
    ? FormatXMLElementFn<T>
    // Allow type T (e.g. lit TemplateResult) for all values that are a string
    : (Type[Property] extends string ? Type[Property]|T : Type[Property]);
};

export class LabelProvider<LabelParameterMap extends Record<string, NamedParameters|SprintfParameters|undefined>> {
  constructor(
    private readonly labels: Record<keyof LabelParameterMap, string>
  ) {}

  public get<K extends keyof LabelParameterMap>(
    key: K,
    // Workaround to ensure that TypeScript enforces the exact number of parameters
    // Note: `args?` allows to omit parameters, when they are actually required.
    ...args: (LabelParameterMap[K] extends undefined ? [] : [Readonly<TemplatedParameters<LabelParameterMap[K], never>>])
  ): string;

  public get<K extends keyof LabelParameterMap>(
    key: K,
    args?: Readonly<TemplatedParameters<LabelParameterMap[K], never>>,
  ): string {
    const res = this.render<K, never>(key, args);
    return Array.isArray(res) ? res.join('') : res;
  }

  public render<K extends keyof LabelParameterMap, T extends object|never>(
    key: K,
    args: Readonly<TemplatedParameters<LabelParameterMap[K], T>>,
  ): string | T | Array<string | T> {
    if (!(key in this.labels)) {
      throw new Error('Label is not defined: ' + String(key));
    }

    const label = this.labels[key];

    if (args === undefined) {
      return label;
    }

    if (Array.isArray(args)) {
      return this.sprintf(label, args);
    }

    const parts = this.getFormatter(label).formatToParts<T>(args as Record<string, PrimitiveType | T | FormatXMLElementFn<T>>);

    // Hot path for straight simple msg translations
    if (parts.length === 1) {
      return parts[0].value;
    }
    return parts.map(part => part.value);
  }

  private sprintf(
    label: string,
    args: Readonly<SprintfParameters>
  ): string {
    // code taken from lit-helper
    let index = 0;
    return label.replace(/%[sdf]/g, (match) => {
      const arg = args[index++];
      switch (match) {
        case '%s':
          return String(arg);
        case '%d':
          return String(typeof arg === 'number' ? arg : parseInt(String(arg), 10));
        case '%f':
          return String(typeof arg === 'number' ? arg : parseFloat(arg).toFixed(2));
        default:
          return match;
      }
    });
  }

  private getFormatter(label: string): IntlMessageFormat {
    return memoize('message', createFormatter)(label);
  }
}

const configuredFormats = getConfiguredDateFormats();

function createFormatter(label: string): IntlMessageFormat {
  const timeZone = configuredFormats?.timezone ?? undefined;
  const dateConfig = {
    short: { timeZone, dateStyle: 'short' },
    medium: { timeZone, dateStyle: 'medium' },
    long: { timeZone, dateStyle: 'long' },
    full: { timeZone, dateStyle: 'full' }
  } as const;
  const timeConfig = {
    short: { timeZone, timeStyle: 'short' },
    medium: { timeZone, timeStyle: 'medium' },
    long: { timeZone, timeStyle: 'long' },
    full: { timeZone, timeStyle: 'full' }
  } as const;

  return new IntlMessageFormat(
    label,
    getLocale(),
    {
      date: dateConfig,
      time: timeConfig,
    },
    { formatters }
  );
}

const formatters: Formatters = {
  getNumberFormat: memoize('number', (locale?: string | string[], opts?: Intl.NumberFormatOptions) => new Intl.NumberFormat(locale, opts)),
  getDateTimeFormat: memoize('datetime', (locale?: Intl.LocalesArgument, opts?: Intl.DateTimeFormatOptions) => {
    const { dateStyle, timeStyle, timeZone } = opts;

    // Apply configured TYPO3 date format to the "medium" preset
    if (configuredFormats && (
      dateStyle === 'medium' ||
      timeStyle === 'medium'
    )) {
      const format = (value: string) => {
        return DateTime.fromJSDate(new Date(value), { zone: timeZone })
          .setLocale(locale as string)
          .toFormat(
            (dateStyle === 'medium' && timeStyle === 'medium')
              ? configuredFormats.formats.datetime
              : (dateStyle === 'medium' ? configuredFormats.formats.date : configuredFormats.formats.time)
          );
      };
      return { format } as unknown as Intl.DateTimeFormat;
    }

    return new Intl.DateTimeFormat(locale, { dateStyle, timeStyle, timeZone });
  }),
  getPluralRules: memoize('date', (locale?: Intl.LocalesArgument, opts?: Intl.PluralRulesOptions) => new Intl.PluralRules(locale, opts)),
};

type Factory = (...args: never[]) => object;
const cache: Record<string, object> = {};
/**
 * Cache the (object) result of a factory function,
 * to avoid costly Intl.* initialization over and over again.
 */
function memoize<F extends Factory>(context: string, factory: F): F {
  return ((...args: Parameters<F>): ReturnType<F> => {
    const identifier = JSON.stringify({ context, args });
    return (cache[identifier] ??= factory(...args)) as ReturnType<F>;
  }) as unknown as F;
}

function getConfiguredDateFormats(): DateConfiguration | null {
  try {
    const root = (typeof opener?.top?.TYPO3 !== 'undefined' ? opener.top : top);
    return root.TYPO3.settings.DateConfiguration;
  } catch {
    return null;
  }
}

function getLocale(): string {
  const locale = document.documentElement.lang || 'en';
  // Handle TYPO3's special 'ch' locale mapping (similar to date-time-picker.ts)
  if (locale === 'ch') {
    return 'zh';
  }
  return locale;
}
