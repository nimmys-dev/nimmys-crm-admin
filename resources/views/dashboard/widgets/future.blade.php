{{--
    Placeholders for modules not yet built.

    Deliberately shows an em dash rather than 0 — "not measured yet" must not
    look like a real reading of zero. Each panel reserves the space its chart
    will occupy so adding the real widget causes no reflow.

    When a module lands, replace the matching block with the real widget and
    delete its entry here.
--}}

<div class="grid grid-cols-12 gap-x-6">

    {{--
        Lead and follow-up statistics have graduated to real widgets — see
        dashboard/widgets/leads.blade.php. Only Task remains a placeholder.
    --}}
    @foreach ([
        [
            'title' => 'Task statistics',
            'icon' => 'ti ti-checklist',
            'note' => 'Available once the Task module is built.',
            'figures' => ['Open', 'Completed', 'Overdue'],
        ],
    ] as $panel)
        <div class="col-span-12 xl:col-span-4">
            <x-dashboard-widget :title="$panel['title']" :icon="$panel['icon']" class="widget-placeholder">

                <div class="placeholder-figures">
                    @foreach ($panel['figures'] as $figure)
                        <div>
                            <p class="stat-tile-label">{{ $figure }}</p>
                            <p class="placeholder-figure-value">—</p>
                        </div>
                    @endforeach
                </div>

                <x-chart-placeholder :label="$panel['title']" :note="$panel['note']" height="140px" />

            </x-dashboard-widget>
        </div>
    @endforeach

</div>
