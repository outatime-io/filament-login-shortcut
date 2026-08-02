<div>
    @if ($available)
        <x-filament::section>
            <x-slot name="heading">
                {{ __('filament-developer-login::messages.heading') }}
            </x-slot>

            @if ($nonLocal)
                <x-filament::callout color="warning">
                    {{ __('filament-developer-login::messages.warning', ['environments' => $environments]) }}
                </x-filament::callout>
            @endif

            <div style="display: grid; gap: 1rem;">
                {{ $this->form }}

                <x-filament::button type="button" wire:click="login" wire:loading.attr="disabled" :disabled="blank($data['selectedIdentifier'] ?? null)" style="width: 100%;">
                    {{ __('filament-developer-login::messages.login_button') }}
                </x-filament::button>
            </div>
        </x-filament::section>
    @endif
</div>
