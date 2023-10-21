<div class="w-full p-4">

    <x-new-button wire:click="toggleCreateQuestionModal"></x-new-button>

    @if($this->hasErrorMessage())
        <x-error-page code="{{ $this->getStatusCode() }}" message="{{ $this->getErrorMessage() }}"></x-error-page>
    @else
    <x-table>
        <x-slot name="head">
            <x-table.heading class="w-1/12">#</x-table.heading>
            <x-table.heading class="w-6/12">{{ __('Quiz text') }}</x-table.heading>
            <x-table.heading class="w-4/12">{{ __('# of questions') }}</x-table.heading>
            <x-table.heading class="w-1/12"></x-table.heading>
        </x-slot>
        <x-slot name="body">
            @forelse($quizzes as $q)
            <x-table.row wire:loading.class.delay="opacity-75" wire:key="row-{{ $q['id'] }}">
                <x-table.cell>{{ $q['id'] }}</x-table.cell>
                <x-table.cell>
                    <a href="/quizzes/{{ $q['id'] }}/questions">
                        {{ $q['name'] }}
                    </a>      
                </x-table.cell>
                <x-table.cell>{{ $q['number_of_questions'] }}</x-table.cell>
                <x-table.cell class="text-right text-sm font-medium space-x-2">
                    <div class="flex space-x-2">
                        <button type="button" @disabled(0) wire:click="toggleQRCodeModal({{ $q['id'] }})" class="px-3 py-3 {{ $this->closedColor('modify', 0) }} text-white text-xs rounded-md">
                            <i class="fas fa-qrcode fa-sm" aria-hidden="true" title="{{ __('QR Code') }}"></i>
                        </button>
                        <button type="button" @disabled(1) wire:click="toggleUpdateQuizModal({{ $q['id'] }})" class="px-3 py-3 {{ $this->closedColor('modify', 1) }} text-white text-xs rounded-md">
                            <i class="fas fa-edit fa-sm" aria-hidden="true" title="{{ __('Update') }}"></i>
                        </button>
                        <button type="button" @disabled(0) wire:click="toggleDeleteQuizModal({{ $q['id'] }})" class="px-3 py-3 {{ $this->closedColor('delete', 0) }} text-white text-xs rounded-md">
                            <i class="fas fa-trash fa-sm" aria-hidden="true" title="{{ __('Delete') }}"></i>
                        </button>
                    </div>
                </x-table.cell>
            </x-table.row>
            @empty
            <x-table.row wire:key="row-empty">
                <x-table.cell colspan="4" class="whitespace-nowrap">
                    <div class="flex justify-center items-center">
                        <span class="py-8 text-base text-center font-medium text-gray-400 uppercase">{{ __('There are no quizzes in the database') }} ...</span>
                    </div>
                </x-table.cell>
            </x-table.row>
            @endforelse
        </x-slot>
    </x-table>

    <div class="mt-4">
        @if(self::PAGINATING)
            {{ $quizzes->links() }}
        @endif
    </div>

    @endif

    <!-- QR Code Modal -->
    <x-dialog-modal wire:model="results_qrcode" maxWidth="lg">
        <x-slot name="title">
            {{ __('QR Code for voting') }}
        </x-slot>

        <x-slot name="content">
            {{ __('To use the web based voting client please scan the QR code on the left. The QR Code on the right can be consumed by the mobile voting client only.') }}

            <div class="flex mt-4 justify-center items-center space-x-4">
                <div class="w-1/2">
                    <img src="data:image/png;base64, {{ $this->generateQrCode($quiz_id) }}" alt="QR Code for web based voting client for {{ $quiz_id }}">
                </div>
                <div class="w-1/2">
                    <img src="data:image/png;base64, {{ $this->generateQrCodeForMobile($quiz_id) }}" alt="QR Code for mobile voting client for {{ $quiz_id }}">
                </div>
            </div>
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="$toggle('results_qrcode')" wire:loading.attr="disabled">
                {{ __('Close') }}
            </x-secondary-button>
        </x-slot>
    </x-dialog-modal>

    <!-- Create New Quiz Modal -->
    <x-dialog-modal wire:model="new_quiz">
        <x-slot name="title">
            {{ __('Create Quiz') }}
        </x-slot>

        <x-slot name="content">
            {{ __('Please enter the name of your quiz. This action will create a new quiz, and add the selected questions to it. A question can belong to multiple quizzes.') }}

            <div class="mt-4" x-data="{}" x-on:confirming-quiz-create.window="setTimeout(() => $refs.quiz_name.focus(), 250)">
                <x-input type="text" class="mt-1 block w-3/4"
                            autocomplete=""
                            placeholder="{{ old('$name') }}"
                            x-ref="quiz_name"
                            wire:model.defer="name"
                            wire:keydown.enter="create" />

                <x-input-error for="name" class="mt-2" />
            </div>
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="$toggle('new_quiz')" wire:loading.attr="disabled">
                {{ __('Cancel') }}
            </x-secondary-button>

            <x-danger-button class="ml-3" wire:click="create" wire:loading.attr="disabled">
                {{ __('Create Quiz') }}
            </x-danger-button>
        </x-slot>
    </x-dialog-modal>

    <!-- Update Question Modal -->
    <x-dialog-modal wire:model="update_quiz">
        <x-slot name="title">
            {{ __('Update Quiz') }}
        </x-slot>

        <x-slot name="content">
            {{ __('Please enter a new name for the selected quiz or add, remove any questions to it.') }}

            <div class="mt-4" x-data="{}" x-on:confirming-quiz-name-update.window="setTimeout(() => $refs.quiz_name.focus(), 250)">
                <x-input type="text" class="mt-1 block w-3/4"
                            autocomplete=""
                            placeholder="{{ old('$name') }}"
                            x-ref="quiz_name"
                            wire:model.defer="name"
                            wire:keydown.enter="update({{ $quiz_id }})" />

                <x-input-error for="name class="mt-2" />
            </div>
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="$toggle('update_quiz')" wire:loading.attr="disabled">
                {{ __('Cancel') }}
            </x-secondary-button>

            <x-danger-button class="ml-3" wire:click="update({{ $quiz_id }})" wire:loading.attr="disabled">
                {{ __('Update Quiz') }}
            </x-danger-button>
        </x-slot>
    </x-dialog-modal>

    <!-- Delete Quiz Confirmation Modal -->
    <x-dialog-modal wire:model="confirm_delete">
        <x-slot name="title">
            {{ __('Delete Quiz') }}
        </x-slot>

        <x-slot name="content">
            {{ __('Are you sure you want to delete the selected quiz? If your quiz is deleted then the related questions will still remain in the database.') }}
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="$toggle('confirm_delete')" wire:loading.attr="disabled">
                {{ __('Cancel') }}
            </x-secondary-button>

            <x-danger-button class="ml-3" wire:click="delete({{ $quiz_id }})" wire:loading.attr="disabled">
                {{ __('Delete Quiz') }}
            </x-danger-button>
        </x-slot>
    </x-dialog-modal>

</div>

