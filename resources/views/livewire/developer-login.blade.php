<div>
    @if ($available)
        <section class="fi-section mb-6 rounded-xl border border-gray-200 p-4 dark:border-white/10" aria-labelledby="developer-login-heading">
            <h2 id="developer-login-heading" class="fi-section-header-heading text-base font-semibold">{{ __('filament-developer-login::messages.heading') }}</h2>
            @if ($nonLocal)
                <div class="mt-3 rounded-lg border border-warning-300 bg-warning-50 p-3 text-sm text-warning-800 dark:border-warning-600 dark:bg-warning-950 dark:text-warning-200" role="alert">{{ __('filament-developer-login::messages.warning', ['environments' => $environments]) }}</div>
            @endif
            <div class="mt-4">{{ $this->form }}</div>
            <button type="button" wire:click="login" wire:loading.attr="disabled" @disabled(blank($data['selectedIdentifier'] ?? null)) class="fi-btn fi-size-md fi-color fi-color-primary fi-bg-color-400 hover:fi-bg-color-300 dark:fi-bg-color-600 dark:hover:fi-bg-color-500 fi-text-color-900 hover:fi-text-color-800 dark:fi-text-color-950 dark:hover:fi-text-color-950 mt-4 w-full">{{ __('filament-developer-login::messages.login_button') }}</button>
        </section>
    @endif
</div>
