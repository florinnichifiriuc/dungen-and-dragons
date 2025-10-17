import js from '@eslint/js';
import tsParser from '@typescript-eslint/parser';
import tsPlugin from '@typescript-eslint/eslint-plugin';
import reactPlugin from 'eslint-plugin-react';
import reactHooksPlugin from 'eslint-plugin-react-hooks';
import jsxA11yPlugin from 'eslint-plugin-jsx-a11y';

const targetFiles = [
    'resources/js/components/condition-timers/ConditionTimerShareLinkControls.tsx',
    'resources/js/components/condition-timers/MobileConditionTimerRecapWidget.tsx',
    'resources/js/components/condition-timers/PlayerConditionTimerSummaryPanel.tsx',
    'resources/js/Pages/Groups/ConditionTimerSummary.tsx',
    'resources/js/Pages/Shares/ConditionTimerSummary.tsx',
];

export default [
    {
        ignores: ['**/node_modules/**', 'vendor/**', 'public/**', 'build/**', 'storage/**'],
    },
    {
        files: targetFiles,
        languageOptions: {
            parser: tsParser,
            parserOptions: {
                ecmaVersion: 'latest',
                sourceType: 'module',
                ecmaFeatures: {
                    jsx: true,
                },
                project: './tsconfig.json',
            },
            globals: {
                window: 'readonly',
                document: 'readonly',
                navigator: 'readonly',
                route: 'readonly',
                fetch: 'readonly',
                console: 'readonly',
                setTimeout: 'readonly',
            },
        },
        plugins: {
            '@typescript-eslint': tsPlugin,
            react: reactPlugin,
            'react-hooks': reactHooksPlugin,
            'jsx-a11y': jsxA11yPlugin,
        },
        rules: {
            ...js.configs.recommended.rules,
            ...tsPlugin.configs.recommended.rules,
            ...reactPlugin.configs.recommended.rules,
            ...reactHooksPlugin.configs.recommended.rules,
            ...jsxA11yPlugin.configs.recommended.rules,
            'react/react-in-jsx-scope': 'off',
            'react/jsx-uses-react': 'off',
            'react/jsx-uses-vars': 'off',
        },
        settings: {
            react: {
                version: 'detect',
            },
        },
    },
];
