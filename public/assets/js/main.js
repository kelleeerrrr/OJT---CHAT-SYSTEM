window.addEventListener('load', function () {
    "use strict";

    /**
     * Easy selector helper function
     */
    const select = (el, all = false) => {
        el = el.trim()
        if (all) {
            return [...document.querySelectorAll(el)]
        } else {
            return document.querySelector(el)
        }
    }

    /**
     * Easy event listener function
     */
    const on = (type, el, listener, all = false) => {
        if (all) {
            select(el, all).forEach(e => e.addEventListener(type, listener))
        } else {
            select(el, all).addEventListener(type, listener)
        }
    }

    /**
     * Easy on scroll event listener
     */
    const onscroll = (el, listener) => {
        el.addEventListener('scroll', listener)
    }

    /**
     * Sidebar toggle
     */
    if (select('.sidebar-toggle')) {
        on('click', '.sidebar-toggle', function (e) {
            e.preventDefault();
            const sidebar = select('.sidebar');
            if (sidebar) {
                sidebar.classList.toggle('collapsed');
            }
        })
    }

    /**
     * Back to top button
     */
    let backtotop = select('.back-to-top')
    if (backtotop) {
        const toggleBacktotop = () => {
            if (window.scrollY > 100) {
                backtotop.classList.add('active')
            } else {
                backtotop.classList.remove('active')
            }
        }
        window.addEventListener('load', toggleBacktotop)
        onscroll(document, toggleBacktotop)

        backtotop.addEventListener('click', (e) => {
            e.preventDefault();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        })
    }

    /**
     * Dark mode toggle
     */
    if (select('.dark-mode-toggle')) {
        on('click', '.dark-mode-toggle', function (e) {
            e.preventDefault();
            document.body.classList.toggle('dark');
            localStorage.setItem('darkMode', document.body.classList.contains('dark'));
        })
    }

    /**
     * Initialize dark mode from localStorage
     */
    if (localStorage.getItem('darkMode') === 'true') {
        document.body.classList.add('dark');
    }
});
