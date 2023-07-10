<th scope="col"
    {{ $attributes->merge(['class' => 'px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wide bg-gray-50 dark:bg-gray-700 dark:text-gray-400']) }}
    {{ $attributes }}
>
    {{ $slot }}
</th>