<div class="w-full p-4">

    <button type="button" wire:click="toggleCreateQuestionModal"
           class="fixed z-100 bottom-10 right-8 bg-blue-600 w-20 h-20 rounded-full drop-shadow-lg flex justify-center items-center text-white text-4xl hover:bg-blue-700 hover:drop-shadow-2xl hover:animate-bounce duration-300">
            <svg width="50" height="50" fill="currentColor" class="bi bi-plus" viewBox="0 0 16 16">
                <path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/>
            </svg>
    </button>

    @if($error_message)
        <p class="text-lg text-center font-medium text-red-500">{{ $error_message }}</p>
    @else
    <x-table>
        <x-slot name="head">
            <x-table.heading class="w-2/12">{{ __('Question number') }}</x-table.heading>
            <x-table.heading class="w-6/12">{{ __('Question text') }}</x-table.heading>
            <x-table.heading class="w-2/12">{{ __('Number of voting options') }}</x-table.heading>
            <x-table.heading class="w-2/12"></x-table.heading>
        </x-slot>
        <x-slot name="body">
            @forelse($questions as $q)
            <x-table.row wire:loading.class.delay="opacity-75" wire:key="row-{{ $q['id'] }}">
                <x-table.cell>{{ $q['id'] }}</x-table.cell>
                <x-table.cell>
                    <a href="/questions/{{ $q['id'] }}/votes">
                        {{ $q['question_text'] }}
                    </a>
                </x-table.cell>
                <x-table.cell>{{ $q['number_of_votes'] }}</x-table.cell>
                <x-table.cell class="text-right text-sm font-medium space-x-2">
                    <button type="button" wire:click="toggleUpdateQuestionModal({{ $q['id'] }})" class="px-3 py-3 bg-blue-500 hover:bg-blue-600 text-white text-xs rounded-md">
                        <i class="fas fa-edit fa-sm" aria-hidden="true" title="{{ __('Update') }}"></i>
                    </button>
                    <button type="button" wire:click="toggleDeleteQuestionModal({{ $q['id'] }})" class="px-3 py-3 bg-red-500 hover:bg-red-600 text-white text-xs rounded-md">
                        <i class="fas fa-trash fa-sm" aria-hidden="true" title="{{ __('Delete') }}"></i>
                    </button>
                </x-table.cell>
            </x-table.row>
            @empty
            <x-table.row wire:key="row-empty">
                <x-table.cell colspan="4" class="whitespace-nowrap">
                    <div class="flex justify-center items-center">
                        <span class="py-8 text-base text-center font-medium text-gray-400 uppercase">{{ __('There are no questions in the database') }} ...</span>
                    </div>
                </x-table.cell>
            </x-table.row>
            @endforelse
        </x-slot>
    </x-table>

    <div class="mt-4">
        @if(App\Http\Livewire\ShowQuestions::PAGINATING)
            {{ $questions->links() }}
        @endif
    </div>

    @endif

    <!-- Create New Question Modal -->
    <x-dialog-modal wire:model="new_question">
        <x-slot name="title">
            {{ __('Create Question') }}
        </x-slot>

        <x-slot name="content">
            {{ __('Please enter your question. This action will create a new question, and then you can add voting options to it.') }}

            <div class="mt-4" x-data="{}" x-on:confirming-question-create.window="setTimeout(() => $refs.question_text.focus(), 250)">
                <x-input type="text" class="mt-1 block w-3/4"
                            autocomplete=""
                            placeholder="{{ old('$question_text') }}"
                            x-ref="question_text"
                            wire:model.defer="question_text"
                            wire:keydown.enter="create" />

                <x-input-error for="question_text" class="mt-2" />
            </div>
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="$toggle('new_question')" wire:loading.attr="disabled">
                {{ __('Cancel') }}
            </x-secondary-button>

            <x-danger-button class="ml-3" wire:click="create" wire:loading.attr="disabled">
                {{ __('Create Question') }}
            </x-danger-button>
        </x-slot>
    </x-dialog-modal>

    <!-- Update Question Modal -->
    <x-dialog-modal wire:model="update_question">
        <x-slot name="title">
            {{ __('Update Question') }}
        </x-slot>

        <x-slot name="content">
            {{ __('Please enter your new text for the selected question') }}

            <div class="mt-4" x-data="{}" x-on:confirming-question-text-update.window="setTimeout(() => $refs.question_text.focus(), 250)">
                <x-input type="text" class="mt-1 block w-3/4"
                            autocomplete=""
                            placeholder="{{ old('$question_text') }}"
                            x-ref="question_text"
                            wire:model.defer="question_text"
                            wire:keydown.enter="update({{ $question_id }})" />

                <x-input-error for="question_text" class="mt-2" />
            </div>
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="$toggle('update_question')" wire:loading.attr="disabled">
                {{ __('Cancel') }}
            </x-secondary-button>

            <x-danger-button class="ml-3" wire:click="update({{ $question_id }})" wire:loading.attr="disabled">
                {{ __('Update Question') }}
            </x-danger-button>
        </x-slot>
    </x-dialog-modal>


    <!-- Delete Question Confirmation Modal -->
    <x-dialog-modal wire:model="confirm_delete">
        <x-slot name="title">
            {{ __('Delete Question') }}
        </x-slot>

        <x-slot name="content">
            {{ __('Are you sure you want to delete the selected question? Once your question is deleted, all of its data and associated votes will be permanently deleted.') }}
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="$toggle('confirm_delete')" wire:loading.attr="disabled">
                {{ __('Cancel') }}
            </x-secondary-button>

            <x-danger-button class="ml-3" wire:click="delete({{ $question_id }})" wire:loading.attr="disabled">
                {{ __('Delete Question') }}
            </x-danger-button>
        </x-slot>
    </x-dialog-modal>

</div>

