/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./resources/views/**/*.blade.php",
    "./resources/js/**/*.js",
  ],
  plugins: [
    require("daisyui"),
  ],
  daisyui: {
    themes: ["light", "dark"],
  },
}
