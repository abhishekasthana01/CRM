/** @type {import('tailwindcss').Config} */
module.exports = {
    content: ["./src/Resources/**/*.blade.php", "./src/Resources/**/*.js"],

    theme: {
        container: {
            center: true,

            screens: {
                "4xl": "1920px",
            },

            padding: {
                DEFAULT: "16px",
            },
        },

        screens: {
            sm: "525px",
            md: "768px",
            lg: "1024px",
            xl: "1240px",
            "2xl": "1440px",
            "3xl": "1680px",
            "4xl": "1920px",
        },

        extend: {
            colors: {
                brandColor: "var(--brand-color)",
                beam: {
                    pink: "#FF2069",
                    pinkLight: "#FF5089", // +20% brightness
                    charcoal: "#06303A",
                    teal: "#54D9C9",
                    purple: "#5030E2",
                    yellow: "#F6C838",
                    grey: "#191919"
                },
            },

            fontFamily: {
                inter: ['Inter'],
                icon: ['icomoon']
            }
        },
    },
    
    darkMode: 'class',

    plugins: [],

    safelist: [
        {
            pattern: /icon-/,
        }
    ]
};