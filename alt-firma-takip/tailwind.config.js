/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./frontend/**/*.{html,js}",
    "./frontend/pages/**/*.{html,js}",
  ],
  theme: {
    extend: {
      colors: {
        primary: '#3b82f6',
        success: '#10b981',
        danger: '#ef4444',
        warning: '#f59e0b',
      },
    },
  },
  plugins: [],
}
