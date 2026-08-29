@extends('layouts.app')

@section('content')

<div class="container py-4">

    <div class="card shadow-sm border-0">

        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0">Create Task</h4>
        </div>

        <div class="card-body">

            <form action="{{ route('tasks.store') }}" method="POST" id="createTaskForm">
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
                            <option value="yearly"
                                @selected(old('task_type') == 'yearly')>
                                Yearly
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

                <!-- <div id="monthly_fields" class="task-fields">

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

                </div> -->

                <div id="monthly_fields" class="task-fields">
                    <div class="mb-3">
                        <label class="form-label">
                            Select Monthly Date Range <span class="text-danger">*</span>
                        </label>

                        <!-- Calendar Open Button -->
                        <div>
                            <button type="button" class="btn btn-outline-primary mb-2" id="toggle-calendar-btn">
                                <i class="ti ti-calendar"></i> Pick Date Range
                            </button>
                        </div>

                        <!-- Inline Calendar Wrapper (Initially Hidden) -->
                        <div id="calendar-wrapper" style="display: none;" class="mt-2 mb-2">
                            <div id="monthly-calendar"></div>
                        </div>

                        {{-- Hidden Input Fields --}}
                        <input type="hidden" name="monthly_start_date" id="monthly_start_date" value="{{ old('monthly_start_date') }}">
                        <input type="hidden" name="monthly_end_date" id="monthly_end_date" value="{{ old('monthly_end_date') }}">

                        @error('monthly_start_date')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                        @error('monthly_end_date')
                            <div class="text-danger mt-1">{{ $message }}</div>
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

                <!-- <div id="quarterly_fields" class="task-fields">

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

                </div> -->
                <div id="quarterly_fields" class="task-fields">

                    <label class="form-label">
                        Quarterly Schedule <span class="text-danger">*</span>
                    </label>

                    <div id="quarterly-container">

                        <div class="quarterly-row border rounded p-3 mb-3">
                            
                            <!-- Main Grid Wrapper (2 Columns) -->
                            <div class="quarterly-grid-wrapper">

                                {{-- Left Side Div --}}
                                <div class="quarterly-left-col">
                                    <label class="form-label">Quarter</label>
                                    <select name="quarters[0][quarter]" class="form-select quarter-select">
                                        <option value="">Select Quarter</option>
                                        <option value="q1">Q1 - April, May, June</option>
                                        <option value="q2">Q2 - July, August, September</option>
                                        <option value="q3">Q3 - October, November, December</option>
                                        <option value="q4">Q4 - January, February, March</option>
                                    </select>
                                </div>

                                {{-- Right Side Div --}}
                                <div class="quarterly-right-col">
                                    <div class="date-field">
                                        <label class="form-label">Start Date</label>
                                        <input type="date" name="quarters[0][start_date]" class="form-control quarter-start">
                                    </div>

                                    <div class="date-field">
                                        <label class="form-label">End Date</label>
                                        <input type="date" name="quarters[0][end_date]" class="form-control quarter-end">
                                    </div>

                                    <div class="action-field">
                                        <button type="button" class="btn btn-danger remove-quarter">
                                            <i class="ti ti-trash"></i>
                                        </button>
                                    </div>
                                </div>

                            </div>

                        </div>

                    </div>

                    {{-- Add Quarter --}}
                    <button type="button" id="add-quarter" class="btn btn-primary">
                        <i class="ti ti-plus"></i> Add Quarter
                    </button>

                </div>

                {{-- ========================================================= --}}
                {{-- YEARLY --}}
                {{-- ========================================================= --}}

                <div id="yearly_fields" class="task-fields">

                    <div class="row">

                        {{-- Start Date --}}
                        <div class="col-md-6 mb-3">

                            <label class="form-label">
                                Start Date <span class="text-danger">*</span>
                            </label>

                            <input type="date"
                                name="yearly_start_date"
                                id="yearly_start_date"
                                value="{{ old('yearly_start_date') }}"
                                class="form-control @error('yearly_start_date') is-invalid @enderror">

                            @error('yearly_start_date')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror

                        </div>


                        {{-- End Date --}}
                        <div class="col-md-6 mb-3">

                            <label class="form-label">
                                End Date <span class="text-danger">*</span>
                            </label>

                            <input type="date"
                                name="yearly_end_date"
                                id="yearly_end_date"
                                value="{{ old('yearly_end_date') }}"
                                class="form-control @error('yearly_end_date') is-invalid @enderror">

                            @error('yearly_end_date')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror

                        </div>

                    </div>

                </div>

