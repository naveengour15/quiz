@forelse($leaderboard as $entry)
    @php
        $rank = $entry->lb_rank ?? ((($leaderboard->currentPage() - 1) * $leaderboard->perPage()) + $loop->iteration);
        $isMe = auth()->check() && $entry->user_id == auth()->id();
        
        $photoUrl = null;
        if (!empty($entry->profile_photo)) {
            if (\Illuminate\Support\Str::startsWith($entry->profile_photo, ['http://', 'https://'])) {
                $photoUrl = $entry->profile_photo;
            } else {
                $path = ltrim($entry->profile_photo, '/');
                $s3Base = rtrim(config('filesystems.disks.s3.url') ?: env('AWS_URL', 'https://chaloonline.s3.ap-south-1.amazonaws.com'), '/');
                $photoUrl = \Illuminate\Support\Str::startsWith($path, 'storage/') ? asset($path) : ($s3Base . '/' . $path);
            }
        }

        $rowBg = 'hover:bg-white/[0.04]';
        $rankColor = 'text-white/60';
        $ptsStyle = 'text-white/80';
        if ($rank == 1) {
            $rowBg = 'bg-amber-500/10 hover:bg-amber-500/20 border-l-2 border-amber-400';
            $rankColor = 'text-[#ffd76a] font-black';
            $ptsStyle = 'shimmer-gold-text font-black';
        } elseif ($rank == 2) {
            $rowBg = 'bg-white/5 hover:bg-white/10 border-l-2 border-slate-300';
            $rankColor = 'text-slate-300 font-bold';
            $ptsStyle = 'text-slate-200 font-bold';
        } elseif ($rank == 3) {
            $rowBg = 'bg-amber-900/10 hover:bg-amber-900/20 border-l-2 border-amber-600';
            $rankColor = 'text-[#f3d3b3] font-bold';
            $ptsStyle = 'text-[#f3d3b3] font-bold';
        } elseif ($isMe) {
            $rowBg = 'bg-[#00E5A0]/10 hover:bg-[#00E5A0]/20 border-l-2 border-[#00E5A0]';
            $rankColor = 'text-[#00E5A0] font-bold';
            $ptsStyle = 'text-[#00E5A0] font-bold';
        }
    @endphp

    <div class="grid grid-cols-12 px-4 py-3 items-center gap-2 transition-all duration-200 lb-row-animate {{ $rowBg }}" style="animation-delay: {{ min($loop->index * 0.04, 0.4) }}s;">
        <!-- Rank -->
        <div class="col-span-2 flex items-center gap-1 font-mono text-xs {{ $rankColor }}">
            @if ($rank == 1)
                #1 <span class="text-sm">🥇</span> 
            @elseif ($rank == 2)
                #2 <span class="text-sm">🥈</span> 
            @elseif ($rank == 3)
                #3 <span class="text-sm">🥉</span> 
            @else
                #{{ $rank }}
            @endif
        </div>

        

        <!-- Player Info -->
        <div class="col-span-5 flex items-center gap-2.5 min-w-0">
            <div class="w-8 h-8 rounded-full bg-gradient-to-tr from-[#7c3aed]/30 to-[#d946ef]/30 border border-[#e879f9]/30 flex items-center justify-center shrink-0 overflow-hidden text-xs font-bold text-[#e879f9] shadow-sm">
                @if ($photoUrl)
                    <img src="{{ $photoUrl }}" alt="{{ $entry->name }}" width="32" height="32" loading="lazy" decoding="async" class="w-full h-full object-cover" onerror="this.style.display='none'; this.nextElementSibling.style.display='inline';">
                    <span class="hidden">{{ strtoupper(mb_substr(trim($entry->name ?? 'U'), 0, 1)) }}</span>
                @elseif (!empty($entry->name))
                    {{ strtoupper(mb_substr(trim($entry->name), 0, 1)) }}
                @else
                    N
                @endif
            </div>
            <span class="font-display text-xs font-bold text-[#e8ddff] truncate">
                {{ $entry->name ?? 'No player' }}
                @if ($isMe)
                    <span class="text-[10px] text-[#00E5A0] font-mono font-bold">(You)</span>
                @endif
            </span>
        </div>
        <!-- Points -->
         <div class="col-span-2 flex items-center gap-1 font-mono text-xs ">
             {{ number_format($entry->leader_board_point ?? 0) }} 
        </div>

        <!-- Points -->
        <div class="col-span-3 text-right font-mono text-xs {{ $ptsStyle }}">
            {{ number_format($entry->lb_points ?? 0) }} 
        </div>
    </div>
@empty
    <div class="p-8 text-center border-t border-white/5">
        <span class="material-symbols-outlined text-white/20 text-4xl mb-1">emoji_events</span>
        <p class="text-xs text-white/50">No rankings found yet.</p>
    </div>
@endforelse
