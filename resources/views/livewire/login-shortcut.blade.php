<div>
    @if ($available)
        <x-filament::section>
            <x-slot name="heading">
                {{ __('filament-login-shortcut::messages.heading') }}
            </x-slot>

            <div
                x-data="{ rateLimitMessage: null }"
                x-on:filament-login-shortcut-rate-limited.window="rateLimitMessage = $event.detail.message"
                style="display: grid; gap: 1rem;"
            >
                @if ($nonLocal)
                    <x-filament::callout
                        color="warning"
                        :description="__('filament-login-shortcut::messages.warning', ['environments' => $environments])"
                    />
                @endif

                <div
                    x-cloak
                    x-show="rateLimitMessage"
                    x-text="rateLimitMessage"
                    role="alert"
                    style="border: 1px solid rgb(253 230 138); border-radius: 0.75rem; background: rgb(255 251 235); color: rgb(146 64 14); padding: 0.75rem 1rem;"
                ></div>

                {{ $this->form }}

                <x-filament::button type="button" wire:click="login" wire:loading.attr="disabled" :disabled="blank($data['selectedIdentifier'] ?? null)" style="width: 100%;">
                    {{ __('filament-login-shortcut::messages.login_button') }}
                </x-filament::button>
            </div>
        </x-filament::section>
    @endif
</div>
