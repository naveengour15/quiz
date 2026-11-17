<style>
    .pagination-nav-list {
        display: flex;
        justify-content: end;
        align-items: end;
        width: 100%;
    }

    @media(max-width:480px){
        .pagination-nav-list {
            display: flex;
            justify-content: center;
            align-items: center;
        }
    }
</style>

{{-- Wrapper hamesha show hoga taaki Dropdown aur Info hamesha accessible rahe --}}
<div class="superadmin-pagination-wrapper">
    
    {{-- LEFT SIDE: Info & Per Page Dropdown (Always Visible) --}}
    <div style="display: flex; align-items: center; gap: 16px; flex-wrap: wrap;     justify-content: space-between;
    width: 100%;">
        <span style="font-size: 13px; color: var(--ink-faint, #6b7280); font-weight: 500; white-space: nowrap;">
            Showing {{ $pageData->firstItem() ?? 0 }}–{{ $pageData->lastItem() ?? 0 }} of {{ $pageData->total() }}
        </span>
        
        <form method="GET" style="display: flex; align-items: center; gap: 8px;">
            <label for="per_page" style="font-size: 13px; color: var(--ink, #374151); font-weight: 500; margin: 0; white-space: nowrap;">
                Rows:
            </label>
            <select name="per_page" id="per_page" onchange="this.form.submit()" 
                    style="padding: 6px 10px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 13px; background: #fff; color: #374151; cursor: pointer; outline: none; transition: border-color 0.2s;"
                    onfocus="this.style.borderColor='#4F46E5'" 
                    onblur="this.style.borderColor='#d1d5db'">
                @foreach ([2, 5, 10, 20, 25, 30, 50, 100, 200, 500] as $size)
                    <option value="{{ $size }}" {{ request('per_page', 25) == $size ? 'selected' : '' }}>
                        {{ $size }}
                    </option>
                @endforeach
            </select>
            
            {{-- Preserve all other GET parameters (search, status, etc.) --}}
            @foreach (request()->except('per_page', 'page') as $key => $value)
                @if(is_array($value))
                    @foreach($value as $val)
                        <input type="hidden" name="{{ $key }}[]" value="{{ $val }}">
                    @endforeach
                @else
                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                @endif
            @endforeach
        </form>
    </div>

    {{-- RIGHT SIDE: Page Links (Sirf tab show hoga jab 1 se zyada pages honge) --}}
    <div class="pagination-nav-list">
    @if ($pageData->hasPages())
        <nav role="navigation" aria-label="Pagination Navigation">
            <ul style="display: flex; align-items: center; gap: 4px; list-style: none; margin: 0; padding: 0;">
                
                @php
                    $currentPage = $pageData->currentPage();
                    $lastPage = $pageData->lastPage();
                    $range = 1;
                @endphp

                {{-- 🔹 Previous Button --}}
                @if ($pageData->onFirstPage())
                    <li style="opacity: 0.5; pointer-events: none;">
                        <span style="display: inline-flex; align-items: center; justify-content: center; min-width: 30px; height: 30px; padding: 0 12px; border: 1px solid #e5e7eb; background: #fff; color: #6b7280; border-radius: 6px; font-size: 13px; font-weight: 500; text-decoration: none;">
                            <i class="ti ti-chevron-left" style="margin-right: 4px;"></i> Prev
                        </span>
                    </li>
                @else
                    <li>
                        <a href="{{ $pageData->previousPageUrl() }}" 
                           style="display: inline-flex; align-items: center; justify-content: center; min-width: 30px; height: 30px; padding: 0 12px; border: 1px solid #e5e7eb; background: #fff; color: #4F46E5; border-radius: 6px; font-size: 13px; font-weight: 500; text-decoration: none; transition: all 0.2s;"
                           onmouseover="this.style.background='#EEF2FF'; this.style.borderColor='#4F46E5';"
                           onmouseout="this.style.background='#fff'; this.style.borderColor='#e5e7eb';">
                            <i class="ti ti-chevron-left" style="margin-right: 4px;"></i> Prev
                        </a>
                    </li>
                @endif

                {{-- 🔹 First Page --}}
                @if ($currentPage > $range + 1)
                    <li>
                        <a href="{{ $pageData->url(1) }}" 
                           style="display: inline-flex; align-items: center; justify-content: center; min-width: 30px; height: 30px; border: 1px solid #e5e7eb; background: #fff; color: #374151; border-radius: 6px; font-size: 13px; font-weight: 500; text-decoration: none; transition: all 0.2s;"
                           onmouseover="this.style.background='#EEF2FF'; this.style.borderColor='#4F46E5'; this.style.color='#4F46E5';"
                           onmouseout="this.style.background='#fff'; this.style.borderColor='#e5e7eb'; this.style.color='#374151';">
                            1
                        </a>
                    </li>
                    @if ($currentPage > $range + 2)
                        <li style="opacity: 0.5;">
                            <span style="display: inline-flex; align-items: center; justify-content: center; min-width: 30px; height: 30px; color: #6b7280; font-size: 13px;">...</span>
                        </li>
                    @endif
                @endif

                {{-- 🔹 Pages Around Current --}}
                @for ($i = max(1, $currentPage - $range); $i <= min($lastPage, $currentPage + $range); $i++)
                    @if ($i == $currentPage)
                        <li>
                            <span class="pagination-number-box">
                                {{ $i }}
                            </span>
                        </li>
                    @else
                        <li>
                            <a href="{{ $pageData->url($i) }}" 
                               style="display: inline-flex; align-items: center; justify-content: center; min-width: 30px; height: 30px; border: 1px solid #e5e7eb; background: #fff; color: #374151; border-radius: 6px; font-size: 13px; font-weight: 500; text-decoration: none; transition: all 0.2s;"
                               onmouseover="this.style.background='#EEF2FF'; this.style.borderColor='#4F46E5'; this.style.color='#4F46E5';"
                               onmouseout="this.style.background='#fff'; this.style.borderColor='#e5e7eb'; this.style.color='#374151';">
                                {{ $i }}
                            </a>
                        </li>
                    @endif
                @endfor

                {{-- 🔹 Last Page --}}
                @if ($currentPage < $lastPage - $range)
                    @if ($currentPage < $lastPage - $range - 1)
                        <li style="opacity: 0.5;">
                            <span style="display: inline-flex; align-items: center; justify-content: center; min-width: 30px; height: 30px; color: #6b7280; font-size: 13px;">...</span>
                        </li>
                    @endif
                    <li>
                        <a href="{{ $pageData->url($lastPage) }}" 
                           style="display: inline-flex; align-items: center; justify-content: center; min-width: 30px; height: 30px; border: 1px solid #e5e7eb; background: #fff; color: #374151; border-radius: 6px; font-size: 13px; font-weight: 500; text-decoration: none; transition: all 0.2s;"
                           onmouseover="this.style.background='#EEF2FF'; this.style.borderColor='#4F46E5'; this.style.color='#4F46E5';"
                           onmouseout="this.style.background='#fff'; this.style.borderColor='#e5e7eb'; this.style.color='#374151';">
                            {{ $lastPage }}
                        </a>
                    </li>
                @endif

                {{-- 🔹 Next Button --}}
                @if ($pageData->hasMorePages())
                    <li>
                        <a href="{{ $pageData->nextPageUrl() }}" 
                           style="display: inline-flex; align-items: center; justify-content: center; min-width: 30px; height: 30px; padding: 0 12px; border: 1px solid #e5e7eb; background: #fff; color: #4F46E5; border-radius: 6px; font-size: 13px; font-weight: 500; text-decoration: none; transition: all 0.2s;"
                           onmouseover="this.style.background='#EEF2FF'; this.style.borderColor='#4F46E5';"
                           onmouseout="this.style.background='#fff'; this.style.borderColor='#e5e7eb';">
                            Next <i class="ti ti-chevron-right" style="margin-left: 4px;"></i>
                        </a>
                    </li>
                @else
                    <li style="opacity: 0.5; pointer-events: none;">
                        <span style="display: inline-flex; align-items: center; justify-content: center; min-width: 30px; height: 30px; padding: 0 12px; border: 1px solid #e5e7eb; background: #fff; color: #6b7280; border-radius: 6px; font-size: 13px; font-weight: 500; text-decoration: none;">
                            Next <i class="ti ti-chevron-right" style="margin-left: 4px;"></i>
                        </span>
                    </li>
                @endif

            </ul>
        </nav>
    @endif
    </div>
