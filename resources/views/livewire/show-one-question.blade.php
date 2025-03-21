<div class="w-full p-4"

    x-data="{

        voteResults: @entangle('votes'),
        correctVote: @entangle('correct_vote'),

        init() {
            const createVoteBarCharts = () => {
                const sumOfVotes = this.voteResults.length > 0
                    ? this.voteResults.map(item => item['number_of_votes']).reduce((a, b) => a + b)
                    : undefined;

                this.voteResults.forEach(item => {
                    createSVGBar(item['id'], item['number_of_votes'], sumOfVotes, this.correctVote === item['id'] ? true : false);
                });
            };

            createVoteBarCharts();

            Livewire.on('data-fetched', () => {
                createVoteBarCharts();
            });
        }
    }"

>

    @unless ($question_closed)
    <x-new-button wire:click="toggleCreateVoteModal"></x-new-button>
    @endif

    <div class="py-4 flex justify-center items-center">
        <h3 class="text-lg text-center font-medium text-gray-900 dark:text-gray-100">{{ $question_text }}</h3>
    </div>
    @if($this->hasErrorMessage())
        <x-error-page code="{{ $this->getStatusCode() }}" message="{{ $this->getErrorMessage() }}"></x-error-page>
    @else
    <x-table>
        <x-slot name="head">
            <x-table.heading class="w-1/12">#</x-table.heading>
            <x-table.heading class="w-4/12">{{ __('Vote text') }}</x-table.heading>
            <x-table.heading class="w-2/12">{{ __('# of votes received') }}</x-table.heading>
            <x-table.heading class="w-2/12">{{ __('Last voted at') }}</x-table.heading>
            <x-table.heading class="w-1/12">{{ __('Vote') }}</x-table.heading>
            <x-table.heading class="w-2/12"></x-table.heading>
        </x-slot>
        <x-slot name="body">
            @forelse($votes as $v)
            <x-table.row wire:loading.class.delay="opacity-75" wire:key="row-{{ $v['id'] }}">
                <x-table.cell>
                    <div class="flex space-x-4 items-center">
                    <span>
                        {{ $letters[$loop->index] }})
                    </span>
                    @if($v['image_path'])
                        <a href="#" wire:click="toggleShowImageModal({{ $v['id'] }})">
                            <i class="fas fa-paperclip fa-sm" aria-hidden="true" title="{{ $v['vote_text'] }}"></i>
                        </a>
                    @endif
                    @if($v['id'] === $correct_vote)
                        <i class="fas fa-check fa-sm" aria-hidden="true" title="{{ __('This is the correct vote set by the owner of this question.') }}"></i>
                    @endif
                    </div>
                </x-table.cell>
                <x-table.cell class="space-y-2">
                    <div>{{ $v['vote_text'] }}</div>
                    <div wire:key="bar-id-{{ $v['id'] }}" id="bar-id-{{ $v['id'] }}">
                    </div>
                </x-table.cell>
                <x-table.cell>{{ $v['number_of_votes'] }}</x-table.cell>
                <x-table.cell>{{ $v['voted_at'] ? \Carbon\Carbon::parse($v['voted_at'])->diffForHumans() : 'N/A' }}</x-table.cell>
                <x-table.cell>
                    <button type="button" @disabled($question_closed) wire:click="vote({{ $v['id'] }})" class="px-3 py-3 {{ $this->closedColor('vote') }} text-white text-xs rounded-md">
                        <i class="fas fa-plus fa-sm" aria-hidden="true" title="{{ __('Vote') }}"></i>
                    </button>
                </x-table.cell>
                <x-table.cell class="text-right text-sm font-medium space-x-2">
                    <div class="flex space-x-2">
                        <button type="button" @disabled($question_closed) wire:click="toggleUpdateVoteModal({{ $v['id'] }})" class="px-3 py-3 {{ $this->closedColor('modify') }} text-white text-xs rounded-md">
                            <i class="fas fa-edit fa-sm" aria-hidden="true" title="{{ __('Update') }}"></i>
                        </button>
                        <button type="button" @disabled($question_closed) wire:click="toggleDeleteVoteModal({{ $v['id'] }})" class="px-3 py-3 {{ $this->closedColor('delete') }} text-white text-xs rounded-md">
                            <i class="fas fa-trash fa-sm" aria-hidden="true" title="{{ __('Delete') }}"></i>
                        </button>
                    </div>
                </x-table.cell>
            </x-table.row>
            @empty
            <x-table.row wire:key="row-empty">
                <x-table.cell colspan="6" class="whitespace-nowrap">
                    <div class="flex justify-center items-center">
                        <span class="py-8 text-base font-medium text-gray-400 uppercase">{{ __('There are no votes associated with this question yet in the database') }} ...</span>
                    </div>
                </x-table.cell>
            </x-table.row>
            @endforelse
        </x-slot>
    </x-table>
    @endif

    <!-- Create New Vote Modal -->
    <x-dialog-modal wire:model="new_vote">
        <x-slot name="title">
            {{ __('Create Vote') }}
        </x-slot>

        <x-slot name="content">
            {{ __('Please enter the text for the new vote option. This action will create a new vote option for the selected question and initiates the number of votes to 0.') }}
            <div class="mt-4 mb-4" x-data="{}" x-on:confirming-vote-create.window="setTimeout(() => $refs.vote_text.focus(), 250)">
                <x-input type="text" class="mt-1 block w-3/4"
                            autocomplete=""
                            placeholder="{{ old('$vote_text') }}"
                            x-ref="vote_text"
                            wire:model.defer="vote_text"
                            wire:keydown.enter="create" />

                <x-input-error for="vote_text" class="mt-2" />
            </div>

            {{ __('Optionally you can upload an image for each vote. The image will be shown to the voters on the client.') }}
            <div class="mt-4 space-y-4">
                @if ($vote_image)
                    {{ __('Photo preview:') }}
                    <div class="w-32 h-32 overflow-hidden border-2 border-gray-200 rounded-lg dark:border-gray-700 hover:bg-gray-50">
                        <img class="object-cover w-full h-full" src="{{ $vote_image->temporaryUrl() }}" alt="Thumbnail">
                    </div>
                @endif
                <x-input 
                    type="file"
                    class="mt-1 block w-3/4 h-9 
                            rounded-md border border-input bg-background px-3 py-1 
                            text-sm shadow-sm transition-colors 
                            file:border-0 file:bg-transparent file:text-gray-600 file:dark:text-gray-300 file:text-sm file:font-medium 
                            placeholder:text-muted-foreground 
                            focus:border-1 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600"
                    placeholder="{{ __('Please select an image for uploading') }}"
                    accept="image/png, image/jpeg, image/jpg, image/gif"
                    wire:model.defer="vote_image" />

                <x-input-error for="vote_image" class="mt-2" />
            </div>
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="$toggle('new_vote')" wire:loading.attr="disabled">
                {{ __('Cancel') }}
            </x-secondary-button>

            <x-danger-button class="ml-3" wire:click="create" wire:loading.attr="disabled">
                {{ __('Create Vote') }}
            </x-danger-button>
        </x-slot>
    </x-dialog-modal>

    <!-- Update Vote Modal -->
    <x-dialog-modal wire:model="update_vote">
        <x-slot name="title">
            {{ __('Update Vote') }}
        </x-slot>

        <x-slot name="content">
            <p>{{ __('You can modify the text of the selected voting option. This action will set the number of votes to 0 if the Reset number of votes to 0 checkbox is selected.') }}</p>

            <div class="mt-4" x-data="{}" x-on:confirming-vote-text-update.window="setTimeout(() => $refs.vote_text.focus(), 250)">
                <x-input type="text" class="mt-1 block w-3/4"
                            autocomplete=""
                            placeholder="{{ old('$vote_text') }}"
                            x-ref="vote_text"
                            wire:model.defer="vote_text"
                            wire:keydown.enter="update({{ $vote_id }})" />

                <x-input-error for="vote_text" class="mt-2" />
            </div>

            <div class="mt-4" x-data="{}" x-on:confirming-reset-number-of-votes-update.window="setTimeout(() => $refs.reset_number_of_votes.focus(), 250)">
                <label for="reset_number_of_votes" class="inline-flex items-center">
                    <x-input type="checkbox" id="reset_number_of_votes" class="mt-1 block"
                            x-ref="keep_number_of_votes"
                            wire:model.defer="reset_number_of_votes"
                            wire:keydown.enter="update({{ $vote_id }})" />
                    <span class="ml-2 text-gray-600 dark:text-gray-400">{{ __('Reset number of votes to 0') }}</span>
                </label>
            
                <x-input-error for="reset_number_of_votes" class="mt-2" />
            </div>
            <p class="mt-1">{{ __('Please note that resetting the number of votes to 0 will also remove all locations belonging to this voting option from the database.') }}</p>
           
            @if(!$vote_image && $image_url)
            <div class="mt-4 relative border-2 border-gray-400 rounded-lg w-32 h-auto">
                <img src="{{ $image_url }}" alt="{{ $vote_text }}" class="rounded-lg" />
                
                <!-- Close button with hover effect -->
                <div 
                    wire:click="deleteVoteImage( {{ $vote_id }})" 
                    class="close-button absolute top-0 right-0 dark:bg-gray-900 bg-gray-200 rounded-full p-2 flex items-center justify-center cursor-pointer -mt-4 -mr-4 border-2 border-gray-400 hover:bg-gray-400 dark:hover:bg-gray-700"
                    title="{{ __('Delete image') }}"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="4" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </div>
            </div>
            @endif

            <p class="mt-2">{{ __('Optionally you can upload an image for each vote. The image will be shown to the voters on the client.') }}</p>
            <div class="mt-2 space-y-4">
                @if ($vote_image)
                    {{ __('Photo preview:') }}
                    <div class="w-32 h-32 overflow-hidden border-2 border-gray-200 rounded-lg dark:border-gray-700 hover:bg-gray-50">
                        <img class="object-cover w-full h-full" src="{{ $vote_image->temporaryUrl() }}" alt="Thumbnail">
                    </div>
                @endif
                <x-input 
                    type="file" 
                    class="mt-1 block w-3/4 h-9 
                            rounded-md border border-input bg-background px-3 py-1 
                            text-sm shadow-sm transition-colors 
                            file:border-0 file:bg-transparent file:text-gray-600 file:dark:text-gray-300 file:text-sm file:font-medium 
                            placeholder:text-muted-foreground 
                            focus:border-1 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600"
                    placeholder="{{ __('Please select an image for uploading') }}"
                    accept="image/png, image/jpeg, image/jpg, image/gif"
                    wire:model.defer="vote_image" />

                <x-input-error for="vote_image" class="mt-2" />
            </div>

        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="$toggle('update_vote')" wire:loading.attr="disabled">
                {{ __('Cancel') }}
            </x-secondary-button>

            <x-danger-button class="ml-3" wire:click="update({{ $vote_id }})" wire:loading.attr="disabled">
                {{ __('Update Vote') }}
            </x-danger-button>
        </x-slot>
    </x-dialog-modal>

    <!-- Delete Vote Confirmation Modal -->
    <x-dialog-modal wire:model="confirm_delete">
        <x-slot name="title">
            {{ __('Delete Vote') }}
        </x-slot>

        <x-slot name="content">
            {{ __('Are you sure you want to delete the selected vote? Once your vote is deleted, all of its data will be permanently deleted.') }}
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="$toggle('confirm_delete')" wire:loading.attr="disabled">
                {{ __('Cancel') }}
            </x-secondary-button>

            <x-danger-button class="ml-3" wire:click="delete({{ $vote_id }})" wire:loading.attr="disabled">
                {{ __('Delete Vote') }}
            </x-danger-button>
        </x-slot>
    </x-dialog-modal>

    <!-- Show Vote image -->
    <x-dialog-modal wire:model="show_image">
        <x-slot name="title">
            {{ __('Showing image for ":voteText" vote', ['voteText' => $vote_text]) }}
        </x-slot>
        <x-slot name="content">
            <div class="w-full flex justify-center mb-2 overflow-hidden border-2 border-gray-200 rounded-lg dark:border-gray-700 hover:bg-gray-50">
                <img class="h-96 object-scale-down transition duration-300 ease-in-out hover:scale-125" src="{{ $image_url }}" alt="{{ $vote_text }}">
            </div>                                           
        </x-slot>
        <x-slot name="footer">
            <x-secondary-button wire:click="$toggle('show_image')" wire:loading.attr="disabled">
                {{ __('OK') }}
            </x-secondary-button>
        </x-slot>
    </x-dialog-modal>
</div>
