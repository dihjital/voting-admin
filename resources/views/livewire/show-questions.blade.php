<div class="w-full p-4">

    <x-new-button wire:click="toggleCreateQuestionModal"></x-new-button>

    @if($this->hasErrorMessage())
        <x-error-page code="{{ $this->getStatusCode() }}" message="{{ $this->getErrorMessage() }}"></x-error-page>
    @else
    <!-- Filters section -->
    <x-toggle checked wire:model="filters.quizzes">{{ __('Show quizzes') }}</x-toggle>
    <x-toggle checked wire:model="filters.closed">{{ __('Show closed') }}</x-toggle>

    <x-table>
        <x-slot name="head">
            <x-table.heading class="w-1/12">#</x-table.heading>
            <x-table.heading class="w-1/12"></x-table.heading>
            <x-table.heading class="w-6/12">{{ __('Question text') }}</x-table.heading>
            <x-table.heading class="w-1/12">{{ __('Closed?') }}</x-table.heading>
            <x-table.heading class="w-2/12">{{ __('# of answers') }}</x-table.heading>
            <x-table.heading class="w-1/12"></x-table.heading>
        </x-slot>
        <x-slot name="body">
            @forelse($questions as $q)
            <x-table.row wire:loading.class.delay="opacity-75" wire:key="row-{{ $q['id'] }}">
                <x-table.cell>{{ $q['id'] }}</x-table.cell>
                <x-table.cell class="space-x-1">
                    @if($q['belongs_to_quiz'])
                        <i class="fa-solid fa-trophy" title="{{ __('This question belongs to a quiz') }}"></i>
                    @endif
                    @if($q['is_closed'])
                        <i class="fa-solid fa-lock" title="{{ __('This question is closed') }}"></i>
                    @endif
                </x-table.cell>
                <x-table.cell>
                    <a href="/questions/{{ $q['id'] }}/votes">
                        {{ $q['question_text'] }}
                    </a>
                    @if($q['closed_at'])
                        <p class="italic text-xs">
                        @if($q['is_closed'] && $q['closed_at'] < now())
                            {{ __('This question was automatically closed at: :closeAt', ['closeAt' => Carbon\Carbon::parse($q['closed_at'])->format('m/d/Y')] )}}
                        @else
                            {{ __('This question was set to automatically close at: :closeAt', ['closeAt' => Carbon\Carbon::parse($q['closed_at'])->format('m/d/Y')] )}}
                        @endif
                        </p>
                    @endif      
                </x-table.cell>
                <x-table.cell>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" wire:click="openQuestion({{ $q['id'] }}, {{ $q['is_closed'] }})" value="" class="sr-only peer" {{ $q['is_closed'] ? 'checked' : '' }}>
                        <div class="w-11 h-6 bg-gray-200 rounded-full peer peer-focus:ring-4 peer-focus:ring-indigo-300 dark:peer-focus:ring-indigo-800 dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-indigo-600"></div>
                        <span class="ml-3 text-sm font-medium text-gray-900 dark:text-gray-300"></span>
                    </label>
                </x-table.cell>
                <x-table.cell>{{ $q['number_of_votes'] }}</x-table.cell>
                <x-table.cell class="text-right text-sm font-medium space-x-2">
                    <div class="flex space-x-2">
                        <button type="button" @disabled($q['is_closed']) wire:click="toggleQRCodeModal({{ $q['id'] }})" class="px-3 py-3 {{ $this->closedColor('modify', $q['is_closed']) }} text-white text-xs rounded-md">
                            <i class="fas fa-qrcode fa-sm" aria-hidden="true" title="{{ __('QR Code') }}"></i>
                        </button>
                        <button type="button" @disabled($q['is_closed']) wire:click="toggleUpdateQuestionModal({{ $q['id'] }})" class="px-3 py-3 {{ $this->closedColor('modify', $q['is_closed']) }} text-white text-xs rounded-md">
                            <i class="fas fa-edit fa-sm" aria-hidden="true" title="{{ __('Update') }}"></i>
                        </button>
                        <button type="button" @disabled($q['is_closed']) wire:click="toggleDeleteQuestionModal({{ $q['id'] }})" class="px-3 py-3 {{ $this->closedColor('delete', $q['is_closed']) }} text-white text-xs rounded-md">
                            <i class="fas fa-trash fa-sm" aria-hidden="true" title="{{ __('Delete') }}"></i>
                        </button>
                    </div>
                </x-table.cell>
            </x-table.row>
            @empty
            <x-table.row wire:key="row-empty">
                <x-table.cell colspan="6" class="whitespace-nowrap">
                    <div class="flex justify-center items-center">
                        <span class="py-8 text-base text-center font-medium text-gray-400 uppercase">{{ __('There are no questions in the database') }} ...</span>
                    </div>
                </x-table.cell>
            </x-table.row>
            @endforelse
        </x-slot>
    </x-table>

    <div class="mt-4">
        @if(self::PAGINATING)
            {{ $questions->links() }}
        @endif
    </div>

    @endif

    <!-- QR Code Modal -->
    <x-dialog-modal wire:model.defer="results_qrcode" maxWidth="lg">
        <x-slot name="title">
            {{ __('QR Code for voting') }}
        </x-slot>

        <x-slot name="content">
            {{ __('To use the web based voting client please scan the QR code on the left. The QR Code on the right can be consumed by the mobile voting client only.') }}

            <div class="flex mt-4 justify-center items-center space-x-4">
                <div class="w-1/2">
                    <img src="data:image/png;base64, {{ $this->generateQrCode($question_id) }}" alt="QR Code for web based voting client for {{ $question_id }}">
                </div>
                <div class="w-1/2">
                    <img src="data:image/png;base64, {{ $this->generateQrCodeForMobile($question_id) }}" alt="QR Code for mobile voting client for {{ $question_id }}">
                </div>
            </div>
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="$toggle('results_qrcode')" wire:loading.attr="disabled">
                {{ __('Close') }}
            </x-secondary-button>
        </x-slot>
    </x-dialog-modal>

    <!-- Create New Question Modal -->
    <x-dialog-modal wire:model.defer="new_question">
        <x-slot name="title">
            {{ __('Create Question') }}
        </x-slot>

        <x-slot name="content">
            {{ __('Please enter your question. This action will create a new question, and then you can add voting options to it.') }}

            <div class="mt-4 mb-2" x-data="{}" x-on:confirming-question-create.window="setTimeout(() => $refs.question_text.focus(), 250)">
                <x-input type="text" class="mt-1 block w-3/4"
                            autocomplete=""
                            placeholder="{{ old('$question_text') }}"
                            x-ref="question_text"
                            wire:model.defer="question_text"
                            wire:keydown.enter="create" />

                <x-input-error for="question_text" class="mt-2" />
            </div>

            {{ __('You can specify a date when the question should be closed automatically by the system.') }}
            <div class="relative w-3/4 mt-4" x-data="{}" x-on:confirming-question-create.window="setTimeout(() => $refs.question_close_at.focus(), 250)">
                <x-date-picker wire:model.defer="question_close_at" id="question_close_at" /> 
                <x-input-error for="question_close_at" class="mt-2" />
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
    <x-dialog-modal wire:model.defer="update_question">
        <x-slot name="title">
            {{ __('Update Question') }}
        </x-slot>

        <x-slot name="content">
            {{ __('Please enter your new text for the selected question') }}

            <div class="mt-4 mb-2" x-data="{}" x-on:confirming-question-update.window="setTimeout(() => $refs.question_text.focus(), 250)">
                <x-input type="text" class="mt-1 block w-3/4"
                            autocomplete=""
                            placeholder="{{ old('$question_text') }}"
                            x-ref="question_text"
                            wire:model.defer="question_text"
                            wire:keydown.enter="update({{ $question_id }})" />

                <x-input-error for="question_text" class="mt-2" />
            </div>

            {{ __('You can specify a date when the question should be closed automatically by the system.') }}
            <div class="relative w-3/4 mt-4" x-data="{}" x-on:confirming-question-update.window="setTimeout(() => $refs.question_close_at.focus(), 250)">
                <x-date-picker wire:model.defer="question_close_at" id="question_close_at" /> 
                <x-input-error for="question_close_at" class="mt-2" />
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
    <x-dialog-modal wire:model.defer="confirm_delete">
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