</div>

{{-- Responsive Styles --}}
<style>
    .superadmin-pagination-wrapper{
        display: flex; 
        justify-content: space-between; 
        align-items: center; 
        padding: 15px 15px; 
        margin-top: 16px; 
        border-top: 1px solid var(--line, #e5e7eb); 
        flex-wrap: wrap; 
        gap: 16px;"
    }

    .pagination-number-box{
        display: inline-flex; 
        align-items: center; 
        justify-content: center; 
        min-width: 30px; 
        height: 30px; 
        border: 1px solid #4F46E5; 
        background: #4F46E5; 
        color: #fff; 
        border-radius: 6px; 
        font-size: 13px; 
        font-weight: 600;
    }

    @media (max-width: 768px) {
        .superadmin-pagination-wrapper {
            flex-direction: column;
            align-items: stretch;
            gap: 16px;
        }
        .superadmin-pagination-wrapper > div:first-child {
            justify-content: space-between;
        }
        .superadmin-pagination-wrapper nav ul {
            justify-content: center;
            flex-wrap: wrap;
        }
    }

     @media (max-width: 767px) {
        .superadmin-pagination-wrapper {
           border: none !important;
           margin-top: 0 !important;
           padding: 15px 0px;
        }
    }

    @media (max-width: 480px) {
        .superadmin-pagination-wrapper > div:first-child {
            flex-direction: column;
            align-items: flex-start;
            gap: 12px;
        }
    }
</style>