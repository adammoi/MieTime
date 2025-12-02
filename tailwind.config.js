/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./index.php",
    "./pages/**/*.php",
    "./admin/**/*.php",
    "./includes/**/*.php",
    "./assets/js/**/*.js",
  ],
  theme: {
    extend: {
      colors: {
        primary: {
          50: "#f5f7ff",
          100: "#ebf0fe",
          200: "#d6e0fd",
          300: "#b3c5fb",
          400: "#8099f7",
          500: "#667eea",
          600: "#4f5dd9",
          700: "#3f47b8",
          800: "#363d94",
          900: "#2f3677",
        },
        secondary: {
          50: "#f9f5ff",
          100: "#f3ebfe",
          200: "#e9d8fd",
          300: "#d6b8fb",
          400: "#ba8df7",
          500: "#764ba2",
          600: "#663d8f",
          700: "#563278",
          800: "#462961",
          900: "#39234f",
        },
      },
      fontFamily: {
        sans: ["Inter", "system-ui", "-apple-system", "sans-serif"],
      },
      animation: {
        "fade-in": "fadeIn 0.45s ease-in-out",
        "slide-in": "slideIn 0.45s ease-in-out",
        "bounce-slow": "bounce 2s infinite",
      },
      keyframes: {
        fadeIn: {
          "0%": { opacity: "0", transform: "translateY(20px)" },
          "100%": { opacity: "1", transform: "translateY(0)" },
        },
        slideIn: {
          "0%": { opacity: "0", transform: "translateX(-20px)" },
          "100%": { opacity: "1", transform: "translateX(0)" },
        },
      },
      boxShadow: {
        soft: "0 2px 8px rgba(0, 0, 0, 0.08)",
        medium: "0 4px 12px rgba(0, 0, 0, 0.12)",
        hard: "0 8px 24px rgba(0, 0, 0, 0.15)",
        lift: "0 12px 24px rgba(0, 0, 0, 0.15)",
      },
      borderRadius: {
        xl: "1rem",
        "2xl": "1.5rem",
        "3xl": "2rem",
      },
    },
  },
  plugins: [
    require("@tailwindcss/forms"),
    require("@tailwindcss/typography"),
    require("@tailwindcss/aspect-ratio"),
  ],
};
