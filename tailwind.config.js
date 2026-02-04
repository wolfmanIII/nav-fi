import daisyui from "daisyui";

export default {
    content: [
        "./assets/**/*.js",
        "./templates/**/*.html.twig",
    ],

    plugins: {
        // ✅ Aggiungi daisyUI
        daisyui,
    },

    theme: {
        extend: {
            keyframes: {
                'holographic-glitch': {
                    '0%, 100%': { opacity: '1', filter: 'hue-rotate(0deg) blur(0px)' },
                    '5%': { opacity: '0.8', filter: 'hue-rotate(15deg) blur(1px)' },
                    '10%': { opacity: '0.9', filter: 'hue-rotate(-15deg) blur(0.5px)' },
                    '15%': { opacity: '1', filter: 'hue-rotate(0deg) blur(0px)' },
                    '45%': { opacity: '1', filter: 'hue-rotate(0deg) blur(0px)' },
                    '46%': { opacity: '0.5', filter: 'hue-rotate(90deg) blur(2px) brightness(1.5)' },
                    '47%': { opacity: '1', filter: 'hue-rotate(0deg) blur(0px)' },
                    '50%': { opacity: '1', transform: 'translateX(0)' },
                    '51%': { opacity: '0.9', transform: 'translateX(-2px)' },
                    '52%': { opacity: '1', transform: 'translateX(0)' },
                    '80%': { opacity: '1', filter: 'hue-rotate(0deg) blur(0px)' },
                    '81%': { opacity: '0.8', filter: 'hue-rotate(45deg) blur(1px) translateX(2px)' },
                    '82%': { opacity: '1', filter: 'hue-rotate(0deg) blur(0px)' },
                }
            },
            animation: {
                'holographic-glitch': 'holographic-glitch 4s infinite linear',
            }
        }
    },
};
