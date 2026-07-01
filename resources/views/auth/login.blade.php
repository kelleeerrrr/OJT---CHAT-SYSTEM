<x-guest-layout>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <div class="auth-title text-center mb-6">
        <h2 class="text-2xl font-semibold" style="color: #eb1c24;">{{ __('Login') }}</h2>
        <p class="text-sm text-gray-600 mt-1">{{ __('Sign in to your account') }}</p>
    </div>

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <!-- Email Address -->
        <div class="mb-4">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mb-4">
            <x-input-label for="password" :value="__('Password')" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <div class="mb-4">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded border-gray-300 shadow-sm focus:ring-indigo-500" name="remember" style="accent-color: #eb1c24;">
                <span class="ms-2 text-sm text-gray-600">{{ __('Remember me') }}</span>
            </label>
        </div>

        <div class="flex items-center justify-between mb-6">
            @if (Route::has('password.request'))
                <a class="text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2" href="{{ route('password.request') }}" style="focus:ring-color: #eb1c24;">
                    {{ __('Forgot your password?') }}
                </a>
            @endif

            <x-primary-button>
                {{ __('Log in') }}
            </x-primary-button>
        </div>

        <div class="text-center text-sm text-gray-600 border-t pt-4">
            <span>{{ __("Don't have an account?") }}</span>
            <a href="{{ route('register') }}" class="font-medium ms-1" style="color: #eb1c24;">
                {{ __('Register here') }}
            </a>
        </div>
    </form>
</x-guest-layout>
