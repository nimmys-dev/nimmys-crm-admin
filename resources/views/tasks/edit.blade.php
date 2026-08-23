@extends('layouts.app')

@section('content')

<div class="container py-4">

    <div class="card shadow-sm border-0">

        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0">Edit Task</h4>
        </div>

        <div class="card-body">

            <form action="{{ route('tasks.update', $task->id) }}" method="POST">
                @csrf
                @method('PUT')

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
                               value="{{ old('title', $task->title) }}"
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
                                    @selected(old('assigned_to', $task->assigned_to) == $user->id)>
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
                                    @selected(old('approved_by', $task->approved_by) == $user->id)>
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
                                @selected(old('task_type', $task->task_type) == 'daily')>
                                Daily
                            </option>

                            <option value="weekly"
                                @selected(old('task_type', $task->task_type) == 'weekly')>
                                Weekly
                            </option>

                            <option value="monthly"
                                @selected(old('task_type', $task->task_type) == 'monthly')>
                                Monthly
                            </option>

                            <option value="quarterly"
                                @selected(old('task_type', $task->task_type) == 'quarterly')>
                                Quarterly
                            </option>
                            <option value="yearly"
                                @selected(old('task_type', $task->task_type) == 'yearly')>
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
                                   value="{{ old('start_time', $task->start_time) }}"
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
                                   value="{{ old('end_time', $task->end_time) }}"
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

                                <option value="">Select Start Day</option>

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
                                        @selected(old('week_start_day', $task->week_start_day) == $day)>
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

                                <option value="">Select End Day</option>

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
                                        @selected(old('week_end_day', $task->week_end_day) == $day)>
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
                            Monthly Date Range
                            <span class="text-danger">*</span>
                        </label>

                        <div class="custom-calendar">

                            {{-- Calendar Header --}}
                            <div class="calendar-header">

                                <button type="button"
                                        class="calendar-nav"
                                        id="prev-month">
                                    <i class="ti ti-chevron-left"></i>
                                </button>

                                <div id="calendar-month-year"></div>

                                <button type="button"
                                        class="calendar-nav"
                                        id="next-month">
                                    <i class="ti ti-chevron-right"></i>
                                </button>

                            </div>


                            {{-- Week Days --}}
                            <div class="calendar-weekdays">

                                <div>Sun</div>
                                <div>Mon</div>
                                <div>Tue</div>
                                <div>Wed</div>
                                <div>Thu</div>
                                <div>Fri</div>
                                <div>Sat</div>

                            </div>


                            {{-- Dates --}}
                            <div id="calendar-days"
                                class="calendar-days">
                            </div>

                        </div>


                        {{-- Selected Dates --}}
                        <div class="selected-date-info mt-3">

                            <div>
                                <strong>Start Date:</strong>

                                <span id="selected-start-date">
                                    {{ old('monthly_start_date', $task->monthly_start_date?->format('Y-m-d')) ?: '-' }}
                                </span>
                            </div>

                            <div>
                                <strong>End Date:</strong>

                                <span id="selected-end-date">
                                    {{ old('monthly_end_date', $task->monthly_end_date?->format('Y-m-d')) ?: '-' }}
                                </span>
                            </div>

                        </div>


                        {{-- Hidden inputs --}}
                        <input type="hidden"
                            name="monthly_start_date"
                            id="monthly_start_date"
                            value="{{ old('monthly_start_date', $task->monthly_start_date?->format('Y-m-d')) }}">

                        <input type="hidden"
                            name="monthly_end_date"
                            id="monthly_end_date"
                            value="{{ old('monthly_end_date', $task->monthly_end_date?->format('Y-m-d')) }}">


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

                    </div>

                </div> -->
