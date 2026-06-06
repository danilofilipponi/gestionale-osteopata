<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center justify-center gap-2 rounded-xl border border-line bg-white px-4 py-2.5 text-sm font-bold text-ink shadow-sm transition hover:bg-mist focus:outline-none focus:ring-2 focus:ring-sage/20 focus:ring-offset-2 disabled:opacity-25']) }}>
    {{ $slot }}
</button>
