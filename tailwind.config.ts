import type { Config } from "tailwindcss";

export default {
  content: ["./app/**/*.{js,ts,jsx,tsx}", "./components/**/*.{js,ts,jsx,tsx}"],
  theme: {
    extend: {
      colors: {
        ink: "#17312d",
        muted: "#64736f",
        sage: "#5c8d83",
        mist: "#f2f7f5",
        line: "#dfe9e6",
      },
      boxShadow: {
        card: "0 12px 32px rgba(25, 61, 55, 0.06)",
      },
    },
  },
  plugins: [],
} satisfies Config;