<div id="monthly_fields" class="task-fields">

    <div class="mb-3">

        <label class="form-label">
            Monthly Date Range
            <span class="text-danger">*</span>
        </label>

        {{-- Selected dates --}}
        <div class="selected-date-info mb-2">

            <div>
                <strong>Start Date:</strong>
                <span id="selected-start-date">
                    {{ old('monthly_start_date', $task->monthly_start_date?->format('Y-m-d')) ?: '-' }}
                </span>
            </div>

            <div>
                <strong>End Date:</strong>
                <span id="selected-end-date">
                    {{ old('monthly_end_date', $task->monthly_end_date?->format('Y-m-d')) ?: '-' }}
                </span>
            </div>

        </div>

        {{-- Open calendar button --}}
        <button type="button"
                class="btn btn-primary mb-3"
                id="open-monthly-calendar">
            <i class="ti ti-calendar"></i>
            Select / Change Dates
        </button>


        {{-- Calendar wrapper --}}
        <div id="monthly-calendar-wrapper" style="display: none;">

            <div class="custom-calendar">

                {{-- Calendar Header --}}
                <div class="calendar-header">

                    <button type="button"
                            class="calendar-nav"
                            id="prev-month">
                        <i class="ti ti-chevron-left"></i>
                    </button>

                    <div id="calendar-month-year"></div>

                    <button type="button"
                            class="calendar-nav"
                            id="next-month">
                        <i class="ti ti-chevron-right"></i>
                    </button>

                </div>


                {{-- Week Days --}}
                <div class="calendar-weekdays">
                    <div>Sun</div>
                    <div>Mon</div>
                    <div>Tue</div>
                    <div>Wed</div>
                    <div>Thu</div>
                    <div>Fri</div>
                    <div>Sat</div>
                </div>


                {{-- Dates --}}
                <div id="calendar-days"
                     class="calendar-days">
                </div>


                {{-- Calendar Footer --}}
                <div class="calendar-footer mt-3 text-end">

                    <button type="button"
                            class="btn btn-secondary"
                            id="cancel-monthly-calendar">
                        Cancel
                    </button>

                    <button type="button"
                            class="btn btn-success"
                            id="ok-monthly-calendar"
                            disabled>
                        <i class="ti ti-check"></i>
                        OK
                    </button>

                </div>

            </div>

        </div>


        {{-- Hidden inputs --}}
        <input type="hidden"
               name="monthly_start_date"
               id="monthly_start_date"
               value="{{ old('monthly_start_date', $task->monthly_start_date?->format('Y-m-d')) }}">

        <input type="hidden"
               name="monthly_end_date"
               id="monthly_end_date"
               value="{{ old('monthly_end_date', $task->monthly_end_date?->format('Y-m-d')) }}">


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

                                <option value="">Select Quarter</option>

                                <option value="q1"
                                    @selected(old('quarter', $task->quarter) == 'q1')>
                                    Q1 - April, May, June
                                </option>

                                <option value="q2"
                                    @selected(old('quarter', $task->quarter) == 'q2')>
                                    Q2 - July, August, September
                                </option>

                                <option value="q3"
                                    @selected(old('quarter', $task->quarter) == 'q3')>
                                    Q3 - October, November, December
                                </option>

                                <option value="q4"
                                    @selected(old('quarter', $task->quarter) == 'q4')>
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
                                   value="{{ old('quarter_start_date', $task->quarter_start_date) }}"
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
                                   value="{{ old('quarter_end_date', $task->quarter_end_date) }}"
                                   class="form-control @error('quarter_end_date') is-invalid @enderror">

                            @error('quarter_end_date')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror

                        </div>

                    </div>

                </div>

                <!-- yearly -->
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
                                value="{{ old('yearly_start_date', $task->yearly_start_date?->format('Y-m-d')) }}"
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
                                value="{{ old('yearly_end_date', $task->yearly_end_date?->format('Y-m-d')) }}"
                                class="form-control @error('yearly_end_date') is-invalid @enderror">

                            @error('yearly_end_date')
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
                              placeholder="Enter description">{{ old('description', $task->description) }}</textarea>

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
                            class="btn btn-primary">

                        <i class="ti ti-check"></i>
                        Update Task

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
// document.addEventListener('DOMContentLoaded', function () {

//     const taskType = document.getElementById('task_type');

//     const dailyFields = document.getElementById('daily_fields');
//     const weeklyFields = document.getElementById('weekly_fields');
//     const monthlyFields = document.getElementById('monthly_fields');
//     const quarterlyFields = document.getElementById('quarterly_fields');

