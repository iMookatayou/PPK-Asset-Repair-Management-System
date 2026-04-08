<section>
                    <div class="{{ $headCls }}">
                        <div class="{{ $noCls }}">6</div>
                        <div class="{{ $accentWrap }}">
                            <span class="{{ $accentBar }}"></span>
                            <div class="{{ $titleCls }}">ทีมช่างที่รับผิดชอบ</div>
                            <div class="{{ $subCls }}">ผู้ปฏิบัติงาน</div>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <div class="text-[14px] font-semibold text-slate-800">รายชื่อทีมช่าง</div>
                            <div class="text-[12px] text-slate-500">{{ $workers->count() }} คน</div>
                        </div>

                        <div
                            class="rounded-lg border {{ $line }} bg-white max-h-72 overflow-y-auto divide-y divide-slate-200">
                            @if ($workers->isEmpty())
                                <div class="px-4 py-3 text-[13px] text-slate-500">ยังไม่ได้มอบหมายงานให้ทีมช่าง</div>
                            @else
                                @foreach ($workers as $worker)
                                    @php
                                        $assign = $assignments->firstWhere('user_id', $worker->id);
                                        if (
                                            !$assign ||
                                            $assign->status === \App\Models\MaintenanceAssignment::STATUS_CANCELLED
                                        ) {
                                            continue;
                                        }
                                        $isLead = (bool) ($assign->is_lead ?? false);
                                        $avatar = $worker->avatar_thumb_url ?? null;
                                    @endphp
                                    <div class="flex items-center justify-between gap-3 px-4 py-3">
                                        <div class="flex min-w-0 items-center gap-3">
                                            <div
                                                class="h-9 w-9 flex-shrink-0 overflow-hidden rounded-full border {{ $line }} bg-white">
                                                @if ($avatar)
                                                    <img src="{{ $avatar }}" alt="{{ $worker->name }}"
                                                        class="h-full w-full object-cover">
                                                @else
                                                    <div
                                                        class="grid h-full w-full place-items-center text-[11px] text-slate-500 bg-slate-50">
                                                        —</div>
                                                @endif
                                            </div>
                                            <div class="min-w-0 flex-1">
                                                <div class="truncate text-[14px] font-semibold text-slate-900"
                                                    title="{{ $worker->name }}">
                                                    {{ $worker->name }}
                                                </div>
                                                <div class="truncate text-[12px] text-slate-500">
                                                    {{ $worker->role_label ?? ($fallbackRoleLabels[$worker->role ?? 'unknown'] ?? ($worker->role ?? 'unknown')) }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            @endif
                        </div>

                        @can('assign', $req)
                            <div class="flex justify-end">
                                <button type="button" id="openAssignModalBtn"
                                    class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-3.5 py-2 text-[13px] font-semibold
                    text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-200 shadow-sm transition-all active:scale-95">
                                    <img src="/icon/technical-support.avif" class="w-4 h-4 object-contain brightness-0 invert"
                                        alt="Assign">
                                    มอบหมายทีมช่าง
                                </button>
                            </div>
                        @endcan
                    </div>
                </section>