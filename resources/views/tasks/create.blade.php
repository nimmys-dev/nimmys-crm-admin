@extends('layouts.app')

@section('content')

<div class="container py-4">

    <div class="card shadow-sm border-0">

        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0">Create Task</h4>
        </div>

        <div class="card-body">

            <form action="{{ route('tasks.store') }}" method="POST">
                @csrf

                {{-- ========================================================= --}}
                {{-- ROW 1 : TASK + ASSIGN TO --}}
                {{-- ========================================================= --}}

                <div class="row">

                    {{-- Task --}}
                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Task <span class="text-danger">*</span>
                        </label>

                        <input type="text"
                               name="title"
                               value="{{ old('title') }}"
                               class="form-control @error('title') is-invalid @enderror"
                               placeholder="Enter task">

                        @error('title')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror

                    </div>


                    {{-- Assign To --}}
                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Assign To <span class="text-danger">*</span>
                        </label>

                        <select name="assigned_to"
                                class="form-select @error('assigned_to') is-invalid @enderror">

                            <option value="">Select User</option>

                            @foreach($users as $user)

                                <option value="{{ $user->id }}"
                                    @selected(old('assigned_to') == $user->id)>
                                    {{ $user->name }}
                                </option>

                            @endforeach

                        </select>

                        @error('assigned_to')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror

                    </div>

                </div>


                {{-- ========================================================= --}}
                {{-- ROW 2 : APPROVE TO + TASK TYPE --}}
                {{-- ========================================================= --}}

                <div class="row">

                    {{-- Approve To --}}
                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Approve To <span class="text-danger">*</span>
                        </label>

                        <select name="approved_by"
                                class="form-select @error('approved_by') is-invalid @enderror">

                            <option value="">Select Approver</option>

                            @foreach($users as $user)

                                <option value="{{ $user->id }}"
                                    @selected(old('approved_by') == $user->id)>
                                    {{ $user->name }}
                                </option>

                            @endforeach

                        </select>

                        @error('approved_by')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror

                    </div>


                    {{-- Task Type --}}
                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Task Type <span class="text-danger">*</span>
                        </label>

                        <select name="task_type"
                                id="task_type"
                                class="form-select @error('task_type') is-invalid @enderror">

                            <option value="">Select Task Type</option>

                            <option value="daily"
                                @selected(old('task_type') == 'daily')>
                                Daily
                            </option>

                            <option value="weekly"
                                @selected(old('task_type') == 'weekly')>
                                Weekly
                            </option>

                            <option value="monthly"
                                @selected(old('task_type') == 'monthly')>
                                Monthly
                            </option>

                            <option value="quarterly"
                                @selected(old('task_type') == 'quarterly')>
                                Quarterly
                            </option>

                        </select>

                        @error('task_type')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror

                    </div>

                </div>


                {{-- ========================================================= --}}
                {{-- DAILY --}}
                {{-- ========================================================= --}}

                <div id="daily_fields" class="task-fields">

                    <div class="row">

                        {{-- Start Time --}}
                        <div class="col-md-6 mb-3">

                            <label class="form-label">
                                Start Time <span class="text-danger">*</span>
                            </label>

                            <input type="time"
                                   name="start_time"
                                   value="{{ old('start_time') }}"
                                   class="form-control @error('start_time') is-invalid @enderror">

                            @error('start_time')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror

                        </div>


                        {{-- End Time --}}
                        <div class="col-md-6 mb-3">

                            <label class="form-label">
                                End Time <span class="text-danger">*</span>
                            </label>

                            <input type="time"
                                   name="end_time"
                                   value="{{ old('end_time') }}"
                                   class="form-control @error('end_time') is-invalid @enderror">

                            @error('end_time')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror

                        </div>

                    </div>

                </div>


                {{-- ========================================================= --}}
                {{-- WEEKLY --}}
                {{-- ========================================================= --}}

                <div id="weekly_fields" class="task-fields">

                    <div class="row">

                        {{-- Start Day --}}
                        <div class="col-md-6 mb-3">

                            <label class="form-label">
                                Start Day <span class="text-danger">*</span>
                            </label>

                            <select name="week_start_day"
                                    class="form-select @error('week_start_day') is-invalid @enderror">

                                <option value="">
                                    Select Start Day
                                </option>

                                @foreach([
                                    'monday',
                                    'tuesday',
                                    'wednesday',
                                    'thursday',
                                    'friday',
                                    'saturday',
                                    'sunday'
                                ] as $day)

                                    <option value="{{ $day }}"
                                        @selected(old('week_start_day') == $day)>
                                        {{ ucfirst($day) }}
                                    </option>

                                @endforeach

                            </select>

                            @error('week_start_day')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror

                        </div>


                        {{-- End Day --}}
                        <div class="col-md-6 mb-3">

                            <label class="form-label">
                                End Day <span class="text-danger">*</span>
                            </label>

                            <select name="week_end_day"
                                    class="form-select @error('week_end_day') is-invalid @enderror">

                                <option value="">
                                    Select End Day
                                </option>

                                @foreach([
                                    'monday',
                                    'tuesday',
                                    'wednesday',
                                    'thursday',
                                    'friday',
                                    'saturday',
                                    'sunday'
                                ] as $day)

                                    <option value="{{ $day }}"
                                        @selected(old('week_end_day') == $day)>
                                        {{ ucfirst($day) }}
                                    </option>

                                @endforeach

                            </select>

                            @error('week_end_day')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror

                        </div>

                    </div>

                </div>


                {{-- ========================================================= --}}
                {{-- MONTHLY --}}
                {{-- ========================================================= --}}

                <div id="monthly_fields" class="task-fields">

                    <div class="mb-3">

                        <label class="form-label">
                            Select Monthly Date Range
                            <span class="text-danger">*</span>
                        </label>

                        <div id="monthly-calendar"></div>

                        {{-- Hidden fields --}}
                        <input type="hidden"
                            name="monthly_start_date"
                            id="monthly_start_date"
                            value="{{ old('monthly_start_date') }}">

                        <input type="hidden"
                            name="monthly_end_date"
                            id="monthly_end_date"
                            value="{{ old('monthly_end_date') }}">

                        @error('monthly_start_date')
                            <div class="text-danger mt-1">
                                {{ $message }}
                            </div>
                        @enderror

                        @error('monthly_end_date')
                            <div class="text-danger mt-1">
                                {{ $message }}
                            </div>
                        @enderror

                        <div class="mt-2">
                            <strong>Selected:</strong>
                            <span id="selected-monthly-dates">
                                {{ old('monthly_start_date') && old('monthly_end_date')
                                    ? old('monthly_start_date') . ' - ' . old('monthly_end_date')
                                    : 'No dates selected' }}
                            </span>
                        </div>

                    </div>

                </div>


                {{-- ========================================================= --}}
                {{-- QUARTERLY --}}
                {{-- ========================================================= --}}

                <div id="quarterly_fields" class="task-fields">

                    <div class="row">

                        {{-- Quarter --}}
                        <div class="col-md-4 mb-3">

                            <label class="form-label">
                                Quarter <span class="text-danger">*</span>
                            </label>

                            <select name="quarter"
                                    id="quarter"
                                    class="form-select @error('quarter') is-invalid @enderror">

                                <option value="">
                                    Select Quarter
                                </option>

                                <option value="q1"
                                    @selected(old('quarter') == 'q1')>
                                    Q1 - April, May, June
                                </option>

                                <option value="q2"
                                    @selected(old('quarter') == 'q2')>
                                    Q2 - July, August, September
                                </option>

                                <option value="q3"
                                    @selected(old('quarter') == 'q3')>
                                    Q3 - October, November, December
                                </option>

                                <option value="q4"
                                    @selected(old('quarter') == 'q4')>
                                    Q4 - January, February, March
                                </option>

                            </select>

                            @error('quarter')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror

                        </div>


                        {{-- Start Date --}}
                        <div class="col-md-4 mb-3">

                            <label class="form-label">
                                Start Date <span class="text-danger">*</span>
                            </label>

                            <input type="date"
                                   name="quarter_start_date"
                                   id="quarter_start_date"
                                   value="{{ old('quarter_start_date') }}"
                                   class="form-control @error('quarter_start_date') is-invalid @enderror">

                            @error('quarter_start_date')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror

                        </div>


                        {{-- End Date --}}
                        <div class="col-md-4 mb-3">

                            <label class="form-label">
                                End Date <span class="text-danger">*</span>
                            </label>

                            <input type="date"
                                   name="quarter_end_date"
                                   id="quarter_end_date"
                                   value="{{ old('quarter_end_date') }}"
                                   class="form-control @error('quarter_end_date') is-invalid @enderror">

                            @error('quarter_end_date')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror

                        </div>

                    </div>

                </div>


                {{-- ========================================================= --}}
                {{-- DESCRIPTION --}}
                {{-- ========================================================= --}}

                <div class="mb-4">

                    <label class="form-label">
                        Description
                    </label>

                    <textarea name="description"
                              rows="4"
                              class="form-control @error('description') is-invalid @enderror"
                              placeholder="Enter description">{{ old('description') }}</textarea>

                    @error('description')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror

                </div>


                {{-- ========================================================= --}}
                {{-- BUTTONS --}}
                {{-- ========================================================= --}}

                <div class="d-flex justify-content-end gap-2">

                    <a href="{{ route('tasks.index') }}"
                       class="btn btn-light">
                        Cancel
                    </a>

                    <button type="submit"
                            class="btn btn-danger">

                        <i class="ti ti-check"></i>
                        Create Task

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>


