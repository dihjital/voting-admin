<button 
{{ $attributes->merge(['type' => 'button', 'class' => "fixed z-100 bottom-10 right-8 bg-indigo-500 w-20 h-20 rounded-full drop-shadow-lg flex justify-center items-center text-white text-4xl hover:bg-indigo-600 hover:drop-shadow-2xl hover:animate-bounce duration-300"]) }}>
    <svg width="50" height="50" fill="currentColor" class="bi bi-plus" viewBox="0 0 16 16">
        <path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/>
    </svg>
</button>