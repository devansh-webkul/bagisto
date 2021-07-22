{{-- preloaded fonts --}}
<link rel="preload" href="{{ asset('themes/velocity/assets/fonts/font-rango/rango.ttf') . '?o0evyv' }}" as="font" crossorigin="anonymous" />

{{-- bootstrap --}}
<link rel="stylesheet" href="{{ asset('themes/velocity/assets/css/bootstrap.min.css') }}" />

{{-- bootstrap flipped for rtl --}}
@if (core()->getCurrentLocale() && core()->getCurrentLocale()->direction == 'rtl')
    <link href="{{ asset('themes/velocity/assets/css/bootstrap-flipped.css') }}" rel="stylesheet">
@endif

{{-- mix versioned compiled file --}}
<link rel="stylesheet" href="{{ asset(mix('/css/velocity.css', 'themes/velocity/assets')) }}" />
<link rel="stylesheet" href="{{ asset(mix('/css/velocity-xl-devices.css', 'themes/velocity/assets')) }}" media="screen and (max-width: 1192px)" />
<link rel="stylesheet" href="{{ asset(mix('/css/velocity-l-devices.css', 'themes/velocity/assets')) }}" media="screen and (max-width: 992px)" />
<link rel="stylesheet" href="{{ asset(mix('/css/velocity-m-devices.css', 'themes/velocity/assets')) }}" media="screen and (max-width: 768px)" />
<link rel="stylesheet" href="{{ asset(mix('/css/velocity-sm-devices.css', 'themes/velocity/assets')) }}" media="screen and (max-width: 420px)" />
<link rel="stylesheet" href="{{ asset(mix('/css/velocity-xm-devices.css', 'themes/velocity/assets')) }}" media="screen and (max-width: 320px)" />

{{-- extra css --}}
@stack('css')

{{-- custom css --}}
<style>
    {!! core()->getConfigData('general.content.custom_scripts.custom_css') !!}
</style>