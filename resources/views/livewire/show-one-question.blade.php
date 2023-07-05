<div class="w-full p-4">

    <button type="button" wire:click="toggleCreateVoteModal"
           class="fixed z-100 bottom-10 right-8 bg-blue-600 w-20 h-20 rounded-full drop-shadow-lg flex justify-center items-center text-white text-4xl hover:bg-blue-700 hover:drop-shadow-2xl hover:animate-bounce duration-300">
            <svg width="50" height="50" fill="currentColor" class="bi bi-plus" viewBox="0 0 16 16">
                <path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/>
            </svg>
    </button>

    <div class="py-4 flex justify-center items-center">
        <h3 class="text-lg text-center font-medium text-gray-900 dark:text-gray-100">{{ $question_text }}</h3>
    </div>
    @if($error_message)
        <p class="text-lg text-center font-medium text-red-500">{{ $error_message }}</p>
    @else
    <x-table>
        <x-slot name="head">
            <x-table.heading class="w-1/12">{{ __('Vote number') }}</x-table.heading>
            <x-table.heading class="w-6/12">{{ __('Vote text') }}</x-table.heading>
            <x-table.heading class="w-2/12">{{ __('Number of votes received') }}</x-table.heading>
            <x-table.heading class="w-1/12">{{ __('Vote') }}</x-table.heading>
            <x-table.heading class="w-2/12"></x-table.heading>
        </x-slot>
        <x-slot name="body">
            @php
                $sum_of_votes = array_reduce($votes, fn($carry, $v) => $carry += $v['number_of_votes']) ?: 1;
            @endphp
            @forelse($votes as $v)
            <x-table.row wire:loading.class.delay="opacity-75" wire:key="row-{{ $v['id'] }}">
                <x-table.cell>{{ $v['id'] }}</x-table.cell>
                <x-table.cell class="space-y-2">
                    <div>{{ $v['vote_text'] }}</div>
                    <div id="bar-id-{{ $v['id'] }}">
                        <script>
                            document.addEventListener("DOMContentLoaded", function() {
                                createSVGBar('{{ $v['id'] }}', {{ $v['number_of_votes'] }}, {{ $sum_of_votes }});
                            });
                        </script>
                    </div>
                </x-table.cell>
                <x-table.cell>{{ $v['number_of_votes'] }}</x-table.cell>
                <x-table.cell>
                    <button type="button" wire:click="vote({{ $v['id'] }})" class="px-3 py-3 bg-blue-500 hover:bg-blue-600 text-white text-xs rounded-md">
                        <i class="fas fa-plus fa-sm" aria-hidden="true" title="{{ __('Vote') }}"></i>
                    </button>
                </x-table.cell>
                <x-table.cell class="text-right text-sm font-medium space-x-2">
                    <button type="button" wire:click="toggleUpdateVoteModal({{ $v['id'] }})" class="px-3 py-3 bg-blue-500 hover:bg-blue-600 text-white text-xs rounded-md">
                        <i class="fas fa-edit fa-sm" aria-hidden="true" title="{{ __('Update') }}"></i>
                    </button>
                    <button type="button" wire:click="toggleDeleteVoteModal({{ $v['id'] }})" class="px-3 py-3 bg-red-500 hover:bg-red-600 text-white text-xs rounded-md">
                        <i class="fas fa-trash fa-sm" aria-hidden="true" title="{{ __('Delete') }}"></i>
                    </button>
                </x-table.cell>
            </x-table.row>
            @empty
            <x-table.row wire:key="row-empty">
                <x-table.cell colspan="5" class="whitespace-nowrap">
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

            <div class="mt-4" x-data="{}" x-on:confirming-vote-create.window="setTimeout(() => $refs.vote_text.focus(), 250)">
                <x-input type="text" class="mt-1 block w-3/4"
                            autocomplete=""
                            placeholder="{{ old('$vote_text') }}"
                            x-ref="vote_text"
                            wire:model.defer="vote_text"
                            wire:keydown.enter="create" />

                <x-input-error for="vote_text" class="mt-2" />
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
            {{ __('Please enter your new text for the selected vote option. This action will reset the current number of votes to 0.') }}

            <div class="mt-4" x-data="{}" x-on:confirming-vote-text-update.window="setTimeout(() => $refs.vote_text.focus(), 250)">
                <x-input type="text" class="mt-1 block w-3/4"
                            autocomplete=""
                            placeholder="{{ old('$vote_text') }}"
                            x-ref="vote_text"
                            wire:model.defer="vote_text"
                            wire:keydown.enter="update({{ $vote_id }})" />

                <x-input-error for="vote_text" class="mt-2" />
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

    @push('scripts')
        <script>
            function createSVGBar(id, numberOfVotes, sumOfVotes) {
                let svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
                svg.setAttribute('width', '100%');
                svg.setAttribute('height', '10');

                let rect = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
                rect.setAttribute('x', '0');
                rect.setAttribute('y', '0');
                rect.setAttribute('width', '0');
                rect.setAttribute('height', '10');
                rect.setAttribute('fill', 'lightblue');
                svg.appendChild(rect);

                document.getElementById('bar-id-' + id).appendChild(svg);

                const width = (numberOfVotes / sumOfVotes) * 100;
                rect.setAttribute('width', width + '%');
            }
        </script>
    @endpush

</div>
