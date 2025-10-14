import defaultTheme from 'tailwindcss/defaultTheme'
import forms from '@tailwindcss/forms'
import typography from '@tailwindcss/typography'

/** @type {import('tailwindcss').Config} */
export default {
  darkMode: 'class',
  content: ['index.html', './src/**/*.{ts,tsx}'],
  theme: {
    extend: {
      fontFamily: {
        display: ['"Cinzel Decorative"', ...defaultTheme.fontFamily.serif],
        body: ['"Inter"', ...defaultTheme.fontFamily.sans],
      },
      colors: {
        brand: {
          DEFAULT: '#C53030',
          foreground: '#F8FAFC',
          muted: '#742A2A',
          ring: '#F97316',
        },
        surface: {
          DEFAULT: '#111827',
          elevated: '#1F2937',
        },
      },
      boxShadow: {
        'brand-glow': '0 0 25px rgba(197, 48, 48, 0.45)',
      },
    },
  },
  plugins: [forms, typography],
}

