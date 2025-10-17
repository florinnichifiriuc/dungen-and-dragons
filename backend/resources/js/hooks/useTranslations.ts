import { useCallback } from 'react';

import { usePage } from '@inertiajs/react';

type TranslationShape = Record<string, unknown>;

type TranslationProps = {
    locale?: string;
    translations?: Record<string, TranslationShape>;
};

type ReplacementValues = Record<string, string | number>;

function resolve(path: string, translations: TranslationShape): unknown {
    return path.split('.').reduce<unknown>((accumulator, segment) => {
        if (accumulator && typeof accumulator === 'object' && segment in accumulator) {
            return (accumulator as Record<string, unknown>)[segment];
        }

        return undefined;
    }, translations);
}

function normalizePluralSegment(segment: string): [string | null, string] {
    const [rawKey, ...rest] = segment.split(':');

    if (rest.length === 0) {
        return [null, rawKey.trim()];
    }

    return [rawKey.trim(), rest.join(':').trim()];
}

function selectPluralForm(
    template: string,
    replacements: ReplacementValues | undefined,
    locale: string
): string {
    if (!replacements || replacements.count === undefined) {
        return template;
    }

    const parts = template.split('|');

    if (parts.length === 1) {
        return template;
    }

    const count = Number(replacements.count);

    if (Number.isNaN(count)) {
        return template;
    }

    const segments = parts.map(normalizePluralSegment);
    const hasExplicitKeys = segments.every(([key]) => key !== null);

    if (!hasExplicitKeys) {
        return count === 1 ? segments[0]?.[1] ?? template : segments[segments.length - 1]?.[1] ?? template;
    }

    if (count === 0) {
        const zeroMatch = segments.find(([key]) => key === 'zero');

        if (zeroMatch) {
            return zeroMatch[1];
        }
    }

    const pluralRule = new Intl.PluralRules(locale).select(count);
    const match = segments.find(([key]) => key === pluralRule) ?? segments.find(([key]) => key === 'other');

    return match?.[1] ?? template;
}

function applyReplacements(template: string, replacements: ReplacementValues | undefined): string {
    if (!replacements) {
        return template;
    }

    return Object.entries(replacements).reduce<string>((carry, [placeholder, value]) => {
        return carry.replaceAll(`:${placeholder}`, String(value));
    }, template);
}

export function useTranslations(namespace = 'app') {
    const { props } = usePage<TranslationProps>();
    const dictionary = (props.translations?.[namespace] as TranslationShape) ?? {};
    const locale = props.locale ?? 'en';

    const translate = useCallback(
        (key: string, fallback?: string, replacements?: ReplacementValues) => {
            const value = resolve(key, dictionary);

            const template =
                typeof value === 'string'
                    ? value
                    : value === undefined && fallback !== undefined
                      ? fallback
                      : typeof value === 'string'
                        ? value
                        : key;

            const resolvedPlural = selectPluralForm(template, replacements, locale);

            return applyReplacements(resolvedPlural, replacements);
        },
        [dictionary, locale]
    );

    return { t: translate, locale };
}