//     function hideAllFields() {
//         dailyFields.style.display = 'none';
//         weeklyFields.style.display = 'none';
//         monthlyFields.style.display = 'none';
//         quarterlyFields.style.display = 'none';
//     }

//     function showFields(type) {

//         hideAllFields();

//         if (type === 'daily') {
//             dailyFields.style.display = 'block';
//         }

//         if (type === 'weekly') {
//             weeklyFields.style.display = 'block';
//         }

//         if (type === 'monthly') {
//             monthlyFields.style.display = 'block';
//         }

//         if (type === 'quarterly') {
//             quarterlyFields.style.display = 'block';
//         }
//     }

//     taskType.addEventListener('change', function () {
//         showFields(this.value);
//     });

//     // Show existing task type fields
//     showFields(taskType.value);


//     /*
//     |--------------------------------------------------------------------------
//     | Quarterly Date
//     |--------------------------------------------------------------------------
//     */

//     const quarter = document.getElementById('quarter');

//     const quarterStart =
//         document.getElementById('quarter_start_date');

//     const quarterEnd =
//         document.getElementById('quarter_end_date');


//     quarter.addEventListener('change', function () {

//         let year = new Date().getFullYear();

//         switch (this.value) {

//             case 'q1':

//                 quarterStart.value = `${year}-04-01`;
//                 quarterEnd.value = `${year}-06-30`;

//                 break;

//             case 'q2':

//                 quarterStart.value = `${year}-07-01`;
//                 quarterEnd.value = `${year}-09-30`;

//                 break;

//             case 'q3':

//                 quarterStart.value = `${year}-10-01`;
//                 quarterEnd.value = `${year}-12-31`;

//                 break;

//             case 'q4':

//                 quarterStart.value = `${year + 1}-01-01`;
//                 quarterEnd.value = `${year + 1}-03-31`;

//                 break;

//             default:

//                 quarterStart.value = '';
//                 quarterEnd.value = '';

//                 break;
//         }

//     });

// });
document.addEventListener('DOMContentLoaded', function () {

    const taskType = document.getElementById('task_type');

    const dailyFields = document.getElementById('daily_fields');
    const weeklyFields = document.getElementById('weekly_fields');
    const monthlyFields = document.getElementById('monthly_fields');
    const quarterlyFields = document.getElementById('quarterly_fields');
    const yearlyFields = document.getElementById('yearly_fields'); // Yearly fields ചേർത്തു

    function hideAllFields() {
        if (dailyFields) dailyFields.style.display = 'none';
        if (weeklyFields) weeklyFields.style.display = 'none';
        if (monthlyFields) monthlyFields.style.display = 'none';
        if (quarterlyFields) quarterlyFields.style.display = 'none';
        if (yearlyFields) yearlyFields.style.display = 'none'; // ഇവിടെ Hide ചെയ്യുക
    }

    function showFields(type) {

        hideAllFields();

        if (type === 'daily' && dailyFields) {
            dailyFields.style.display = 'block';
        }

        if (type === 'weekly' && weeklyFields) {
            weeklyFields.style.display = 'block';
        }

        if (type === 'monthly' && monthlyFields) {
            monthlyFields.style.display = 'block';
        }

        if (type === 'quarterly' && quarterlyFields) {
            quarterlyFields.style.display = 'block';
        }

        if (type === 'yearly' && yearlyFields) {
            yearlyFields.style.display = 'block'; // Yearly ആകുമ്പോൾ മാത്രം Show ചെയ്യും
        }
    }

    taskType.addEventListener('change', function () {
        showFields(this.value);
    });

    // Page load ചെയ്യുമ്പോൾ നിലവിലുള്ള task type അനുസരിച്ച് ഫീൽഡുകൾ കാണിക്കുക
    showFields(taskType.value);


    /*
    |--------------------------------------------------------------------------
    | Quarterly Date Handling
    |--------------------------------------------------------------------------
    */
    const quarter = document.getElementById('quarter');
    const quarterStart = document.getElementById('quarter_start_date');
    const quarterEnd = document.getElementById('quarter_end_date');

    if (quarter) {
        quarter.addEventListener('change', function () {
            let year = new Date().getFullYear();

            switch (this.value) {
                case 'q1':
                    quarterStart.value = `${year}-04-01`;
                    quarterEnd.value = `${year}-06-30`;
                    break;
                case 'q2':
                    quarterStart.value = `${year}-07-01`;
                    quarterEnd.value = `${year}-09-30`;
                    break;
                case 'q3':
                    quarterStart.value = `${year}-10-01`;
                    quarterEnd.value = `${year}-12-31`;
                    break;
                case 'q4':
                    quarterStart.value = `${year + 1}-01-01`;
                    quarterEnd.value = `${year + 1}-03-31`;
                    break;
                default:
                    quarterStart.value = '';
                    quarterEnd.value = '';
                    break;
            }
        });
    }
});
</script>
<script>

