/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./*.php",
    "./js/**/*.js"
  ],
  theme: {
    extend: {
      colors: {
        'charcoal': '#12161A',
        'dark-secondary': '#1C2228',
        'off-white': '#F8F9FA',
        'warm-cream': '#FAFAF8',
        'luxury-gold': '#D4AF37',
        'muted-gold': '#B9982E',
        'border-grey': '#E5E7EB',
        'muted-text': '#8B9198',
      },
      fontFamily: {
        'playfair': ['"Playfair Display"', 'Georgia', 'serif'],
        'inter': ['Inter', 'system-ui', 'sans-serif'],
        'mono': ['"JetBrains Mono"', '"Fira Code"', 'monospace'],
      },
      borderRadius: {
        'card': '14px',
      },
      boxShadow: {
        'card': '0 1px 3px rgba(0,0,0,0.07), 0 4px 16px rgba(0,0,0,0.05)',
        'card-hover': '0 4px 20px rgba(0,0,0,0.12)',
        'modal': '0 20px 60px rgba(0,0,0,0.25)',
      },
      transitionDuration: {
        '200': '200ms',
      }
    },
  },
  plugins: [],
}
