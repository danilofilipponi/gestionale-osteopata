<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center gap-2 rounded-xl border border-transparent bg-sage px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-[#4f7f75] focus:outline-none focus:ring-2 focus:ring-sage/20 focus:ring-offset-2 active:bg-[#426b63]']) }}>
    {{ $slot }}
</button>
