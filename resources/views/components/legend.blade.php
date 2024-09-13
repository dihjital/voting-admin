<!-- This is a legend for all the icons we use for questions in the list page //-->

<div class="flex flex-wrap space-x-6 text-sm ml-2 mt-4 text-gray-500 dark:text-gray-400">
    <div class="flex items-center space-x-2">
        <i class="fa-solid fa-trophy" title="{{ __('The question belongs to a quiz') }}"></i>
        <span>{{ __('The question belongs to a quiz') }}</span>
    </div>
    <div class="flex items-center space-x-2">
        <i class="fa-solid fa-lock" title="{{ __('The question is closed') }}"></i>
        <span>{{ __('The question is closed') }}</span>
    </div>
    <div class="flex items-center space-x-2">
        <i class="fa-solid fa-user-secret" title="{{ __('A valid e-mail is required to vote for this question') }}"></i>
        <span>{{ __('A valid e-mail address is required to vote') }}</span>
    </div>
    <div class="flex items-center space-x-2">
        <i class="fa-solid fa-eye-slash" title="{{ __('Current votes will NOT be shown during voting') }}"></i>
        <span>{{ __('Current votes will NOT be shown during voting') }}</span>
    </div>
</div>