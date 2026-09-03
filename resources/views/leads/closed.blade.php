@extends('layouts.app')

@section('page-title', 'Closed Leads')

@section('page-actions')
    <a href="{{ route('leads.index') }}" class="btn btn-secondary">
        <i class="ti ti-arrow-left"></i>
        Back to Leads
    </a>
@endsection

@section('content')

<div class="card">

    <div class="card-header">
        <h3 class="card-title">
            <i class="ti ti-lock"></i>
            Closed Leads
        </h3>
    </div>

    <div class="card-body">

        <div class="table-responsive">

            <table class="table table-vcenter card-table">

                <thead>
                    <tr>
                        <th>Lead</th>
                        <th>Phone</th>
                        <th>Assign To</th>
                        <th>Status</th>
                        <th>Next Follow-up</th>
                        <th>Remarks</th>
                        <th>Created</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>

                <tbody>

                    @forelse($leads as $lead)

                        <tr>

                            {{-- Lead --}}
                            <td>
                                <a href="{{ route('leads.show', $lead) }}"
                                   class="text-decoration-none">

                                    <strong>
                                        {{ $lead->name }}
                                    </strong>

                                    @if($lead->company)
                                        <div class="text-muted small">
                                            {{ $lead->company }}
                                        </div>
                                    @endif

                                </a>
                            </td>


                            {{-- Phone --}}
                            <td>
                                {{ $lead->phone ?? '—' }}
                            </td>


                            {{-- Assigned To --}}
                            <td>
                                {{ $lead->owner?->name ?? '—' }}
                            </td>


                            {{-- Status --}}
                            <td>
                                <span class="badge bg-secondary">
                                    Closed
                                </span>
                            </td>


                            {{-- Next Follow-up --}}
                            <td>
                                @if($lead->latestCall?->next_followup_date)
                                    {{ \Carbon\Carbon::parse($lead->latestCall->next_followup_date)->format('d M Y') }}
                                @else
                                    —
                                @endif
                            </td>


                            {{-- Remarks --}}
                            <td>
                                {{ $lead->remarks ?? '—' }}
                            </td>


                            {{-- Created --}}
                            <td>
                                {{ $lead->created_at?->format('d M Y') }}
                            </td>


                            {{-- Actions --}}
                            <td class="text-end">

                                <a href="{{ route('leads.show', $lead) }}"
                                   class="btn btn-sm btn-icon"
                                   title="View">

                                    <i class="ti ti-eye"></i>

                                </a>

                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td colspan="8" class="text-center py-4">
                                <div class="text-muted">
                                    No closed leads found.
                                </div>
                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </div>

    @if($leads->hasPages())
        <div class="card-footer">
            {{ $leads->links() }}
        </div>
    @endif

</div>

@endsection