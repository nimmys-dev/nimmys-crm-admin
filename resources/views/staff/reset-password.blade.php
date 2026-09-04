@extends('layouts.app')

@section('content')

<div class="container-fluid">

    {{-- Page Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">Reset Password</h4>
            <p class="text-muted mb-0">
                Reset password for {{ $member->name }}
            </p>
        </div>

        <a href="{{ route('staff.index') }}"
           class="btn btn-secondary">
            <i class="ti ti-arrow-left me-1"></i>
            Back
        </a>
    </div>

    {{-- Reset Password Card --}}
    <div class="card">

        <div class="card-header">
            <h5 class="mb-0">
                <i class="ti ti-key me-1"></i>
                Reset Password
            </h5>
        </div>

        <div class="card-body">

            <div class="mb-4">
                <strong>Staff:</strong>
                {{ $member->name }}
                <br>

                <strong>Email:</strong>
                {{ $member->email }}
            </div>

            <form method="POST"
                  action="{{ route('staff.update-password', $member) }}"
                  id="resetPasswordForm">

                @csrf
                @method('PUT')

                {{-- New Password --}}
                <div class="mb-3">
                    <label for="password" class="form-label">
                        New Password <span class="text-danger">*</span>
                    </label>

                    <input type="password"
                           name="password"
                           id="password"
                           class="form-control @error('password') is-invalid @enderror"
                           placeholder="Enter new password"
                           required>

                    @error('password')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                {{-- Confirm Password --}}
                <div class="mb-4">
                    <label for="password_confirmation" class="form-label">
                        Confirm Password <span class="text-danger">*</span>
                    </label>

                    <input type="password"
                           name="password_confirmation"
                           id="password_confirmation"
                           class="form-control"
                           placeholder="Confirm new password"
                           required>
                </div>

                {{-- Buttons --}}
                <div class="d-flex gap-2">

                    <button type="submit"
                            id="resetPasswordBtn"
                            class="btn btn-primary">

                        <i class="ti ti-key me-1"></i>
                        Reset Password

                    </button>

                    <a href="{{ route('staff.index') }}"
                       class="btn btn-secondary">
                        Cancel
                    </a>

                </div>

            </form>

        </div>
    </div>

</div>

@endsection


<script>
document.getElementById('resetPasswordForm').addEventListener('submit', function (e) {

    const form = this;
    const button = document.getElementById('resetPasswordBtn');

    // Prevent multiple submissions
    if (form.dataset.submitted === 'true') {
        e.preventDefault();
        return false;
    }

    form.dataset.submitted = 'true';

    // Disable button & change text
    button.disabled = true;
    button.innerHTML = '<i class="ti ti-loader me-1"></i> Resetting...';
});
</script>