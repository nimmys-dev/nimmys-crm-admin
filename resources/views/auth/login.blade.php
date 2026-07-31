@extends('layouts.guest')

@section('title', 'Log in')

@section('content')

    <h4 class="text-center font-medium mb-4">Log in</h4>

    <x-alerts />

    <form method="POST" action="{{ route('login.store') }}">
        @csrf

        <div class="grid grid-cols-12 gap-4">
            <x-form.input
                name="email"
                type="email"
                label="Email address"
                col="col-span-12"
                autocomplete="username"
                autofocus
                required
            />

            <x-form.input
                name="password"
                type="password"
                label="Password"
                col="col-span-12"
                autocomplete="current-password"
                required
            />
        </div>

        <div class="flex mt-4 justify-between items-center flex-wrap">
            <div class="form-check">
                <input class="form-check-input input-primary" type="checkbox" name="remember" id="remember"
                    value="1" @checked(old('remember')) />
                <label class="form-check-label text-muted" for="remember">Remember me</label>
            </div>
        </div>

        <div class="mt-4 text-center">
            <x-button type="submit" class="mx-auto">Log in</x-button>
        </div>
    </form>

@endsection
