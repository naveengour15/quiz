<nav
    class="fixed bottom-0 w-full z-40 bg-surface-glass backdrop-blur-xl border-t border-glass px-2 pb-6 pt-2 max-w-md mx-auto left-1/2 -translate-x-1/2">
    <div class="flex justify-around items-center w-full">
        @php
            $r = request();
            $activeClass = 'text-primary scale-105 font-bold';
            $inactiveClass = 'text-on-surface-variant hover:text-on-surface opacity-70';
        @endphp

        <a href="{{ route('fsrapp.home') }}"
            class="flex flex-col items-center gap-1 transition-all duration-200 py-1 px-3 rounded-xl {{ $r->routeIs('fsrapp.home') ? $activeClass : $inactiveClass }}">
            <span class="material-symbols-outlined text-2xl" {!! $r->routeIs('fsrapp.home') ? 'style="font-variation-settings: \'FILL\' 1;"' : '' !!}>home</span>
            <span class="font-label-caps text-[10px]">{{ __('app.home') }}</span>
        </a>

        <a href="{{ route('fsrapp.leaderboard') }}"
            class="flex flex-col items-center gap-1 transition-all duration-200 py-1 px-3 rounded-xl {{ $r->routeIs('fsrapp.leaderboard*') ? $activeClass : $inactiveClass }}">
            <span class="material-symbols-outlined text-2xl" {!! $r->routeIs('fsrapp.leaderboard*') ? 'style="font-variation-settings: \'FILL\' 1;"' : '' !!}>leaderboard</span>
            <span class="font-label-caps text-[10px]">{{ __('app.leaderboard') }}</span>
        </a>


        <a href="{{ route('fsrapp.wallet.index') }}"
            class="flex flex-col items-center gap-1 transition-all duration-200 py-1 px-3 rounded-xl {{ $r->routeIs('fsrapp.wallet*') ? $activeClass : $inactiveClass }}">
            <span class="material-symbols-outlined text-2xl" {!! $r->routeIs('fsrapp.wallet*') ? 'style="font-variation-settings: \'FILL\' 1;"' : '' !!}>account_balance_wallet</span>
            <span class="font-label-caps text-[10px]">{{ __('app.points') }}</span>
        </a>


        <a href="{{ route('fsrapp.redeem.index') }}"
            class="flex flex-col items-center gap-1 transition-all duration-200 py-1 px-3 rounded-xl {{ $r->routeIs('fsrapp.redeem*') ? $activeClass : $inactiveClass }}">
            <span class="material-symbols-outlined text-2xl" {!! $r->routeIs('fsrapp.redeem*') ? 'style="font-variation-settings: \'FILL\' 1;"' : '' !!}>redeem</span>
            <span class="font-label-caps text-[10px]">{{ __('app.redeem') }}</span>
        </a>
        <a href="{{ route('fsrapp.profile') }}"
            class="flex flex-col items-center gap-1 transition-all duration-200 py-1 px-3 rounded-xl {{ $r->routeIs('fsrapp.profile*') ? $activeClass : $inactiveClass }}">
            <span class="material-symbols-outlined text-2xl" {!! $r->routeIs('fsrapp.profile*') ? 'style="font-variation-settings: \'FILL\' 1;"' : '' !!}>person</span>
            <span class="font-label-caps text-[10px]">{{ __('app.profile') }}</span>
        </a>
    </div>
</nav>