{{-- Repeat Mode --}}
<div class="col-md-6 mb-3">
    <label class="form-label">
        Repeat Mode
    </label>

    <div class="form-check form-switch mt-2">
        <input
            type="checkbox"
            name="repeat_mode"
            value="1"
            id="repeat_mode"
            class="form-check-input"
            @checked(old('repeat_mode'))
        >

        <label
            class="form-check-label"
            for="repeat_mode"
        >
            Enable Repeat Mode
        </label>
    </div>

    <small class="text-muted">
        Automatically create the next task after completion.
    </small>

    @error('repeat_mode')
        <div class="text-danger mt-1">
            {{ $message }}
        </div>
    @enderror
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
                            class="btn btn-danger" id="createTaskBtn">

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

    const dailyFields =
        document.getElementById('daily_fields');

    const weeklyFields =
        document.getElementById('weekly_fields');

    const monthlyFields =
        document.getElementById('monthly_fields');

    const quarterlyFields =
        document.getElementById('quarterly_fields');

    const yearlyFields =
        document.getElementById('yearly_fields');


    /*
    |--------------------------------------------------------------------------
    | Hide All Task Fields
    |--------------------------------------------------------------------------
    */

    function hideAllFields() {

        if (dailyFields) {
            dailyFields.style.display = 'none';
        }

        if (weeklyFields) {
            weeklyFields.style.display = 'none';
        }

        if (monthlyFields) {
            monthlyFields.style.display = 'none';
        }

        if (quarterlyFields) {
            quarterlyFields.style.display = 'none';
        }

        if (yearlyFields) {
            yearlyFields.style.display = 'none';
        }

    }


    /*
    |--------------------------------------------------------------------------
    | Show Selected Task Fields
    |--------------------------------------------------------------------------
    */

    function showFields(type) {

        hideAllFields();

        switch (type) {

            case 'daily':

                if (dailyFields) {
                    dailyFields.style.display = 'block';
                }

                break;


            case 'weekly':

                if (weeklyFields) {
                    weeklyFields.style.display = 'block';
                }

                break;


            case 'monthly':

                if (monthlyFields) {
                    monthlyFields.style.display = 'block';
                }

                break;


            case 'quarterly':

                if (quarterlyFields) {
                    quarterlyFields.style.display = 'block';
                }

                break;


            case 'yearly':

                if (yearlyFields) {
                    yearlyFields.style.display = 'block';
                }

                break;

        }

    }


    /*
    |--------------------------------------------------------------------------
    | Task Type Change
    |--------------------------------------------------------------------------
    */

    if (taskType) {

        taskType.addEventListener('change', function () {

            showFields(this.value);

        });

    }


    /*
    |--------------------------------------------------------------------------
    | Show Existing / Old Task Type
    |--------------------------------------------------------------------------
    */

    if (taskType) {

        showFields(taskType.value);

    }


    /*
    |--------------------------------------------------------------------------
    | QUARTERLY
    |--------------------------------------------------------------------------
    */

    const quarter =
        document.getElementById('quarter');

    const quarterStart =
        document.getElementById('quarter_start_date');

    const quarterEnd =
        document.getElementById('quarter_end_date');


    if (
        quarter &&
        quarterStart &&
        quarterEnd
    ) {

        quarter.addEventListener('change', function () {

            const year =
                new Date().getFullYear();


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

    }


    /*
    |--------------------------------------------------------------------------
    | YEARLY
    |--------------------------------------------------------------------------
    */

    const yearlyStart =
        document.getElementById('yearly_start_date');

    const yearlyEnd =
        document.getElementById('yearly_end_date');


    /*
    |--------------------------------------------------------------------------
    | Yearly Start Date Change
    |--------------------------------------------------------------------------
    |
    | Example:
    |
    | Start: 2026-04-01
    | End:   2026-05-01
    |
    | Start: 2026-08-15
    | End:   2026-09-15
    |
    |--------------------------------------------------------------------------
    */

    if (
        yearlyStart &&
        yearlyEnd
    ) {

        yearlyStart.addEventListener('change', function () {

            if (!this.value) {

                yearlyEnd.value = '';

                return;

            }


            const startDate =
                new Date(this.value + 'T00:00:00');


            const startYear =
                startDate.getFullYear();

            const startMonth =
                startDate.getMonth();

            const startDay =
                startDate.getDate();


            /*
            |--------------------------------------------------------------------------
            | Calculate next month
            |--------------------------------------------------------------------------
            */

            const endDate =
                new Date(
                    startYear,
                    startMonth + 1,
                    startDay
                );


            /*
            |--------------------------------------------------------------------------
            | Don't allow next year
            |--------------------------------------------------------------------------
            */

            if (
                endDate.getFullYear() !== startYear
            ) {

                yearlyEnd.value = '';

                return;

            }


            /*
            |--------------------------------------------------------------------------
            | Format End Date
            |--------------------------------------------------------------------------
            */

            const endYear =
                endDate.getFullYear();

            const endMonth =
                String(
                    endDate.getMonth() + 1
                ).padStart(2, '0');

            const endDay =
                String(
                    endDate.getDate()
                ).padStart(2, '0');


            yearlyEnd.value =
                `${endYear}-${endMonth}-${endDay}`;

        });

    }


    /*
    |--------------------------------------------------------------------------
    | Yearly Initial Values
    |--------------------------------------------------------------------------
    |
    | Only set default dates when creating a new task.
    | Existing edit values will NOT be overwritten.
    |--------------------------------------------------------------------------
    */

    if (
        taskType &&
        taskType.value === 'yearly' &&
        yearlyStart &&
        yearlyEnd
    ) {

        if (
            !yearlyStart.value &&
            !yearlyEnd.value
        ) {

            const year =
                new Date().getFullYear();


            yearlyStart.value =
                `${year}-01-01`;

            yearlyEnd.value =
                `${year}-02-01`;

        }

    }

});
</script>
@push('scripts')

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<script>

