/**** Tailwind CSS config for Binajia (Symfony) ****/
module.exports = {
  content: [
    './assets/**/*.js',
    './templates/**/*.html.twig',
    './templates/**/*.twig'
  ],
  theme: {
    extend: {
      colors: {
        // Primary brand: deep logo green
        primary: { DEFAULT: '#1E6A2E', dark: '#155024', light: '#2E8B57' },
        // Secondary brand: logo gold
        secondary: { DEFAULT: '#D4A030', dark: '#B5831F', light: '#E6B84A' },
        // Accent: brighter supportive green
        accent: { DEFAULT: '#3D9B65', dark: '#2F7A4E', light: '#61B381' },
        cream: '#FAF8F5',
        charcoal: '#2B2520'
      },
      fontFamily: {
        sans: ['Inter','ui-sans-serif','system-ui'],
        serif: ['Playfair Display','Georgia','serif']
      },
      backgroundImage: {
        'afric-motif': "url('data:image/svg+xml;utf8,<svg xmlns=\\"http://www.w3.org/2000/svg\\" width=\\"220\\" height=\\"220\\" viewBox=\\"0 0 220 220\\" fill=\\"none\\"><g opacity=\\"0.05\\"><path d=\\"M0 110h220M110 0v220\\" stroke=\\"%232B2520\\" stroke-width=\\"1\\"/><circle cx=\\"110\\" cy=\\"110\\" r=\\"40\\" stroke=\\"%232B2520\\" stroke-width=\\"1\\" fill=\\"none\\"/></g></svg>')"
      }
    }
  },
  plugins: []
};
