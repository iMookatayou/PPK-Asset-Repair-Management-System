<section>
                    <div class="{{ $headCls }}">
                        <div class="{{ $noCls }}">2</div>
                        <div class="{{ $accentWrap }}">
                            <span class="{{ $accentBar }}"></span>
                            <div class="{{ $titleCls }}">รายละเอียดปัญหา</div>
                            <div class="{{ $subCls }}">หัวข้อและอาการเสีย</div>
                        </div>
                    </div>

                    <div class="space-y-4 text-sm">
                        @php
                            $types = collect($types ?? []);
                            $select = "w-full h-9 rounded-md border $line bg-white px-3 text-sm
                        focus:border-emerald-600 focus:ring-2 focus:ring-emerald-100";
                        @endphp

                        <div>
                            <div class="flex items-center justify-between">
                                <div class="text-sm font-medium text-slate-700">ประเภทงาน (Report Type)</div>
                                @cannot('setType', $req)
                                    <span class="text-[12px] text-slate-400">* เฉพาะเจ้าหน้าที่/ช่างเท่านั้น</span>
                                @endcannot
                            </div>

                            @can('setType', $req)
                                <form method="POST" action="{{ route('maintenance.requests.type.update', $req->id) }}"
                                    class="mt-2 flex gap-2 items-center">
                                    @csrf
                                    <select name="type_id" class="{{ $select }}">
                                        <option value="">— ยังไม่ระบุประเภท —</option>
                                        @foreach ($types as $t)
                                            <option value="{{ $t->id }}" @selected((int) $req->type_id === (int) $t->id)>
                                                {{ $t->name }}</option>
                                        @endforeach
                                    </select>
                                    <button type="submit"
                                        class="inline-flex items-center justify-center h-9 w-9 rounded-md bg-emerald-600 text-white
                               hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-slate-300"
                                        title="บันทึกประเภท">
                                        <span class="material-symbols-outlined ms text-[18px] leading-none">save</span>
                                    </button>
                                </form>
                            @else
                                <div
                                    class="mt-2 rounded-md border {{ $line }} bg-white px-3 py-2 text-sm text-slate-700 min-h-[44px] flex items-center">
                                    {{ $req->type?->name ?? '— ยังไม่ระบุประเภท —' }}
                                </div>
                            @endcan
                        </div>

                        <div>
                            <div class="text-sm font-medium text-slate-700">หัวข้อ <span class="text-rose-600">*</span>
                            </div>
                            <div
                                class="mt-2 rounded-md border {{ $line }} bg-white px-3 py-2 font-semibold text-slate-900 min-h-[44px]">
                                {{ $req->title ?: '-' }}
                            </div>
                        </div>

                        <div>
                            <div class="text-sm font-medium text-slate-700">รายละเอียด / อาการเสีย</div>
                            <div
                                class="mt-2 rounded-md border {{ $line }} bg-white px-3 py-2 text-slate-800 whitespace-pre-line min-h-[120px]">
                                {{ $req->description ?: '—' }}
                            </div>
                        </div>
                    </div>
                </section>