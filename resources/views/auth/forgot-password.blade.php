@extends('layouts.app')

@section('content')

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-5">

            <div class="card">
                <div class="card-body">

                    <h4 class="mb-3">Forgot Password</h4>

                    <p class="text-muted">
                        Enter your email address and we will send you
                        a password reset link.
                    </p>

                    @if (session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="alert alert-danger">
                            {{ $errors->first() }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('password.email') }}">
                        @csrf

                        <div class="mb-3">
                            <label for="email" class="form-label">
                                Email Address
                            </label>

                            <input
                                type="email"
                                name="email"
                                id="email"
                                class="form-control"
                                value="{{ old('email') }}"
                                required
                                autofocus
                            >
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            Send Password Reset Link
                        </button>
                    </form>

                    <div class="text-center mt-3">
                        <a href="{{ route('login') }}">
                            Back to Login
                        </a>
                    </div>

                </div>
            </div>

        </div>
    </div>
</div>

@endsection