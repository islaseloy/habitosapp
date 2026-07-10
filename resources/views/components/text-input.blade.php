@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border border-habits-line p-2.5 bg-habits-surface text-habits-ink rounded-lg focus:border-habits-green focus:ring-habits-green/20 focus:outline-none transition-colors duration-200']) }}>