// document.addEventListener('DOMContentLoaded', function () {

//     const startInput = document.getElementById('monthly_start_date');
//     const endInput = document.getElementById('monthly_end_date');
//     const selectedText = document.getElementById('selected-monthly-dates');

//     flatpickr("#monthly-calendar", {

//         inline: true,

//         mode: "range",

//         dateFormat: "Y-m-d",

//         onChange: function(selectedDates, dateStr, instance) {

//             if (selectedDates.length === 1) {

//                 startInput.value =
//                     instance.formatDate(
//                         selectedDates[0],
//                         "Y-m-d"
//                     );

//                 endInput.value = "";

//                 selectedText.textContent =
//                     startInput.value;

//             }

//             if (selectedDates.length === 2) {

//                 startInput.value =
//                     instance.formatDate(
//                         selectedDates[0],
//                         "Y-m-d"
//                     );

//                 endInput.value =
//                     instance.formatDate(
//                         selectedDates[1],
//                         "Y-m-d"
//                     );

//                 selectedText.textContent =
//                     startInput.value + " - " + endInput.value;
//             }
//         }

//     });

// });

document.addEventListener('DOMContentLoaded', function () {

    const startInput =
        document.getElementById('monthly_start_date');

    const endInput =
        document.getElementById('monthly_end_date');

    const selectedText =
        document.getElementById('selected-monthly-dates');

    const calendarWrapper =
        document.getElementById('calendar-wrapper');

    const toggleBtn =
        document.getElementById('toggle-calendar-btn');


    /*
    |--------------------------------------------------------------------------
    | Confirmed Dates
    |--------------------------------------------------------------------------
    */

    let confirmedStart =
        startInput.value || '';

    let confirmedEnd =
        endInput.value || '';


    /*
    |--------------------------------------------------------------------------
    | Flatpickr
    |--------------------------------------------------------------------------
    */

    const fp = flatpickr('#monthly-calendar', {

        inline: true,

        mode: 'range',

        dateFormat: 'Y-m-d',


        /*
        |--------------------------------------------------------------------------
        | Existing Dates - Edit Page
        |--------------------------------------------------------------------------
        */

        defaultDate:
            confirmedStart && confirmedEnd
                ? [confirmedStart, confirmedEnd]
                : (
                    confirmedStart
                        ? [confirmedStart]
                        : []
                ),


        /*
        |--------------------------------------------------------------------------
        | Calendar Open
        |--------------------------------------------------------------------------
        */

        onOpen: function () {

            // Save current confirmed values
            confirmedStart =
                startInput.value || '';

            confirmedEnd =
                endInput.value || '';

        },


        /*
        |--------------------------------------------------------------------------
        | Date Selection
        |--------------------------------------------------------------------------
        */

        onChange: function (
            selectedDates,
            dateStr,
            instance
        ) {

            /*
            |--------------------------------------------------------------
            | First Date
            |--------------------------------------------------------------
            */

            if (selectedDates.length === 1) {

                const start =
                    instance.formatDate(
                        selectedDates[0],
                        'Y-m-d'
                    );

                startInput.value =
                    start;

                endInput.value =
                    '';

                selectedText.textContent =
                    start;
            }


            /*
            |--------------------------------------------------------------
            | Start + End Date
            |--------------------------------------------------------------
            */

            if (selectedDates.length === 2) {

                const start =
                    instance.formatDate(
                        selectedDates[0],
                        'Y-m-d'
                    );

                const end =
                    instance.formatDate(
                        selectedDates[1],
                        'Y-m-d'
                    );

                startInput.value =
                    start;

                endInput.value =
                    end;

                selectedText.textContent =
                    start + ' - ' + end;
            }

        },


        /*
        |--------------------------------------------------------------------------
        | Calendar Ready
        |--------------------------------------------------------------------------
        */

        onReady: function (
            selectedDates,
            dateStr,
            instance
        ) {

            /*
            |--------------------------------------------------------------
            | Button Container
            |--------------------------------------------------------------
            */

            const buttonContainer =
                document.createElement('div');

            buttonContainer.className =
                'd-flex gap-2 mt-2';


            /*
            |--------------------------------------------------------------
            | Cancel Button
            |--------------------------------------------------------------
            */

            const cancelBtn =
                document.createElement('button');

            cancelBtn.type =
                'button';

            cancelBtn.className =
                'btn btn-sm w-50';

            cancelBtn.style.backgroundColor =
                '#6c757d';

            cancelBtn.style.borderColor =
                '#6c757d';

            cancelBtn.style.color =
                '#fff';

            cancelBtn.innerHTML =
                '<i class="ti ti-x"></i> Cancel';


            /*
            |--------------------------------------------------------------
            | OK Button
            |--------------------------------------------------------------
            */

            const okBtn =
                document.createElement('button');

            okBtn.type =
                'button';

            okBtn.className =
                'btn btn-sm w-50';

            okBtn.style.backgroundColor =
                '#1de9b6';

            okBtn.style.borderColor =
                '#1de9b6';

            okBtn.style.color =
                '#fff';

            okBtn.innerHTML =
                '<i class="ti ti-check"></i> OK';


            /*
            |--------------------------------------------------------------------------
            | OK Button Click
            |--------------------------------------------------------------------------
            */

            okBtn.addEventListener(
                'click',
                function () {

                    /*
                    |----------------------------------------------------------
                    | Validate Dates
                    |----------------------------------------------------------
                    */

                    if (
                        !startInput.value ||
                        !endInput.value
                    ) {

                        alert(
                            'Please select start date and end date.'
                        );

                        return;
                    }


                    /*
                    |----------------------------------------------------------
                    | Save Confirmed Dates
                    |----------------------------------------------------------
                    */

                    confirmedStart =
                        startInput.value;

                    confirmedEnd =
                        endInput.value;


                    /*
                    |----------------------------------------------------------
                    | Hide Calendar
                    |----------------------------------------------------------
                    */

                    calendarWrapper.style.display =
                        'none';

                }
            );


            /*
            |--------------------------------------------------------------------------
            | Cancel Button Click
            |--------------------------------------------------------------------------
            */

            cancelBtn.addEventListener(
                'click',
                function () {

                    /*
                    |----------------------------------------------------------
                    | Restore Previous Dates
                    |----------------------------------------------------------
                    */

                    startInput.value =
                        confirmedStart;

                    endInput.value =
                        confirmedEnd;


                    /*
                    |----------------------------------------------------------
                    | Restore Calendar Selection
                    |----------------------------------------------------------
                    */

                    if (
                        confirmedStart &&
                        confirmedEnd
                    ) {

                        selectedText.textContent =
                            confirmedStart +
                            ' - ' +
                            confirmedEnd;

                        fp.setDate(
                            [
                                confirmedStart,
                                confirmedEnd
                            ],
                            false
                        );

                    } else {

                        startInput.value =
                            '';

                        endInput.value =
                            '';

                        selectedText.textContent =
                            'No dates selected';

                        fp.clear();
                    }


                    /*
                    |----------------------------------------------------------
                    | Hide Calendar
                    |----------------------------------------------------------
                    */

                    calendarWrapper.style.display =
                        'none';

                }
            );


            /*
            |--------------------------------------------------------------------------
            | Add Buttons
            |--------------------------------------------------------------------------
            */

            buttonContainer.appendChild(
                cancelBtn
            );

            buttonContainer.appendChild(
                okBtn
            );

            instance.calendarContainer.appendChild(
                buttonContainer
            );

        }

    });


    /*
    |--------------------------------------------------------------------------
    | Pick Date Range Button
    |--------------------------------------------------------------------------
    */

    toggleBtn.addEventListener(
        'click',
        function () {

            if (
                calendarWrapper.style.display ===
                'none'
            ) {

                /*
                |--------------------------------------------------------------
                | Open Calendar
                |--------------------------------------------------------------
                */

                calendarWrapper.style.display =
                    'block';


                /*
                |--------------------------------------------------------------
                | Save Current Values
                |--------------------------------------------------------------
                */

                confirmedStart =
                    startInput.value || '';

                confirmedEnd =
                    endInput.value || '';


                /*
                |--------------------------------------------------------------
                | Set Existing Selection
                |--------------------------------------------------------------
                */

                if (
                    confirmedStart &&
                    confirmedEnd
                ) {

                    fp.setDate(
                        [
                            confirmedStart,
                            confirmedEnd
                        ],
                        false
                    );

                } else if (confirmedStart) {

                    fp.setDate(
                        [confirmedStart],
                        false
                    );

                } else {

                    fp.clear();
                }

            } else {

                /*
                |--------------------------------------------------------------
                | Close Calendar
                |--------------------------------------------------------------
                */

                calendarWrapper.style.display =
                    'none';

            }

        }
    );

});
</script>
<script>
    document.addEventListener('DOMContentLoaded', function () {

    document.querySelectorAll('input[type="time"]').forEach(function (input) {

        input.addEventListener('click', function () {
            if (typeof this.showPicker === 'function') {
                this.showPicker();
            }
        });

    });

});
</script>

