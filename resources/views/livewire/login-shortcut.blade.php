<div>
    @if ($available)
        <x-filament::section>
            <x-slot name="heading">
                {{ __('filament-login-shortcut::messages.heading') }}
            </x-slot>

            @if ($nonLocal)
                <x-filament::callout
                    color="warning"
                    :description="__('filament-login-shortcut::messages.warning', ['environments' => $environments])"
                />
            @endif

            <div style="display: grid; gap: 1rem;">
                {{ $this->form }}

                <x-filament::button type="button" wire:click="login" wire:loading.attr="disabled" :disabled="blank($data['selectedIdentifier'] ?? null)" style="width: 100%;">
                    {{ __('filament-login-shortcut::messages.login_button') }}
                </x-filament::button>
            </div>
        </x-filament::section>
    @endif
</div>
