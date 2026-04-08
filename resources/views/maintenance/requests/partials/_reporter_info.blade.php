<section>
                <div class="{{ $headCls }}">
                    <div class="{{ $noCls }}">3</div>
                    <div class="{{ $accentWrap }}">
                        <span class="{{ $accentBar }}"></span>
                        <div class="{{ $titleCls }}">ผู้แจ้ง &amp; ความเร่งด่วน</div>
                        <div class="{{ $subCls }}">ข้อมูลผู้แจ้ง / ระดับความเร่งด่วน</div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="space-y-4 text-sm">
                        <div>
                            <div class="text-sm font-medium text-slate-700">ผู้แจ้ง</div>
                            <div class="mt-2 rounded-md border {{ $line }} bg-white px-3 py-2">
                                <div class="font-semibold text-slate-900">
                                    {{ $req->reporter?->name ?? ($req->reporter_name ?? '-') }}
                                </div>
                                @if (($req->reporter?->email ?? $req->reporter_email) || $req->reporter_phone)
                                    <div class="mt-1 text-xs text-slate-500 space-y-0.5">
                                        @if ($req->reporter?->email ?? $req->reporter_email)
                                            <div>{{ $req->reporter?->email ?? $req->reporter_email }}</div>
                                        @endif
                                        @if ($req->reporter_phone)
                                            <div>โทร. {{ $req->reporter_phone }}</div>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="space-y-4 text-sm">
                        <div>
                            <div class="text-sm font-medium text-slate-700">ระดับความเร่งด่วน</div>
                            <div class="mt-2 text-[15px] font-semibold {{ $prioTextTone }}">
                                {{ $prioLabel }}
                            </div>
                        </div>
                    </div>
                </div>
            </section>