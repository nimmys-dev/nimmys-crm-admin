@once
    @push('styles')
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tom-select@2.4.3/dist/css/tom-select.css">
    @endpush

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/tom-select@2.4.3/dist/js/tom-select.complete.min.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                document.querySelectorAll('select[data-user-select]').forEach(function (select) {
                    if (select.tomselect) {
                        return;
                    }

                    new TomSelect(select, {
                        create: false,
                        allowEmptyOption: true,
                        placeholder: 'Search and select a user',
                        searchField: ['text'],
                        maxOptions: null,
                    });
                });
            });
        </script>
    @endpush
@endonce
