<footer class="pc-footer">
    <div class="footer-wrapper container-fluid mx-10">
        <div class="grid grid-cols-12 gap-1.5">
            <div class="col-span-12 sm:col-span-6 my-1">
                <p class="m-0">&copy; {{ now()->year }} {{ config('app.name') }}</p>
            </div>
            <div class="col-span-12 sm:col-span-6 my-1 justify-self-end">
                <p class="m-0">v{{ config('app.version', '1.0.0') }}</p>
            </div>
        </div>
    </div>
</footer>
