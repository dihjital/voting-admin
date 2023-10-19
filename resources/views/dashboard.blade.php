<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-5">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

                <div class="grid grid-cols-3 grid-rows-2 p-0 gap-2 justify-items-center items-center">
                    <!-- First row //-->
                    <div class="p-4 w-full h-full col-span-1">
                        <a href="/questions" class="flex flex-col items-center bg-white border border-gray-200 rounded-lg shadow md:flex-row md:max-w-xl hover:bg-gray-100 dark:border-gray-700 dark:bg-gray-800 dark:hover:bg-gray-700">
                            <img class="hidden lg:flex object-cover w-full rounded-t-lg h-120 md:h-60 md:w-20 md:rounded-none md:rounded-l-lg" src="/questions.png" alt="">
                            <div class="flex flex-col justify-between p-4 leading-normal">
                                <h5 class="mb-2 text-xl font-bold tracking-tight text-gray-900 dark:text-white">{{ __('Number of questions')}}</h5>
                                <p class="mb-3 font-bold text-2xl text-gray-700 dark:text-gray-400">{{ $results?->number_of_questions }}</p>
                            </div>
                        </a>
                    </div>
                    <div class="p-4 w-full h-full col-span-1">
                        <a href="#" class="flex flex-col items-center bg-white border border-gray-200 rounded-lg shadow md:flex-row md:max-w-xl hover:bg-gray-100 dark:border-gray-700 dark:bg-gray-800 dark:hover:bg-gray-700">
                            <img class="hidden lg:flex object-cover w-full rounded-t-lg h-120 md:h-60 md:w-20 md:rounded-none md:rounded-l-lg" src="/answer.jpeg" alt="">
                            <div class="flex flex-col justify-between p-4 leading-normal">
                                <h5 class="mb-2 text-xl font-bold tracking-tight text-gray-900 dark:text-white">{{ __('Number of possible answers')}}</h5>
                                <p class="mb-3 font-bold text-2xl text-gray-700 dark:text-gray-400">{{ $results?->number_of_answers }}</p>
                            </div>
                        </a>
                    </div>
                    <div class="p-4 w-full h-full col-span-1">
                        <a href="#" class="flex flex-col items-center bg-white border border-gray-200 rounded-lg shadow md:flex-row md:max-w-xl hover:bg-gray-100 dark:border-gray-700 dark:bg-gray-800 dark:hover:bg-gray-700">
                            <img class="hidden lg:flex object-cover w-full rounded-t-lg h-120 md:h-60 md:w-20 md:rounded-none md:rounded-l-lg" src="/total_votes.jpeg" alt="">
                            <div class="flex flex-col justify-between p-4 leading-normal">
                                <h5 class="mb-2 text-xl font-bold tracking-tight text-gray-900 dark:text-white">{{ __('Total number of votes received') }}</h5>
                                <p class="mb-3 font-bold text-2xl text-gray-700 dark:text-gray-400">{{ $results?->total_number_of_votes }}</p>
                            </div>
                        </a>
                    </div>
                    <!-- Second row //-->
                    <div class="p-4 w-full h-full col-span-1">
                        <a href="{{ $results?->highest_vote['id'] ? '/questions/'.$results?->highest_vote['id'].'/votes' : '#' }}" class="flex flex-col items-center bg-white border border-gray-200 rounded-lg shadow md:flex-row md:max-w-xl hover:bg-gray-100 dark:border-gray-700 dark:bg-gray-800 dark:hover:bg-gray-700">
                            <img class="hidden lg:flex object-cover w-full rounded-t-lg h-120 md:h-60 md:w-20 md:rounded-none md:rounded-l-lg" src="/answer.jpeg" alt="">
                            <div class="flex flex-col justify-between p-4 leading-normal">
                                <h5 class="mb-2 text-xl font-bold tracking-tight text-gray-900 dark:text-white">{{ __('Answer with the most votes') }}</h5>
                                <p class="font-bold text-sm text-gray-700 dark:text-gray-400">Q: {{ Str::limit($results?->highest_vote['question_text'], 40, '...') }}</p>
                                <p class="font-bold text-sm text-gray-700 dark:text-gray-400">A: {{ Str::limit($results?->highest_vote['vote_text'], 40, '...') }}</p>
                                <p class="font-bold text-sm text-gray-700 dark:text-gray-400"># {{ $results?->highest_vote['number_of_votes'] }}</p>
                            </div>
                        </a>
                    </div>
                    <div class="p-4 w-full h-full col-span-1">
                        <a href="{{ $results?->highest_question['id'] ? '/questions/'.$results?->highest_question['id'].'/votes' : '#' }}" class="flex flex-col items-center bg-white border border-gray-200 rounded-lg shadow md:flex-row md:max-w-xl hover:bg-gray-100 dark:border-gray-700 dark:bg-gray-800 dark:hover:bg-gray-700">
                            <img class="hidden lg:flex object-cover w-full rounded-t-lg h-120 md:h-60 md:w-20 md:rounded-none md:rounded-l-lg" src="/questions.png" alt="">
                            <div class="flex flex-col justify-between p-4 leading-normal">
                                <h5 class="mb-2 text-xl font-bold tracking-tight text-gray-900 dark:text-white">{{ __('Question with the most answers')}}</h5>
                                <p class="font-bold text-sm text-gray-700 dark:text-gray-400">Q: {{ Str::limit($results?->highest_question['question_text'], 40, '...') }}</p>
                                <p class="font-bold text-sm text-gray-700 dark:text-gray-400"># {{ $results?->highest_question['number_of_votes'] }}</p>
                            </div>
                        </a>
                    </div>
                    <div class="p-4 w-full h-full col-span-1">
                        <a href="{{ $results?->most_voted_question['id'] ? '/questions/'.$results?->most_voted_question['id'].'/votes' : '#' }}" class="flex flex-col items-center bg-white border border-gray-200 rounded-lg shadow md:flex-row md:max-w-xl hover:bg-gray-100 dark:border-gray-700 dark:bg-gray-800 dark:hover:bg-gray-700">
                            <img class="hidden lg:flex object-cover w-full rounded-t-lg h-120 md:h-60 md:w-20 md:rounded-none md:rounded-l-lg" src="/questions.png" alt="">
                            <div class="flex flex-col justify-between p-4 leading-normal">
                                <h5 class="mb-2 text-xl font-bold tracking-tight text-gray-900 dark:text-white">{{ __('Question with the highest number of votes') }}</h5>
                                <p class="font-bold text-sm text-gray-700 dark:text-gray-400">Q: {{ Str::limit($results?->most_voted_question['question_text'], 40, '...') }}</p>
                                <p class="font-bold text-sm text-gray-700 dark:text-gray-400"># {{ $results?->most_voted_question['total_votes'] }}</p>
                            </div>
                        </a>
                    </div>
                  </div>
                  
        </div>
    </div>
</x-app-layout>
