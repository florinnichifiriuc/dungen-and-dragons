import { useCallback } from 'react';

import { usePage } from '@inertiajs/react';

type TranslationShape = Record<string, unknown>;

type TranslationProps = {
    locale?: string;
    translations?: Record<string, TranslationShape>;
};

function resolve(path: string, translations: TranslationShape): unknown {
    return path.split('.').reduce<unknown>((accumulator, segment) => {
        if (accumulator && typeof accumulator === 'object' && segment in accumulator) {
            return (accumulator as Record<string, unknown>)[segment];
        }

        return undefined;
    }, translations);
}

export function useTranslations(namespace = 'app') {
    const { props } = usePage<TranslationProps>();
    const dictionary = (props.translations?.[namespace] as TranslationShape) ?? {};

    const translate = useCallback(
        (key: string, fallback?: string) => {
            const value = resolve(key, dictionary);

            if (typeof value === 'string') {
                return value;
            }

            if (value === undefined && fallback !== undefined) {
                return fallback;
            }

            return typeof value === 'string' ? value : key;
        },
        [dictionary]
    );

    return { t: translate, locale: props.locale ?? 'en' };
}