{{-- ========================================================= --}}
{{-- SCRIPT --}}
{{-- ========================================================= --}}

<script>
document.addEventListener('DOMContentLoaded', function () {

    const taskType = document.getElementById('task_type');

    const dailyFields = document.getElementById('daily_fields');
    const weeklyFields = document.getElementById('weekly_fields');
    const monthlyFields = document.getElementById('monthly_fields');
    const quarterlyFields = document.getElementById('quarterly_fields');


    /*
    |--------------------------------------------------------------------------
    | Hide All Task Fields
    |--------------------------------------------------------------------------
    */

    function hideAllFields() {

        dailyFields.style.display = 'none';
        weeklyFields.style.display = 'none';
        monthlyFields.style.display = 'none';
        quarterlyFields.style.display = 'none';

    }


    /*
    |--------------------------------------------------------------------------
    | Show Selected Task Fields
    |--------------------------------------------------------------------------
    */

    function showFields(type) {

        hideAllFields();

        if (type === 'daily') {
            dailyFields.style.display = 'block';
        }

        if (type === 'weekly') {
            weeklyFields.style.display = 'block';
        }

        if (type === 'monthly') {
            monthlyFields.style.display = 'block';
        }

        if (type === 'quarterly') {
            quarterlyFields.style.display = 'block';
        }

    }


    /*
    |--------------------------------------------------------------------------
    | Task Type Change
    |--------------------------------------------------------------------------
    */

    taskType.addEventListener('change', function () {

        showFields(this.value);

    });


    /*
    |--------------------------------------------------------------------------
    | Show Old Value After Validation Error
    |--------------------------------------------------------------------------
    */

    showFields(taskType.value);


    /*
    |--------------------------------------------------------------------------
    | Quarterly Date
    |--------------------------------------------------------------------------
    */

    const quarter = document.getElementById('quarter');

    const quarterStart =
        document.getElementById('quarter_start_date');

    const quarterEnd =
        document.getElementById('quarter_end_date');


    quarter.addEventListener('change', function () {

        let year = new Date().getFullYear();

        switch (this.value) {

            /*
            |--------------------------------------------------------------------------
            | Q1 - April to June
            |--------------------------------------------------------------------------
            */

            case 'q1':

                quarterStart.value =
                    `${year}-04-01`;

                quarterEnd.value =
                    `${year}-06-30`;

                break;


            /*
            |--------------------------------------------------------------------------
            | Q2 - July to September
            |--------------------------------------------------------------------------
            */

            case 'q2':

                quarterStart.value =
                    `${year}-07-01`;

                quarterEnd.value =
                    `${year}-09-30`;

                break;


            /*
            |--------------------------------------------------------------------------
            | Q3 - October to December
            |--------------------------------------------------------------------------
            */

            case 'q3':

                quarterStart.value =
                    `${year}-10-01`;

                quarterEnd.value =
                    `${year}-12-31`;

                break;


            /*
            |--------------------------------------------------------------------------
            | Q4 - January to March
            |--------------------------------------------------------------------------
            */

            case 'q4':

                quarterStart.value =
                    `${year + 1}-01-01`;

                quarterEnd.value =
                    `${year + 1}-03-31`;

                break;


            default:

                quarterStart.value = '';
                quarterEnd.value = '';

                break;
        }

    });

});
</script>
@push('scripts')

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<script>

document.addEventListener('DOMContentLoaded', function () {

    const startInput = document.getElementById('monthly_start_date');
    const endInput = document.getElementById('monthly_end_date');
    const selectedText = document.getElementById('selected-monthly-dates');

    flatpickr("#monthly-calendar", {

        inline: true,

        mode: "range",

        dateFormat: "Y-m-d",

        onChange: function(selectedDates, dateStr, instance) {

            if (selectedDates.length === 1) {

                startInput.value =
                    instance.formatDate(
                        selectedDates[0],
                        "Y-m-d"
                    );

                endInput.value = "";

                selectedText.textContent =
                    startInput.value;

            }

            if (selectedDates.length === 2) {

                startInput.value =
                    instance.formatDate(
                        selectedDates[0],
                        "Y-m-d"
                    );

                endInput.value =
                    instance.formatDate(
                        selectedDates[1],
                        "Y-m-d"
                    );

                selectedText.textContent =
                    startInput.value + " - " + endInput.value;
            }
        }

    });

});

</script>
@push('styles')
<link rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
@endpush

@endpush

@endsection