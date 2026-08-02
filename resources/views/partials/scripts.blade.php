<script src="{{ asset('assets/js/plugins/simplebar.min.js') }}"></script>
<script src="{{ asset('assets/js/plugins/popper.min.js') }}"></script>
<script src="{{ asset('assets/js/icon/custom-icon.js') }}"></script>
{{-- Required: assets/js/script.js calls feather.replace() unguarded. --}}
<script src="{{ asset('assets/js/plugins/feather.min.js') }}"></script>
<script src="{{ asset('assets/js/component.js') }}"></script>
<script src="{{ asset('assets/js/theme.js') }}"></script>
<script src="{{ asset('assets/js/script.js') }}"></script>

<script>
    (function () {
        var LOGOS = {
            dark: @json(asset('assets/images/logo-white.svg')),
            light: @json(asset('assets/images/logo-dark.svg'))
        };

        // The theme's layout_change() rewrites logo src to a relative
        // "../assets/..." path, which breaks on nested routes like
        // /staff/create. Wrap it to persist the choice and use absolute URLs.
        var original = window.layout_change;

        window.layout_change = function (layout) {
            if (typeof original === 'function') {
                original(layout);
            }

            document.documentElement.setAttribute('data-pc-theme', layout);

            try {
                localStorage.setItem('crm-theme', layout);
            } catch (e) {}

            document.querySelectorAll('.logo-lg, .auth-logo').forEach(function (img) {
                img.setAttribute('src', LOGOS[layout] || LOGOS.light);
            });
        };

        var saved = 'light';
        try {
            saved = localStorage.getItem('crm-theme') || 'light';
        } catch (e) {}

        window.layout_change(saved);
        layout_theme_sidebar_change('dark');
        change_box_container('false');
        layout_caption_change('true');
        layout_rtl_change('false');
        preset_change('preset-1');
        main_layout_change('vertical');
    })();
</script>

@stack('scripts')
