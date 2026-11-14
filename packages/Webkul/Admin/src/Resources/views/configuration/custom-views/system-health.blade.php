@php
    $report = app(\Webkul\Admin\SystemHealth\SystemHealth::class)->report();

    $attention = collect($report)->whereIn('state', ['failing', 'unconfirmed'])->count();

    $translation = 'admin::app.configuration.index.about.system-health';
@endphp

<div class="grid gap-4">
    <!-- Summary -->
    <div @class([
        'flex items-center gap-2.5 rounded-sm border px-4 py-3',
        'border-emerald-200 bg-emerald-50 dark:border-emerald-900 dark:bg-emerald-950' => ! $attention,
        'border-red-200 bg-red-50 dark:border-red-900 dark:bg-red-950' => $attention,
    ])>
        <span @class([
            'text-2xl',
            'icon-done text-emerald-600' => ! $attention,
            'icon-information text-red-600' => $attention,
        ])></span>

        <p class="text-sm font-semibold text-gray-800 dark:text-white">
            {{ $attention
                ? trans($translation.'.summary-attention', ['count' => $attention, 'total' => count($report)])
                : trans($translation.'.summary-healthy') }}
        </p>
    </div>

    <!-- Checks -->
    <div class="rounded-sm border dark:border-gray-800">
        @foreach ($report as $check)
            <div class="flex items-start gap-3 border-b px-4 py-3 last:border-b-0 dark:border-gray-800">
                <!-- State Icon -->
                <span @class([
                    'mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-base',
                    'icon-done bg-emerald-100 text-emerald-600 dark:bg-emerald-950' => $check['state'] === 'healthy',
                    'icon-cancel bg-red-100 text-red-600 dark:bg-red-950' => $check['state'] === 'failing',
                    'icon-information bg-amber-100 text-amber-600 dark:bg-amber-950' => $check['state'] === 'unconfirmed',
                    'bg-gray-100 text-gray-400 dark:bg-gray-800' => $check['state'] === 'idle',
                ])>
                    @if ($check['state'] === 'idle')
                        &ndash;
                    @endif
                </span>

                <!-- What It Is -->
                <div class="grid min-w-0 flex-1 gap-0.5">
                    <p class="text-sm font-semibold text-gray-800 dark:text-white">
                        @lang($translation.'.checks.'.$check['name'].'.title')
                    </p>

                    <p class="text-sm text-gray-600 dark:text-gray-300">
                        @lang($translation.'.checks.'.$check['name'].'.info')
                    </p>

                    @if ($check['message'])
                        <p class="wrap-anywhere text-sm font-medium text-red-600">
                            {{ $check['message'] }}

                            @if ($check['url'])
                                <a
                                    class="text-blue-600 hover:underline ltr:ml-1 rtl:mr-1"
                                    href="{{ $check['url'] }}"
                                >
                                    @lang($translation.'.open-settings')
                                </a>
                            @endif
                        </p>
                    @endif
                </div>

                <!-- State -->
                <p @class([
                    'shrink-0 text-sm font-semibold',
                    'text-emerald-600' => $check['state'] === 'healthy',
                    'text-red-600' => $check['state'] === 'failing',
                    'text-amber-600' => $check['state'] === 'unconfirmed',
                    'text-gray-500 dark:text-gray-400' => $check['state'] === 'idle',
                ])>
                    @lang($translation.'.states.'.$check['state'])
                </p>
            </div>
        @endforeach
    </div>
</div>