// document.addEventListener('DOMContentLoaded', function () {

//     const calendarDays =
//         document.getElementById('calendar-days');

//     const monthYear =
//         document.getElementById('calendar-month-year');

//     const prevButton =
//         document.getElementById('prev-month');

//     const nextButton =
//         document.getElementById('next-month');

//     const startInput =
//         document.getElementById('monthly_start_date');

//     const endInput =
//         document.getElementById('monthly_end_date');

//     const selectedStart =
//         document.getElementById('selected-start-date');

//     const selectedEnd =
//         document.getElementById('selected-end-date');


//     /*
//     |--------------------------------------------------------------------------
//     | Existing values from edit page
//     |--------------------------------------------------------------------------
//     */

//     let startDate = startInput.value
//         ? new Date(startInput.value + 'T00:00:00')
//         : null;

//     let endDate = endInput.value
//         ? new Date(endInput.value + 'T00:00:00')
//         : null;


//     /*
//     |--------------------------------------------------------------------------
//     | Open calendar on existing start date
//     |--------------------------------------------------------------------------
//     */

//     let currentDate = startDate
//         ? new Date(startDate)
//         : new Date();


//     /*
//     |--------------------------------------------------------------------------
//     | Format date
//     |--------------------------------------------------------------------------
//     */

//     function formatDate(date) {

//         const year = date.getFullYear();

//         const month = String(
//             date.getMonth() + 1
//         ).padStart(2, '0');

//         const day = String(
//             date.getDate()
//         ).padStart(2, '0');

//         return `${year}-${month}-${day}`;
//     }


//     /*
//     |--------------------------------------------------------------------------
//     | Render Calendar
//     |--------------------------------------------------------------------------
//     */

//     function renderCalendar() {

//         calendarDays.innerHTML = '';


//         const year =
//             currentDate.getFullYear();

//         const month =
//             currentDate.getMonth();


//         const monthName =
//             currentDate.toLocaleString('default', {
//                 month: 'long'
//             });


//         monthYear.textContent =
//             `${monthName} ${year}`;


//         /*
//         |--------------------------------------------------------------------------
//         | First day of month
//         |--------------------------------------------------------------------------
//         */

//         const firstDay =
//             new Date(year, month, 1).getDay();


//         /*
//         |--------------------------------------------------------------------------
//         | Number of days
//         |--------------------------------------------------------------------------
//         */

//         const daysInMonth =
//             new Date(year, month + 1, 0).getDate();


//         /*
//         |--------------------------------------------------------------------------
//         | Empty cells
//         |--------------------------------------------------------------------------
//         */

//         for (let i = 0; i < firstDay; i++) {

//             const empty =
//                 document.createElement('div');

//             empty.className =
//                 'calendar-day empty';

//             calendarDays.appendChild(empty);
//         }


//         /*
//         |--------------------------------------------------------------------------
//         | Dates
//         |--------------------------------------------------------------------------
//         */

//         for (let day = 1; day <= daysInMonth; day++) {

//             const date =
//                 new Date(year, month, day);

//             const dateString =
//                 formatDate(date);


//             const button =
//                 document.createElement('div');

//             button.className =
//                 'calendar-day';

//             button.textContent =
//                 day;


//             /*
//             |--------------------------------------------------------------------------
//             | Start Date
//             |--------------------------------------------------------------------------
//             */

//             if (
//                 startDate &&
//                 dateString === formatDate(startDate)
//             ) {

//                 button.classList.add(
//                     'start-date'
//                 );
//             }


