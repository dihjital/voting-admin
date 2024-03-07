@props(['id'])

<div wire:ignore>

<div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
    <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
        <path d="M20 4a2 2 0 0 0-2-2h-2V1a1 1 0 0 0-2 0v1h-3V1a1 1 0 0 0-2 0v1H6V1a1 1 0 0 0-2 0v1H2a2 2 0 0 0-2 2v2h20V4ZM0 18a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V8H0v10Zm5-8h10a1 1 0 0 1 0 2H5a1 1 0 0 1 0-2Z"/>
    </svg>
</div>

<input
    datepicker
    x-data
    x-ref="{{ $id }}"
    x-init="
        new Datepicker($refs.{{ $id }}, {
            autohide: true,
            orientation: 'right',
            format: 'mm/dd/yyyy',
            clearBtn: true,
            todayBtn: true,
            todayHighlight: true,
        });
        $refs.{{ $id }}.addEventListener('changeDate', (event) => {
            event.detail.date
                ? $dispatch('input', event.detail.date.toDateString())
                : $dispatch('input', null);
        });
    "
    id="{{ $id }}"
    type="text"
    placeholder="{{ __('Select date') }}"
    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full ps-10 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
    {{ $attributes }}
>

</div>