@extends('fsrapp.layouts.app')

@section('content')
    <!-- Header -->
    <header class="fsr-header fixed top-0 w-full z-40 h-16 max-w-md mx-auto left-1/2 -translate-x-1/2"
        style="background: rgba(13, 6, 32, 0.85); backdrop-filter: blur(18px); -webkit-backdrop-filter: blur(18px); border-bottom: 1px solid rgba(255, 255, 255, 0.08);">
        <div class="flex justify-between items-center px-4 h-full w-full">
            <div class="flex items-center gap-3 min-w-0 flex-1">
                <a href="{{ url()->previous() }}"
                    class="w-9 h-9 rounded-full flex items-center justify-center cursor-pointer hover:bg-white/10 transition-colors shrink-0"
                    style="background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.1);">
                    <span class="material-symbols-outlined text-[19px]" style="color:#e8ddff;">arrow_back</span>
                </a>
                <div class="min-w-0 flex-1">
                    <h1 class="font-display text-base font-extrabold tracking-tight truncate" style="color:#e8ddff;">
                        {{ __('app.feedback_history_title') }}
                    </h1>
                    <p class="text-[11px] leading-none mt-0.5" style="color:rgba(232,221,255,0.45);">
                        {{ __('app.feedback_history_subtitle') }}
                    </p>
                </div>
            </div>
        </div>
    </header>

    <main class="max-w-md mx-auto px-4 pt-20 pb-10 min-h-screen relative z-10">
        
        @if($feedbacks->count() > 0)
            <div class="space-y-3">
                @foreach($feedbacks as $item)
                    @php
                        // Status Configuration based on Admin Panel
                        $statusLower = strtolower($item->status);
                        
                        // Badge Colors & Icons
                        $statusConfig = [
                            'closed' => ['color' => 'bg-red-500/20 text-red-300 border-red-500/30', 'icon' => 'cancel', 'label' => __('app.closed')],
                            'replied' => ['color' => 'bg-purple-500/20 text-purple-300 border-purple-500/30', 'icon' => 'reply', 'label' => __('app.replied')],
                            'reviewed' => ['color' => 'bg-blue-500/20 text-blue-300 border-blue-500/30', 'icon' => 'visibility', 'label' => __('app.reviewed')],
                            'pending' => ['color' => 'bg-yellow-500/20 text-yellow-300 border-yellow-500/30', 'icon' => 'schedule', 'label' => __('app.pending')],
                        ];
                        
                        $config = $statusConfig[$statusLower] ?? $statusConfig['pending'];
                        
                        // Format Date
                        $dateStr = \Carbon\Carbon::parse($item->created_at)->format('d M, Y h:i A');
                        
                        // Get status note/message
                        $statusNote = null;
                        if ($statusLower === 'closed' && $item->close_note) {
                            $statusNote = $item->close_note;
                        } elseif ($statusLower === 'replied' && $item->reply_message) {
                            $statusNote = $item->reply_message;
                        } elseif ($statusLower === 'reviewed' && $item->review_note) {
                            $statusNote = $item->review_note;
                        }
                    @endphp

                    <!-- Feedback Item Card -->
                    <div class="p-4 rounded-2xl bg-white/[0.03] border border-white/10 hover:bg-white/[0.05] transition-colors shadow-sm">
                        
                        <!-- Top Row: Subject & Status Badge -->
                        <div class="flex items-start justify-between gap-2 mb-2">
                            <h4 class="text-[13px] font-bold text-white truncate flex-1">
                                {{ $item->subject ?? __('app.general_feedback') }}
                            </h4>
                            <span class="text-[9px] font-bold px-2.5 py-1 rounded-full border {{ $config['color'] }} uppercase tracking-wide shrink-0 flex items-center gap-1">
                                <span class="material-symbols-outlined text-[10px]">{{ $config['icon'] }}</span>
                                {{ $config['label'] }}
                            </span>
                        </div>

                        <!-- User Message -->
                        <div class="mb-3 p-2.5 rounded-lg bg-white/[0.02] border border-white/5">
                            <p class="text-[10px] font-semibold text-white/40 uppercase tracking-wider mb-1">{{ __('app.your_message') }}</p>
                            <p class="text-[12px] text-white/70 leading-relaxed">
                                {{ $item->message }}
                            </p>
                        </div>

                        <!-- Admin Status Note/Reply (If exists) -->
                        @if($statusNote)
                            <div class="p-3 rounded-xl bg-gradient-to-r from-[#0ea5e9]/10 to-[#7c3aed]/10 border border-[#0ea5e9]/30 mb-2">
                                <p class="text-[10px] font-bold text-[#38bdf8] uppercase tracking-wider mb-1.5 flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[14px]">admin_panel_settings</span> 
                                    {{ __('app.admin') }} {{ $config['label'] }}
                                </p>
                                <p class="text-[12px] text-white/90 leading-relaxed">
                                    {{ $statusNote }}
                                </p>
                            </div>
                        @endif

                        <!-- Bottom Row: Date & Status Info -->
                        <div class="flex items-center justify-between pt-2 border-t border-white/5">
                            <span class="text-[10px] text-white/40 font-mono flex items-center gap-1">
                                <span class="material-symbols-outlined text-[12px]">schedule</span> {{ $dateStr }}
                            </span>
                            @if($statusLower !== 'pending')
                                <span class="text-[10px] text-[#38bdf8] font-bold flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[12px]">check_circle</span> 
                                    {{ $config['label'] }}
                                </span>
                            @else
                                <span class="text-[10px] text-yellow-400/80 font-medium flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[12px]">pending</span> 
                                    {{ __('app.awaiting_action') }}
                                </span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <!-- Empty State -->
            <div class="flex flex-col items-center justify-center py-20 text-center">
                <div class="w-16 h-16 rounded-full bg-white/5 border border-white/10 flex items-center justify-center mb-4">
                    <span class="material-symbols-outlined text-4xl text-white/20">inbox</span>
                </div>
                <h3 class="text-base font-bold text-white mb-1">{{ __('app.no_feedback_history') }}</h3>
                <p class="text-xs text-white/50 max-w-[250px]">{{ __('app.no_feedback_yet') }}</p>
                
                <a href="{{ url()->previous() }}" class="mt-6 px-6 py-2.5 rounded-full bg-[#7c3aed] hover:bg-[#6d28d9] text-white text-xs font-bold transition-all active:scale-95">
                    {{ __('app.go_back') }}
                </a>
            </div>
        @endif

    </main>
@endsection