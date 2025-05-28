import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./resources/**/*.vue",
  ],
  darkMode: 'class', // Activamos modo oscuro basado en clase 'dark'
  theme: {
    extend: {
      colors: {
        // Puedes extender tu paleta aquí si quieres
        indigo: {
          600: '#4f46e5',
          700: '#4338ca',
          400: '#818cf8',
        },
      },
      // Otras extensiones: fuentes, tamaños, etc
    },
  },
  plugins: [],
}