<!-- Quartly multiple selection -->
<script>
document.addEventListener('DOMContentLoaded', function () {

    const container = document.getElementById('quarterly-container');
    const addButton = document.getElementById('add-quarter');

    let quarterIndex = 1;

    /*
    |--------------------------------------------------------------------------
    | Add Quarter
    |--------------------------------------------------------------------------
    */

    addButton.addEventListener('click', function () {

        const row = document.createElement('div');

        row.className = 'quarterly-row border rounded p-3 mb-3';

        row.innerHTML = `
            <div class="quarterly-grid-wrapper">

                {{-- Left Side: Quarter Dropdown --}}
                <div class="quarterly-left-col">
                    <label class="form-label">Quarter</label>
                    <select name="quarters[${quarterIndex}][quarter]" class="form-select quarter-select">
                        <option value="">Select Quarter</option>
                        <option value="q1">Q1 - April, May, June</option>
                        <option value="q2">Q2 - July, August, September</option>
                        <option value="q3">Q3 - October, November, December</option>
                        <option value="q4">Q4 - January, February, March</option>
                    </select>
                </div>

                {{-- Right Side: Start Date, End Date & Delete Button --}}
                <div class="quarterly-right-col">
                    <div class="date-field">
                        <label class="form-label">Start Date</label>
                        <input type="date" 
                               name="quarters[${quarterIndex}][start_date]" 
                               class="form-control quarter-start">
                    </div>

                    <div class="date-field">
                        <label class="form-label">End Date</label>
                        <input type="date" 
                               name="quarters[${quarterIndex}][end_date]" 
                               class="form-control quarter-end">
                    </div>

                    <div class="action-field">
                        <button type="button" class="btn btn-danger remove-quarter">
                            <i class="ti ti-trash"></i>
                        </button>
                    </div>
                </div>

            </div>
        `;

        container.appendChild(row);
        quarterIndex++;
    });

    /*
    |--------------------------------------------------------------------------
    | Remove Quarter
    |--------------------------------------------------------------------------
    */

    container.addEventListener('click', function (event) {
        const removeButton = event.target.closest('.remove-quarter');

        if (removeButton) {
            const rows = container.querySelectorAll('.quarterly-row');

            // Keep at least one row
            if (rows.length > 1) {
                removeButton.closest('.quarterly-row').remove();
            }
        }
    });

    /*
    |--------------------------------------------------------------------------
    | Auto Set Dates Based On Quarter
    |--------------------------------------------------------------------------
    */

    container.addEventListener('change', function (event) {
        if (!event.target.classList.contains('quarter-select')) {
            return;
        }

        const row = event.target.closest('.quarterly-row');
        const startInput = row.querySelector('.quarter-start');
        const endInput = row.querySelector('.quarter-end');

        const year = new Date().getFullYear();

        switch (event.target.value) {
            case 'q1':
                startInput.value = `${year}-04-01`;
                endInput.value = `${year}-06-30`;
                break;

            case 'q2':
                startInput.value = `${year}-07-01`;
                endInput.value = `${year}-09-30`;
                break;

            case 'q3':
                startInput.value = `${year}-10-01`;
                endInput.value = `${year}-12-31`;
                break;

            case 'q4':
                startInput.value = `${year + 1}-01-01`;
                endInput.value = `${year + 1}-03-31`;
                break;

            default:
                startInput.value = '';
                endInput.value = '';
                break;
        }
    });

});
</script>

<script>

document.getElementById('createTaskForm').addEventListener('submit', function (e) {

    const button = document.getElementById('createTaskBtn');

    // Already submitted - prevent duplicate submission
    if (this.dataset.submitted === 'true') {
        e.preventDefault();
        return false;
    }

    // Mark form as submitted immediately
    this.dataset.submitted = 'true';

    // Disable button
    button.disabled = true;

    // Hide button immediately
    button.style.display = 'none';

});


</script>

@push('styles')
<link rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
@endpush

@endpush

@endsection