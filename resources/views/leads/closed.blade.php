@extends('layouts.app')

@section('page-title', 'Closed Leads')

@section('page-actions')
    <a href="{{ route('leads.index') }}" class="btn btn-secondary">
        <i class="ti ti-arrow-left"></i>
        Back to Leads
    </a>
@endsection

@section('content')
<style>
    .closed-leads-search {
    width: 100%;
    margin-bottom: 20px;
}

.search-wrapper {
    display: flex;
    align-items: center;
    gap: 8px;
    width: 100%;
}

/* Search input */
.search-input-wrapper {
    position: relative;
    width: 380px;
}

.search-input {
    height: 40px;
    padding-left: 40px;
    padding-right: 14px;

    border: 1px solid #d9dee5;
    border-radius: 6px;

    font-size: 14px;
    color: #1f2937;

    background-color: #fff;

    transition: all 0.2s ease;
}

.search-input::placeholder {
    color: #9aa4b2;
}

.search-input:focus {
    border-color: #206bc4;
    box-shadow: 0 0 0 2px rgba(32, 107, 196, 0.10);
    outline: none;
}

/* Search icon */
.search-icon {
    position: absolute;
    left: 13px;
    top: 50%;

    transform: translateY(-50%);

    color: #9aa4b2;
    font-size: 17px;

    z-index: 2;
}

/* Search button */
.search-btn {
    height: 40px;

    padding: 0 17px;

    border-radius: 6px;

    background: #206bc4;
    border: 1px solid #206bc4;

    color: #fff;

    font-size: 14px;
    font-weight: 500;

    display: inline-flex;
    align-items: center;
    justify-content: center;

    transition: all 0.2s ease;
}

.search-btn:hover {
    background: #1a5aa7;
    border-color: #1a5aa7;
    color: #fff;
}

/* Clear button */
.clear-btn {
    height: 40px;

    padding: 0 16px;

    border-radius: 6px;

    border: 1px solid #d9dee5;

    background: #fff;

    color: #6b7280;

    font-size: 14px;
    font-weight: 500;

    display: inline-flex;
    align-items: center;
    justify-content: center;

    transition: all 0.2s ease;
}

.clear-btn:hover {
    background: #f3f4f6;
    border-color: #cbd1d8;
    color: #374151;
}


/* Mobile */
@media (max-width: 576px) {

    .search-wrapper {
        flex-wrap: wrap;
    }

    .search-input-wrapper {
        width: 100%;
    }

    .search-btn,
    .clear-btn {
        flex: 1;
    }

}

</style>

<div class="card">

    <div class="card-header">
        <h3 class="card-title">
            <i class="ti ti-lock"></i>
            Closed Leads
        </h3>
    </div>

    <div class="card-body">
        <form method="GET"
            action="{{ route('leads.closed') }}"
            class="closed-leads-search">

            <div class="search-wrapper">

                {{-- Search Input --}}
                <div class="search-input-wrapper">

                    <i class="ti ti-search search-icon"></i>

                    <input
                        type="text"
                        name="search"
                        value="{{ $search ?? request('search') }}"
                        class="form-control search-input"
                        placeholder="Search lead name or assigned to..."
                    >

                </div>


                {{-- Search Button --}}
                <button type="submit" class="btn search-btn">
                    <i class="ti ti-search me-1"></i>
                    Search
                </button>


                {{-- Clear Button --}}
                @if(request('search'))

                    <a href="{{ route('leads.closed') }}"
                    class="btn clear-btn">
                        <i class="ti ti-x me-1"></i>
                        Clear
                    </a>

                @endif

            </div>

        </form>


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
                                {{ $lead->description ?? '—' }}
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