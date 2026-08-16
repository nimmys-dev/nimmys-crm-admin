@props([
    'id' => 'delete-modal',
    'action' => null,
    'title' => 'Delete record',
    'message' => 'This permanently removes the record. This cannot be undone.',
    'confirm' => 'Delete',
])

{{--
    Pair with a trigger:
    <x-button data-pc-toggle="modal" data-pc-target="#delete-shop-3" variant="danger" size="sm">Delete</x-button>
--}}

<div class="modal" id="{{ $id }}" role="dialog" aria-modal="true" aria-labelledby="{{ $id }}-title">
    <div class="modal-dialog">
        <div class="modal-content">

            <div class="modal-header">
                <h5 id="{{ $id }}-title">{{ $title }}</h5>
                <button type="button" class="modal-close" data-pc-modal-dismiss="#{{ $id }}" aria-label="Close">
                    <i class="ti ti-x"></i>
                </button>
            </div>

            <div class="modal-body">
                {{ filled(trim($slot)) ? $slot : $message }}
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-pc-modal-dismiss="#{{ $id }}">Cancel</button>

                <form method="POST" action="{{ $action }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">{{ $confirm }}</button>
                </form>
            </div>

        </div>
    </div>
</div>