//             /*
//             |--------------------------------------------------------------------------
//             | End Date
//             |--------------------------------------------------------------------------
//             */

//             if (
//                 endDate &&
//                 dateString === formatDate(endDate)
//             ) {

//                 button.classList.add(
//                     'end-date'
//                 );
//             }


//             /*
//             |--------------------------------------------------------------------------
//             | Single selected date
//             |--------------------------------------------------------------------------
//             */

//             if (
//                 startDate &&
//                 endDate &&
//                 dateString === formatDate(startDate) &&
//                 dateString === formatDate(endDate)
//             ) {

//                 button.classList.add(
//                     'selected'
//                 );
//             }


//             /*
//             |--------------------------------------------------------------------------
//             | Range
//             |--------------------------------------------------------------------------
//             */

//             if (
//                 startDate &&
//                 endDate &&
//                 date > startDate &&
//                 date < endDate
//             ) {

//                 button.classList.add(
//                     'in-range'
//                 );
//             }


//             /*
//             |--------------------------------------------------------------------------
//             | Click Date
//             |--------------------------------------------------------------------------
//             */

//             button.addEventListener(
//                 'click',
//                 function () {

//                     /*
//                     | First click
//                     */

//                     if (
//                         !startDate ||
//                         (startDate && endDate)
//                     ) {

//                         startDate =
//                             new Date(date);

//                         endDate = null;

//                     }

//                     /*
//                     | Second click
//                     */

//                     else {

//                         if (date < startDate) {

//                             endDate =
//                                 new Date(startDate);

//                             startDate =
//                                 new Date(date);

//                         } else {

//                             endDate =
//                                 new Date(date);
//                         }

//                     }


//                     /*
//                     |--------------------------------------------------------------------------
//                     | Update hidden inputs
//                     |--------------------------------------------------------------------------
//                     */

//                     startInput.value =
//                         startDate
//                             ? formatDate(startDate)
//                             : '';

//                     endInput.value =
//                         endDate
//                             ? formatDate(endDate)
//                             : '';


//                     /*
//                     |--------------------------------------------------------------------------
//                     | Update displayed values
//                     |--------------------------------------------------------------------------
//                     */

//                     selectedStart.textContent =
//                         startDate
//                             ? formatDate(startDate)
//                             : '-';

//                     selectedEnd.textContent =
//                         endDate
//                             ? formatDate(endDate)
//                             : '-';


//                     renderCalendar();

//                 }
//             );


//             calendarDays.appendChild(button);
//         }

//     }


//     /*
//     |--------------------------------------------------------------------------
//     | Previous Month
//     |--------------------------------------------------------------------------
//     */

//     prevButton.addEventListener('click', function () {

//         currentDate.setMonth(
//             currentDate.getMonth() - 1
//         );

//         renderCalendar();

//     });


//     /*
//     |--------------------------------------------------------------------------
//     | Next Month
//     |--------------------------------------------------------------------------
//     */

//     nextButton.addEventListener('click', function () {

//         currentDate.setMonth(
//             currentDate.getMonth() + 1
//         );

//         renderCalendar();

//     });


//     /*
//     |--------------------------------------------------------------------------
//     | Initial Render
//     |--------------------------------------------------------------------------
//     */

//     renderCalendar();

// });

