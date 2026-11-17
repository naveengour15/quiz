@extends('fsrapp.layouts.app')
@section('content')

    
  <header class="fixed top-0 w-full z-40 bg-surface-glass backdrop-blur-xl border-b border-glass h-16 max-w-md mx-auto left-1/2 -translate-x-1/2">
    <div class="flex justify-between items-center px-container-margin h-full w-full">
      <div class="flex items-center gap-3">
        
            <a href="{{ route('fsrapp.redeem.index') }}" class="w-9 h-9 rounded-full flex items-center justify-center hover:bg-white/10 transition-colors active:scale-90">
              <span class="material-symbols-outlined text-on-surface">arrow_back</span>
            </a>
        <h1 class="font-display text-headline-md font-bold text-primary truncate max-w-[160px]">My Redemptions</h1>
      </div>
      <div class="flex items-center gap-2">
        
      </div>
    </div>
  </header>
    <main class="pt-20 pb-24 px-container-margin min-h-screen" id="redeem-history-main">
      <div class="space-y-gutter"><div class="glass-card rounded-xl skeleton h-16"></div><div class="glass-card rounded-xl skeleton h-16"></div></div>
    </main>
@endsection
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', async () => {
    try {
                const res = await api('/user/redemptions', {
                    method: 'GET'
                });
                const list = asList(res);
                qs('#redeem-history-main').innerHTML = list.length ? `<div class="space-y-gutter">${list.map(r => `
          <div class="glass-card rounded-lg p-3 flex items-center gap-4">
            <div class="w-12 h-12 rounded-lg bg-surface-container flex items-center justify-center"><span class="material-symbols-outlined text-on-surface-variant">card_giftcard</span></div>
            <div class="flex-1">
              <p class="font-headline-md text-body-lg text-on-surface">${escapeHtml(pick(r,['reward_name','name'],'Reward'))}</p>
              <p class="font-body-md text-on-surface-variant text-[12px]">${escapeHtml(pick(r,['status'],'Pending'))} • ${escapeHtml(pick(r,['coins'],'-'))} coins</p>
            </div>
          </div>`).join('')}</div>` :
                    `<p class="text-on-surface-variant text-center py-10">No redemption requests yet.</p>`;
            } catch (e) {
                qs('#redeem-history-main').innerHTML =
                    `<p class="text-error text-center py-10">${escapeHtml(e.message)}</p>`;
            }
});
</script>
@endpush