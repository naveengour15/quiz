@forelse ($walletHistory as $transaction)
    @php
        $typeStr = strtolower((string) ($transaction->type ?? ''));
        $isCredit =
            in_array($typeStr, ['credit', 'add', '1']) ||
            ($typeStr !== 'debit' && $typeStr !== 'deduct' && ($transaction->amount ?? 0) >= 0);
        $amt = abs($transaction->amount ?? 0);
        $rawTitle =
            $transaction->title ??
            ($transaction->remarks ??
                ucwords(str_replace(['_', '-'], ' ', $transaction->transaction_type ?? 'Transaction')));
        $title = trim(str_ireplace(['(Add)', '(Adjusted)'], '', $rawTitle));
        $description = $transaction->description ?? '';
        $date = $transaction->created_at ? $transaction->created_at->format('d M Y, h:i A') : '';
    @endphp
    <div class="transaction-item rounded-2xl p-3.5 flex items-center gap-3 transition-all duration-200">
        <div
            class="w-10 h-10 rounded-xl {{ $isCredit ? 'bg-[#00E5A0]/15 text-[#00E5A0] border border-[#00E5A0]/30' : 'bg-[#ff4d4d]/15 text-[#ff4d4d] border border-[#ff4d4d]/30' }} flex items-center justify-center shrink-0">
            <span
                class="material-symbols-outlined text-[20px]">{{ $isCredit ? 'call_received' : 'call_made' }}</span>
        </div>
        <div class="flex-1 min-w-0">
            <p class="text-xs font-bold text-[#e8ddff] truncate">{{ $title }}</p>
            @if ($description)
                <p class="text-[11px] text-white/60 truncate mt-0.5">{{ $description }}</p>
            @endif
            @if ($date)
                <p class="text-[10px] text-white/40 mt-0.5">{{ $date }}</p>
            @endif
        </div>
        <span class="text-xs font-extrabold shrink-0 {{ $isCredit ? 'text-[#00E5A0]' : 'text-[#ff4d4d]' }}">
            {{ $isCredit ? '+' : '-' }}{{ number_format($amt, 0) }} pts
        </span>
    </div>
@empty
    <div class="rounded-2xl p-8 text-center border border-white/10 bg-white/[0.02] backdrop-blur-md">
        <div
            class="w-14 h-14 rounded-2xl bg-white/5 border border-white/10 flex items-center justify-center mx-auto mb-3 text-white/40">
            <span class="material-symbols-outlined text-3xl">account_balance_wallet</span>
        </div>
        <p class="text-xs font-semibold text-white/50">{{ __('app.no_transactions') }}</p>
        <p class="text-[11px] text-white/40 mt-1 max-w-[200px] mx-auto">{{ __('app.transactions_note') }}</p>
    </div>
@endforelse
