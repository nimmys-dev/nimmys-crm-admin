@extends('layouts.app')

@section('title', 'Reports')

@section('content')

    <div class="space-y-6">
        
        <!-- Top Bar: Title & Export Actions -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Reports</h1>
                <p class="text-xs text-gray-500 mt-1">Home &gt; Reports</p>
            </div>

            <!-- Export Buttons -->
            <div class="flex items-center space-x-2">
                <a href="{{ route('reports.index', array_merge(request()->query(), ['export' => 'csv'])) }}" 
                   style="background-color: #059669 !important; color: #ffffff !important;"
                   class="px-4 py-2 rounded-lg text-sm font-semibold hover:opacity-90 transition flex items-center shadow-sm">
                    <i class="ti ti-file-spreadsheet mr-1.5 text-base"></i> Export CSV
                </a>

                <a href="{{ route('reports.index', array_merge(request()->query(), ['export' => 'excel'])) }}" 
                   style="background-color: #2563eb !important; color: #ffffff !important;"
                   class="px-4 py-2 rounded-lg text-sm font-semibold hover:opacity-90 transition flex items-center shadow-sm">
                    <i class="ti ti-file-text mr-1.5 text-base"></i> Export Excel
                </a>
            </div>
        </div>

        <!-- Filter & Search Bar -->
        <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm">
            <form method="GET" action="{{ route('reports.index') }}" class="grid grid-cols-12 gap-3 items-end">
                
                <!-- 1. Report Type -->
                <div class="col-span-12 lg:col-span-3">
                    <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Report Type</label>
                    <select name="report_type" class="w-full border-gray-300 rounded-lg text-sm p-2 focus:ring-2 focus:ring-[#7A0C16] focus:outline-none border bg-white">
                        <option value="">All Reports</option>
                        <option value="task" {{ request('report_type') == 'task' ? 'selected' : '' }}>Task Reports Only</option>
                        <option value="lead" {{ request('report_type') == 'lead' ? 'selected' : '' }}>Lead Reports Only</option>
                        <option value="staff" {{ request('report_type') == 'staff' ? 'selected' : '' }}>Staff Performance Reports</option>
                    </select>
                </div>

                <!-- 2. From Date -->
                <div class="col-span-12 sm:col-span-6 lg:col-span-2">
                    <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">From Date</label>
                    <input type="date" name="from_date" value="{{ request('from_date') }}" class="w-full border-gray-300 rounded-lg text-sm p-2 focus:ring-2 focus:ring-[#7A0C16] focus:outline-none border">
                </div>

                <!-- 3. To Date -->
                <div class="col-span-12 sm:col-span-6 lg:col-span-2">
                    <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">To Date</label>
                    <input type="date" name="to_date" value="{{ request('to_date') }}" class="w-full border-gray-300 rounded-lg text-sm p-2 focus:ring-2 focus:ring-[#7A0C16] focus:outline-none border">
                </div>

                <!-- 4. Search Bar -->
                <div class="col-span-12 lg:col-span-3">
                    <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Search Keyword</label>
                    <div class="flex items-center">
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Title, staff, or status..." class="w-full border-gray-300 rounded-l-lg text-sm p-2 focus:ring-2 focus:ring-[#7A0C16] focus:outline-none border-y border-l border-r-0">
                        <button type="submit" title="Search" style="background-color: #7A0C16 !important; color: #ffffff !important;" class="px-4 py-2 text-white text-sm font-semibold rounded-r-lg hover:opacity-90 transition shadow-sm flex items-center justify-center">
                            <i class="ti ti-search text-base"></i>
                        </button>
                    </div>
                </div>

                <!-- 5. Action Buttons -->
                <!-- <div class="col-span-12 lg:col-span-2 flex items-center space-x-2">
                    <button type="submit" style="background-color: #7A0C16 !important; color: #ffffff !important;" class="flex-1 py-2 text-white text-sm font-semibold rounded-lg hover:opacity-90 transition shadow flex items-center justify-center space-x-1">
                        <i class="ti ti-filter text-base"></i>
                        <span>Filter</span>
                    </button>
                    
                    @if(request()->anyFilled(['report_type', 'from_date', 'to_date', 'search']))
                        <a href="{{ route('reports.index') }}" class="px-3 py-2 bg-gray-200 text-gray-700 text-sm font-semibold rounded-lg hover:bg-gray-300 transition flex items-center justify-center" title="Reset Filters">
                            <i class="ti ti-refresh text-base"></i>
                        </a>
                    @endif
                </div> -->

            </form>
        </div>

        <!-- Data Table Container -->
        <div class="grid grid-cols-12 gap-x-6">
            <div class="col-span-12">
                <x-card title="Reports List">
                    
                    @if(request('report_type') === 'staff')
                        <!-- Staff Performance Table Structure -->
                        <x-datatable
                            :headers="['Staff Name', 'Total Leads Assigned', 'Closed Leads', 'Completed Tasks', 'Date', 'Actions']"
                            empty-message="No staff performance records found."
                            empty-icon="ti ti-user-check"
                        >
                            @forelse($reports as $report)
                                <tr class="hover:bg-gray-50/80 transition border-b border-gray-100 text-sm">
                                    <td class="py-3.5 px-6 font-semibold text-gray-900">{{ $report['staff_name'] }}</td>
                                    <td class="py-3.5 px-6 font-medium text-blue-600">{{ $report['total_leads'] }}</td>
                                    <td class="py-3.5 px-6 font-medium text-emerald-600">{{ $report['closed_leads'] }}</td>
                                    <td class="py-3.5 px-6 font-medium text-purple-600">{{ $report['completed_tasks'] }}</td>
                                    <td class="py-3.5 px-6 text-gray-600">{{ $report['period'] }}</td>
                                    <td class="py-3.5 px-6 text-center">
                                        <a href="{{ route('reports.download', ['id' => $report['id']]) }}" class="text-[#7A0C16] font-bold hover:underline text-xs inline-flex items-center space-x-1">
                                            <i class="ti ti-download"></i>
                                            <span>Download</span>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                            @endforelse
                        </x-datatable>

                    @else
                        <!-- Task / Lead / All Reports Table Structure -->
                        <x-datatable
                            :headers="['Title', 'Type', 'Status', 'Created At', 'Generated By', 'Actions']"
                            empty-message="No reports found. Try adjusting your filters."
                            empty-icon="ti ti-chart-bar"
                        >
                            @forelse($reports as $report)
                                @php
                                    $status = strtolower($report['status'] ?? '');
                                    $statusClasses = match(true) {
                                        str_contains($status, 'new')         => 'bg-blue-100 text-blue-800 border-blue-200',
                                        str_contains($status, 'open')        => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                                        str_contains($status, 'progress')    => 'bg-sky-100 text-sky-800 border-sky-200',
                                        str_contains($status, 'completed')  => 'bg-green-100 text-green-800 border-green-200',
                                        str_contains($status, 'closed')     => 'bg-gray-100 text-gray-800 border-gray-200',
                                        str_contains($status, 'pending')    => 'bg-amber-100 text-amber-800 border-amber-200',
                                        default                             => 'bg-gray-100 text-gray-700 border-gray-200',
                                    };
                                @endphp
                                <tr class="hover:bg-gray-50/80 transition border-b border-gray-100 text-sm">
                                    <td class="py-3.5 px-6 font-semibold text-gray-900">{{ $report['title'] }}</td>
                                    <td class="py-3.5 px-6">
                                        <span class="px-2.5 py-1 {{ ($report['type'] ?? '') === 'task' ? 'bg-purple-100 text-purple-800' : 'bg-amber-100 text-amber-800' }} rounded-md text-xs font-bold uppercase">
                                            {{ $report['type'] ?? 'N/A' }}
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-6">
                                        <span class="px-2 py-1 rounded-md text-xs font-semibold border capitalize {{ $statusClasses }}">
                                            {{ strtolower($report['status'] ?? '') === 'new' ? 'Open' : ($report['status'] ?? 'N/A') }}
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-6 text-gray-600">{{ $report['period'] }}</td>
                                    <td class="py-3.5 px-6 font-medium text-gray-800">{{ $report['generated_by'] }}</td>
                                    <td class="py-3.5 px-6 text-center">
                                        <a href="{{ route('reports.download', ['id' => $report['id']]) }}" class="text-[#7A0C16] font-bold hover:underline text-xs inline-flex items-center space-x-1">
                                            <i class="ti ti-download"></i>
                                            <span>Download</span>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                            @endforelse
                        </x-datatable>
                    @endif

                    <!-- Pagination Links -->
                    @if($reports->hasPages())
                        <div class="mt-4 px-4 py-3 border-t border-gray-100">
                            {{ $reports->appends(request()->query())->links() }}
                        </div>
                    @endif

                </x-card>
            </div>
        </div>

    </div>

@endsection