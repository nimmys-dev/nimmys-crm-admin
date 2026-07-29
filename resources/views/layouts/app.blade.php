<!DOCTYPE html>
<html lang="en">

<head>
    @include('layouts.header')
</head>

<body>

    {{-- Loader --}}
    <div class="loader-bg fixed inset-0 bg-white dark:bg-themedark-cardbg z-[1034]">
        <div class="loader-track h-[5px] w-full absolute top-0 overflow-hidden">
            <div
                class="loader-fill w-[300px] h-[5px] bg-primary-500 absolute top-0 left-0 animate-[hitZak_0.6s_ease-in-out_infinite_alternate]">
            </div>
        </div>
    </div>

    {{-- Sidebar --}}
    @include('layouts.sidebar')

    {{-- Header --}}
    @include('layouts.topbar')

    {{-- Main Content --}}
    <div class="pc-container">
        <div class="pc-content">
            @yield('content')
        </div>
    </div>

    {{-- Footer --}}
    @include('layouts.footer')

</body>

<script>
    window.addEventListener('load', function () {
    var loader = document.querySelector('.loader-bg');
    if (!loader) return;
    loader.style.transition = 'opacity 0.5s ease';
    loader.style.opacity = '0';
    setTimeout(function () {
        if (loader.parentNode) loader.parentNode.removeChild(loader);
    }, 500);
    });
</script>
<script src="{{ asset('assets/js/plugins/simplebar.min.js') }}"></script>
<script src="{{ asset('assets/js/plugins/popper.min.js') }}"></script>
<script src="{{ asset('assets/js/icon/custom-icon.js') }}"></script>
<script src="{{ asset('assets/js/plugins/feather.min.js') }}"></script>
<script src="{{ asset('assets/js/component.js') }}"></script>
<script src="{{ asset('assets/js/theme.js') }}"></script>
<script src="{{ asset('assets/js/script.js') }}"></script>

<div class="floting-button fixed bottom-[50px] right-[30px] z-[1030]">
</div>


<script>
  layout_change('false');
</script>
 

<script>
  layout_theme_sidebar_change('dark');
</script>

 
<script>
  change_box_container('false');
</script>
 
<script>
  layout_caption_change('true');
</script>
 
<script>
  layout_rtl_change('false');
</script>
 
<script>
  preset_change('preset-1');
</script>
 
<script>
  main_layout_change('vertical');
</script>


</html>
