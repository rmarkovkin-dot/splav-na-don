/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    './*.html',
    './*/*.html',
    './assets/js/*.js',
    '!./.imgbak/**',
    '!./.twbak/**'
  ],
  safelist: [
    'aspect-[4/3]',
    'aspect-[3/2]',
    'aspect-[16/9]',
    'grid-rows-[0fr]',
    'grid-rows-[1fr]'
  ],
  theme: {
    extend: {
      colors: {
        'river-deep': '#082f49',
        'ember': '#ea580c',
        'gold': '#fbbf24'
      },
      boxShadow: {
        'card': '0 4px 14px rgb(8 47 73 / 0.08)'
      },
      fontFamily: {
        sans: ['Manrope', 'system-ui', 'sans-serif']
      }
    }
  },
  plugins: []
}
