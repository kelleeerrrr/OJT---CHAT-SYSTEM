<!-- Footer -->
<footer class="bg-white border-t border-gray-200 py-4 mt-auto">
    <div class="container mx-auto px-4">
        <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
            <!-- Copyright -->
            <div class="text-sm text-gray-600">
                &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
            </div>

            <!-- Version -->
            <div class="text-sm text-gray-500">
                v{{ config('app.version', '1.0.0') }}
            </div>

            <!-- Environment Info (non-production only) -->
            @if(config('app.env') !== 'production')
                <div class="text-xs text-gray-400">
                    {{ config('app.enum') }}
                </div>
            @endif
        </div>
    </div>

    <!-- Back to Top Button -->
    <div class="back-to-top">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path>
        </svg>
    </div>
</footer>
