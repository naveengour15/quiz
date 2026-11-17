<!-- ===== 90% Opacity Gold Animated Announcement Component ===== -->
@if (isset($announcements) && $announcements->count() > 0)
    <div id="announcement-wrapper" class="space-y-3 mb-4">
        @foreach ($announcements as $announcement)
            <div id="announcement-card-{{ $announcement->id }}"
                class="fsr-announce flex items-start gap-3.5 relative transition-all duration-300">
                
                <div class="fsr-announce-icon shrink-0 flex items-center justify-center">
                    <span class="material-symbols-outlined text-[20px] font-bold">campaign</span>
                </div>
                
                <div class="flex-1 min-w-0 pr-6 relative z-10">
                    <div class="flex items-center gap-2 mb-1.5">
                        <span class="fsr-announce-badge">ANNOUNCEMENT</span>
                        @if ($announcement->created_at)
                            <span class="text-[10px] text-[#180838]/80 font-mono font-medium">
                                {{ $announcement->created_at->diffForHumans() }}
                            </span>
                        @endif
                    </div>
                    
                    <p class="text-[14px] font-extrabold leading-snug text-[#180838] tracking-wide">
                        {{ $announcement->subject }}
                    </p>
                    
                    <p class="text-[12.5px] mt-1 leading-relaxed text-[#2a0e5c] font-semibold">
                        {!! nl2br(e($announcement->message)) !!}
                    </p>
                </div>
                
                <!-- Close Button -->
                <button type="button" onclick="closeFsrAnnouncement({{ $announcement->id }})"
                    class="absolute top-3 right-3 z-10 w-6 h-6 rounded-full flex items-center justify-center bg-[#180838]/10 text-[#180838]/80 hover:text-[#180838] hover:bg-[#180838]/20 transition-colors"
                    title="Close Announcement">
                    <span class="material-symbols-outlined fsr-announce-close-btn text-[16px]" style="font-size: 16px !important;">close</span>
                </button>
            </div>
        @endforeach
    </div>

    <script>
        function closeFsrAnnouncement(id) {
            const card = document.getElementById('announcement-card-' + id);
            if (card) {
                card.style.opacity = '0';
                card.style.transform = 'translateY(-10px)';
                setTimeout(() => {
                    card.remove();
                    const wrapper = document.getElementById('announcement-wrapper');
                    if (wrapper && wrapper.children.length === 0) {
                        wrapper.remove();
                    }
                }, 300);
            }

            fetch("{{ url('/fsrapp/announcement') }}/" + id + "/close", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            }).catch(err => console.error('Error closing announcement:', err));
        }
    </script>
@endif