</script>
<script>
document.addEventListener('DOMContentLoaded', function () {

    const calendarWrapper =
        document.getElementById('monthly-calendar-wrapper');

    const calendarDays =
        document.getElementById('calendar-days');

    const monthYear =
        document.getElementById('calendar-month-year');

    const prevButton =
        document.getElementById('prev-month');

    const nextButton =
        document.getElementById('next-month');

    const openButton =
        document.getElementById('open-monthly-calendar');

    const okButton =
        document.getElementById('ok-monthly-calendar');

    const cancelButton =
        document.getElementById('cancel-monthly-calendar');

    const startInput =
        document.getElementById('monthly_start_date');

    const endInput =
        document.getElementById('monthly_end_date');

    const selectedStart =
        document.getElementById('selected-start-date');

    const selectedEnd =
        document.getElementById('selected-end-date');


    /*
    |--------------------------------------------------------------------------
    | Existing values
    |--------------------------------------------------------------------------
    */

    let startDate = startInput.value
        ? new Date(startInput.value + 'T00:00:00')
        : null;

    let endDate = endInput.value
        ? new Date(endInput.value + 'T00:00:00')
        : null;


    /*
    |--------------------------------------------------------------------------
    | Current Calendar Month
    |--------------------------------------------------------------------------
    */

    let currentDate = startDate
        ? new Date(startDate)
        : new Date();


    /*
    |--------------------------------------------------------------------------
    | Temporary dates
    |--------------------------------------------------------------------------
    */

    let tempStartDate = startDate
        ? new Date(startDate)
        : null;

    let tempEndDate = endDate
        ? new Date(endDate)
        : null;


    /*
    |--------------------------------------------------------------------------
    | Format Date
    |--------------------------------------------------------------------------
    */

    function formatDate(date) {

        const year = date.getFullYear();

        const month = String(
            date.getMonth() + 1
        ).padStart(2, '0');

        const day = String(
            date.getDate()
        ).padStart(2, '0');

        return `${year}-${month}-${day}`;
    }


    /*
    |--------------------------------------------------------------------------
    | Open Calendar
    |--------------------------------------------------------------------------
    */

    openButton.addEventListener('click', function () {

        tempStartDate = startDate
            ? new Date(startDate)
            : null;

        tempEndDate = endDate
            ? new Date(endDate)
            : null;

        currentDate = tempStartDate
            ? new Date(tempStartDate)
            : new Date();

        calendarWrapper.style.display = 'block';

        updateOkButton();

        renderCalendar();

    });


    /*
    |--------------------------------------------------------------------------
    | Close Calendar
    |--------------------------------------------------------------------------
    */

    function closeCalendar() {

        calendarWrapper.style.display = 'none';

    }


    /*
    |--------------------------------------------------------------------------
    | OK Button
    |--------------------------------------------------------------------------
    */

    okButton.addEventListener('click', function () {

        if (!tempStartDate || !tempEndDate) {
            return;
        }

        // Save selected dates
        startDate = new Date(tempStartDate);
        endDate = new Date(tempEndDate);


        // Update hidden fields
        startInput.value =
            formatDate(startDate);

        endInput.value =
            formatDate(endDate);


        // Update displayed dates
        selectedStart.textContent =
            formatDate(startDate);

        selectedEnd.textContent =
            formatDate(endDate);


        // Close calendar
        closeCalendar();

    });


    /*
    |--------------------------------------------------------------------------
    | Cancel Button
    |--------------------------------------------------------------------------
    */

    cancelButton.addEventListener('click', function () {

        // Restore original dates
        tempStartDate = startDate
            ? new Date(startDate)
            : null;

        tempEndDate = endDate
            ? new Date(endDate)
            : null;

        closeCalendar();

        renderCalendar();

    });


    /*
    |--------------------------------------------------------------------------
    | Enable / Disable OK
    |--------------------------------------------------------------------------
    */

    function updateOkButton() {

        if (tempStartDate && tempEndDate) {

            okButton.disabled = false;

        } else {

            okButton.disabled = true;

        }

    }


    /*
    |--------------------------------------------------------------------------
    | Render Calendar
    |--------------------------------------------------------------------------
    */

    function renderCalendar() {

        calendarDays.innerHTML = '';

        const year =
            currentDate.getFullYear();

        const month =
            currentDate.getMonth();


        const monthName =
            currentDate.toLocaleString('default', {
                month: 'long'
            });


        monthYear.textContent =
            `${monthName} ${year}`;


        const firstDay =
            new Date(year, month, 1).getDay();


        const daysInMonth =
            new Date(year, month + 1, 0).getDate();


        /*
        |--------------------------------------------------------------------------
        | Empty cells
        |--------------------------------------------------------------------------
        */

        for (let i = 0; i < firstDay; i++) {

            const empty =
                document.createElement('div');

            empty.className =
                'calendar-day empty';

            calendarDays.appendChild(empty);

        }


        /*
        |--------------------------------------------------------------------------
        | Dates
        |--------------------------------------------------------------------------
        */

        for (
            let day = 1;
            day <= daysInMonth;
            day++
        ) {

            const date =
                new Date(year, month, day);

            const dateString =
                formatDate(date);


            const button =
                document.createElement('div');

            button.className =
                'calendar-day';

            button.textContent =
                day;


            /*
            |--------------------------------------------------------------------------
            | Start Date
            |--------------------------------------------------------------------------
            */

            if (
                tempStartDate &&
                dateString === formatDate(tempStartDate)
            ) {

                button.classList.add('start-date');

            }


            /*
            |--------------------------------------------------------------------------
            | End Date
            |--------------------------------------------------------------------------
            */

            if (
                tempEndDate &&
                dateString === formatDate(tempEndDate)
            ) {

                button.classList.add('end-date');

            }


            /*
            |--------------------------------------------------------------------------
            | Range
            |--------------------------------------------------------------------------
            */

            if (
                tempStartDate &&
                tempEndDate &&
                date > tempStartDate &&
                date < tempEndDate
            ) {

                button.classList.add('in-range');

            }


            /*
            |--------------------------------------------------------------------------
            | Date Click
            |--------------------------------------------------------------------------
            */

            button.addEventListener('click', function (event) {

                event.stopPropagation();


                /*
                | First date
                */

                if (
                    !tempStartDate ||
                    (tempStartDate && tempEndDate)
                ) {

                    tempStartDate =
                        new Date(date);

                    tempEndDate = null;

                }


                /*
                | Second date
                */

                else {

                    if (date < tempStartDate) {

                        tempEndDate =
                            new Date(tempStartDate);

                        tempStartDate =
                            new Date(date);

                    } else {

                        tempEndDate =
                            new Date(date);

                    }

                }


                updateOkButton();

                renderCalendar();

            });


            calendarDays.appendChild(button);

        }

    }


    /*
    |--------------------------------------------------------------------------
    | Previous Month
    |--------------------------------------------------------------------------
    */

    prevButton.addEventListener('click', function (event) {

        event.stopPropagation();

        currentDate.setMonth(
            currentDate.getMonth() - 1
        );

        renderCalendar();

    });


    /*
    |--------------------------------------------------------------------------
    | Next Month
    |--------------------------------------------------------------------------
    */

    nextButton.addEventListener('click', function (event) {

        event.stopPropagation();

        currentDate.setMonth(
            currentDate.getMonth() + 1
        );

        renderCalendar();

    });


    /*
    |--------------------------------------------------------------------------
    | Click Outside Calendar
    |--------------------------------------------------------------------------
    */

    document.addEventListener('click', function (event) {

        if (
            calendarWrapper.style.display === 'block' &&
            !calendarWrapper.contains(event.target) &&
            event.target !== openButton
        ) {

            closeCalendar();

        }

    });


    /*
    |--------------------------------------------------------------------------
    | Initial Calendar Render
    |--------------------------------------------------------------------------
    */

    renderCalendar();

});
</script>
@push('styles')
<link rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
@endpush
@push('styles')
<style>
    .custom-calendar {
        width: 100%;
        max-width: 420px;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        padding: 15px;
        background: #fff;
    }

    .calendar-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 15px;
    }

    #calendar-month-year {
        font-size: 16px;
        font-weight: 600;
    }

    .calendar-nav {
        width: 35px;
        height: 35px;
        border: 1px solid #ddd;
        background: #fff;
        border-radius: 6px;
        cursor: pointer;
    }

    .calendar-weekdays,
    .calendar-days {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 5px;
    }

    .calendar-weekdays div {
        text-align: center;
        font-size: 12px;
        font-weight: 600;
        color: #777;
        padding: 8px 0;
    }

    .calendar-day {
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 6px;
        cursor: pointer;
        font-size: 14px;
    }

    .calendar-day:hover {
        background: #f1f5f9;
    }

    .calendar-day.empty {
        cursor: default;
    }

    .calendar-day.in-range {
        background: #eeeafd;
        color: #7367f0;
    }

    .calendar-day.start-date,
    .calendar-day.end-date,
    .calendar-day.selected {
        background: #7367f0;
        color: #fff;
    }

    .selected-date-info {
        display: flex;
        gap: 30px;
        font-size: 14px;
    }
</style>
@endpush
@endsection