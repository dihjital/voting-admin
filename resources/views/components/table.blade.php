<div class="overflow-x-auto sm:-mx-6 lg:-mx-8">
    <div class="py-2 inline-block min-w-full sm:px-6 lg:px-8">
        <div class="overflow-hidden">
            <div class="flex flex-col">
                <div class="-my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                    <div class="py-2 align-middle inline-block min-w-full sm:px-6 lg:px-8">
                        <div class="shadow overflow-hidden sm:rounded-lg">
                            <table class="min-w-full text-sm text-left text-gray-500 dark:text-gray-400"
                                   {{ $attributes }}
                            >
                                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                <tr>
                                    {{ $head }}
                                </tr>
                                </thead>
                                <tbody>
                                {{ $body }}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>