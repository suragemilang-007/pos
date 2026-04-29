@extends('layouts.app')

@section('title', 'Kelola User')
@section('page-title', 'Kelola User')

@section('content')
<div class="space-y-5">

    {{-- ── Filter & Aksi ────────────────────────────────────────────────────── --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-4">
        <div class="flex flex-col sm:flex-row gap-3">

            {{-- Filter Role --}}
            <select id="filter-role"
                class="sm:w-44 px-3 py-2 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors"
                onchange="loadUser(1)">
                <option value="">Semua Role</option>
                <option value="kasir">Kasir</option>
                <option value="admin_gudang">Admin Gudang</option>
                <option value="owner">Owner</option>
            </select>

            {{-- Filter Status --}}
            <select id="filter-status"
                class="sm:w-36 px-3 py-2 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors"
                onchange="loadUser(1)">
                <option value="">Semua Status</option>
                <option value="aktif">Aktif</option>
                <option value="nonaktif">Nonaktif</option>
            </select>

            <div class="flex-1"></div>

            {{-- Tombol Tambah --}}
            <button onclick="bukaModalTambah()"
                class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600 text-white text-sm font-medium rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900 shrink-0 whitespace-nowrap">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Tambah User
            </button>
        </div>
    </div>

    {{-- ── Tabel User ───────────────────────────────────────────────────────── --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">

        {{-- Skeleton loading --}}
        <div id="tbl-skeleton" class="p-6 space-y-3">
            @for ($i = 0; $i < 6; $i++)
            <div class="animate-pulse flex gap-4 items-center">
                <div class="w-8 h-8 bg-gray-200 dark:bg-gray-700 rounded-full shrink-0"></div>
                <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-1/5"></div>
                <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-1/6"></div>
                <div class="h-5 bg-gray-200 dark:bg-gray-700 rounded-full w-20"></div>
                <div class="h-5 bg-gray-200 dark:bg-gray-700 rounded-full w-16"></div>
                <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-28"></div>
                <div class="h-8 bg-gray-200 dark:bg-gray-700 rounded-lg w-40 ml-auto"></div>
            </div>
            @endfor
        </div>

        <div id="tbl-wrapper" class="hidden overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Nama</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Username</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-center">Role</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-center">Status</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Login Terakhir</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody id="tbody-user" class="divide-y divide-gray-200 dark:divide-gray-700"></tbody>
            </table>

            <div id="tbl-empty" class="hidden px-6 py-12 text-center">
                <svg class="mx-auto w-12 h-12 text-gray-300 dark:text-gray-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                </svg>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Tidak ada user ditemukan.</p>
            </div>
        </div>

        {{-- Pagination --}}
        <div id="pagination-wrapper" class="hidden px-6 py-3 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <p id="pagination-info" class="text-xs text-gray-500 dark:text-gray-400"></p>
            <div id="pagination-btns" class="flex items-center gap-1"></div>
        </div>
    </div>

</div>
@endsection

{{-- ══════════════════════════════════════════════════════════════════════════
     MODAL TAMBAH USER
══════════════════════════════════════════════════════════════════════════ --}}
<div id="modal-tambah"
    class="hidden fixed inset-0 z-50 items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
    <div class="w-full max-w-md bg-white dark:bg-gray-800 rounded-2xl shadow-2xl overflow-hidden">

        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Tambah User</h3>
            <button onclick="tutupModalTambah()" class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 rounded-lg transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <div class="px-6 py-5 space-y-4">

            {{-- Nama --}}
            <div class="space-y-1">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Nama Lengkap <span class="text-red-500">*</span>
                </label>
                <input id="tambah-nama" type="text" maxlength="100" autocomplete="off"
                    placeholder="Contoh: Budi Santoso"
                    class="block w-full px-3 py-2 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-colors"
                    oninput="clearErr('tambah-err-nama','tambah-nama')">
                <p id="tambah-err-nama" class="hidden text-xs text-red-600 dark:text-red-400"></p>
            </div>

            {{-- Username + validasi unik realtime --}}
            <div class="space-y-1">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Username <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <input id="tambah-username" type="text" maxlength="50" autocomplete="off"
                        placeholder="Contoh: budi.kasir"
                        class="block w-full px-3 py-2 pr-9 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-colors"
                        oninput="cekUsernameDebounce()">
                    {{-- Indikator cek unik --}}
                    <div id="tambah-username-status" class="absolute right-3 top-1/2 -translate-y-1/2 hidden">
                        <svg id="tambah-username-spinner" class="hidden animate-spin w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                        <svg id="tambah-username-ok" class="hidden w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <svg id="tambah-username-conflict" class="hidden w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </div>
                </div>
                <p id="tambah-err-username" class="hidden text-xs text-red-600 dark:text-red-400"></p>
            </div>

            {{-- Password --}}
            <div class="space-y-1">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Password <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <input id="tambah-password" type="password" maxlength="100" autocomplete="new-password"
                        placeholder="Min. 8 karakter"
                        class="block w-full px-3 py-2 pr-9 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-colors"
                        oninput="clearErr('tambah-err-password','tambah-password'); validasiKonfirmasi()">
                    <button type="button" onclick="togglePassword('tambah-password','eye-tambah-pw')"
                        class="absolute right-2.5 top-1/2 -translate-y-1/2 p-0.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                        <svg id="eye-tambah-pw" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                    </button>
                </div>
                <p id="tambah-err-password" class="hidden text-xs text-red-600 dark:text-red-400"></p>
            </div>

            {{-- Konfirmasi Password --}}
            <div class="space-y-1">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Konfirmasi Password <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <input id="tambah-konfirmasi" type="password" maxlength="100" autocomplete="new-password"
                        placeholder="Ulangi password"
                        class="block w-full px-3 py-2 pr-9 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-colors"
                        oninput="validasiKonfirmasi()">
                    <button type="button" onclick="togglePassword('tambah-konfirmasi','eye-tambah-konfirm')"
                        class="absolute right-2.5 top-1/2 -translate-y-1/2 p-0.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                        <svg id="eye-tambah-konfirm" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                    </button>
                </div>
                <p id="tambah-err-konfirmasi" class="hidden text-xs text-red-600 dark:text-red-400"></p>
            </div>

            {{-- Role --}}
            <div class="space-y-1">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Role <span class="text-red-500">*</span>
                </label>
                <select id="tambah-role"
                    class="block w-full px-3 py-2 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors"
                    onchange="clearErr('tambah-err-role','tambah-role')">
                    <option value="">-- Pilih Role --</option>
                    <option value="kasir">Kasir</option>
                    <option value="admin_gudang">Admin Gudang</option>
                    <option value="owner">Owner</option>
                </select>
                <p id="tambah-err-role" class="hidden text-xs text-red-600 dark:text-red-400"></p>
            </div>

        </div>

        <div class="flex justify-end gap-3 px-6 py-4 border-t border-gray-200 dark:border-gray-700">
            <button onclick="tutupModalTambah()"
                class="px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 text-sm font-medium rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                Batal
            </button>
            <button id="btn-tambah-simpan" onclick="simpanTambahUser()"
                class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600 text-white text-sm font-medium rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 disabled:opacity-50 disabled:cursor-not-allowed">
                <svg id="spinner-tambah" class="hidden animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                <span id="lbl-tambah">Simpan</span>
            </button>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════════════
     MODAL EDIT USER (TANPA field password)
══════════════════════════════════════════════════════════════════════════ --}}
<div id="modal-edit"
    class="hidden fixed inset-0 z-50 items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
    <div class="w-full max-w-md bg-white dark:bg-gray-800 rounded-2xl shadow-2xl overflow-hidden">

        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Edit User</h3>
            <button onclick="tutupModalEdit()" class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 rounded-lg transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <div class="px-6 py-5 space-y-4">

            {{-- Nama --}}
            <div class="space-y-1">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Nama Lengkap <span class="text-red-500">*</span>
                </label>
                <input id="edit-nama" type="text" maxlength="100" autocomplete="off"
                    class="block w-full px-3 py-2 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-colors"
                    oninput="clearErr('edit-err-nama','edit-nama')">
                <p id="edit-err-nama" class="hidden text-xs text-red-600 dark:text-red-400"></p>
            </div>

            {{-- Username --}}
            <div class="space-y-1">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Username <span class="text-red-500">*</span>
                </label>
                <input id="edit-username" type="text" maxlength="50" autocomplete="off"
                    class="block w-full px-3 py-2 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-colors"
                    oninput="clearErr('edit-err-username','edit-username')">
                <p id="edit-err-username" class="hidden text-xs text-red-600 dark:text-red-400"></p>
            </div>

            {{-- Role --}}
            <div class="space-y-1">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Role <span class="text-red-500">*</span>
                </label>
                <select id="edit-role"
                    class="block w-full px-3 py-2 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors"
                    onchange="clearErr('edit-err-role','edit-role')">
                    <option value="kasir">Kasir</option>
                    <option value="admin_gudang">Admin Gudang</option>
                    <option value="owner">Owner</option>
                </select>
                <p id="edit-err-role" class="hidden text-xs text-red-600 dark:text-red-400"></p>
            </div>

            {{-- Info: ubah password lewat fitur terpisah --}}
            <div class="flex items-center gap-2 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                <svg class="w-4 h-4 text-blue-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-xs text-blue-700 dark:text-blue-300">
                    Untuk mengubah password, gunakan tombol <strong>Reset Password</strong> pada baris user.
                </p>
            </div>

        </div>

        <div class="flex justify-end gap-3 px-6 py-4 border-t border-gray-200 dark:border-gray-700">
            <button onclick="tutupModalEdit()"
                class="px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 text-sm font-medium rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                Batal
            </button>
            <button id="btn-edit-simpan" onclick="simpanEditUser()"
                class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600 text-white text-sm font-medium rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 disabled:opacity-50 disabled:cursor-not-allowed">
                <svg id="spinner-edit" class="hidden animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                <span id="lbl-edit">Perbarui</span>
            </button>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════════════
     MODAL RESET PASSWORD
══════════════════════════════════════════════════════════════════════════ --}}
<div id="modal-reset"
    class="hidden fixed inset-0 z-50 items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
    <div class="w-full max-w-sm bg-white dark:bg-gray-800 rounded-2xl shadow-2xl overflow-hidden">

        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <div>
                <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Reset Password</h3>
                <p id="reset-subtitle" class="text-xs text-gray-500 dark:text-gray-400 mt-0.5"></p>
            </div>
            <button onclick="tutupModalReset()" class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 rounded-lg transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <div class="px-6 py-5 space-y-4">

            {{-- Password Baru --}}
            <div class="space-y-1">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Password Baru <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <input id="reset-password" type="password" maxlength="100" autocomplete="new-password"
                        placeholder="Min. 8 karakter"
                        class="block w-full px-3 py-2 pr-9 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-colors"
                        oninput="clearErr('reset-err-password','reset-password'); validasiResetKonfirmasi()">
                    <button type="button" onclick="togglePassword('reset-password','eye-reset-pw')"
                        class="absolute right-2.5 top-1/2 -translate-y-1/2 p-0.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                        <svg id="eye-reset-pw" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                    </button>
                </div>
                <p id="reset-err-password" class="hidden text-xs text-red-600 dark:text-red-400"></p>
            </div>

            {{-- Konfirmasi Password Baru --}}
            <div class="space-y-1">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Konfirmasi Password Baru <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <input id="reset-konfirmasi" type="password" maxlength="100" autocomplete="new-password"
                        placeholder="Ulangi password baru"
                        class="block w-full px-3 py-2 pr-9 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-colors"
                        oninput="validasiResetKonfirmasi()">
                    <button type="button" onclick="togglePassword('reset-konfirmasi','eye-reset-konfirm')"
                        class="absolute right-2.5 top-1/2 -translate-y-1/2 p-0.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                        <svg id="eye-reset-konfirm" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                    </button>
                </div>
                <p id="reset-err-konfirmasi" class="hidden text-xs text-red-600 dark:text-red-400"></p>
            </div>

        </div>

        <div class="flex justify-end gap-3 px-6 py-4 border-t border-gray-200 dark:border-gray-700">
            <button onclick="tutupModalReset()"
                class="px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 text-sm font-medium rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                Batal
            </button>
            <button id="btn-reset-simpan" onclick="eksekusiResetPassword()"
                class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600 text-white text-sm font-medium rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 disabled:opacity-50 disabled:cursor-not-allowed">
                <svg id="spinner-reset" class="hidden animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                <span id="lbl-reset">Reset Password</span>
            </button>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════════════
     MODAL KONFIRMASI NONAKTIFKAN
══════════════════════════════════════════════════════════════════════════ --}}
<div id="modal-nonaktif"
    class="hidden fixed inset-0 z-50 items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
    <div class="w-full max-w-sm bg-white dark:bg-gray-800 rounded-2xl shadow-2xl overflow-hidden">

        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Nonaktifkan User</h3>
        </div>

        <div class="px-6 py-5">
            <div class="flex items-start gap-3">
                <div class="shrink-0 w-10 h-10 rounded-full bg-red-100 dark:bg-red-900/40 flex items-center justify-center">
                    <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <div>
                    <p id="nonaktif-pesan" class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed"></p>
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-2">
                        Semua sesi aktif user ini akan dihapus secara otomatis dan user tidak dapat login kembali sampai diaktifkan oleh owner.
                    </p>
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-3 px-6 py-4 border-t border-gray-200 dark:border-gray-700">
            <button onclick="tutupModalNonaktif()"
                class="px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 text-sm font-medium rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                Batal
            </button>
            <button id="btn-nonaktif-ok" onclick="eksekusiNonaktif()"
                class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 disabled:opacity-50 disabled:cursor-not-allowed">
                <svg id="spinner-nonaktif" class="hidden animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                <span id="lbl-nonaktif">Ya, Nonaktifkan</span>
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
// ── State ─────────────────────────────────────────────────────────────────────
let userList        = [];
let currentPage     = 1;
let editingId       = null;
let resetTargetId   = null;
let nonaktifTarget  = null;
let usernameTimer   = null;
let usernameValid   = false;   // hasil cek unik realtime

// ── Load User ─────────────────────────────────────────────────────────────────
async function loadUser(page = 1) {
    currentPage = page;

    const role   = document.getElementById('filter-role').value;
    const status = document.getElementById('filter-status').value;

    const params = new URLSearchParams({ page, limit: 20 });
    if (role)   params.set('role',   role);
    if (status) params.set('status', status);

    document.getElementById('tbl-skeleton').classList.remove('hidden');
    document.getElementById('tbl-wrapper').classList.add('hidden');
    document.getElementById('pagination-wrapper').classList.add('hidden');

    try {
        const { data } = await axios.get('/api/owner/user?' + params.toString());
        userList = data.data || [];
        renderTabel(userList, data.pagination);
    } catch (err) {
        showToast('error', 'Gagal memuat data', err.response?.data?.message || 'Periksa koneksi');
        document.getElementById('tbl-skeleton').classList.add('hidden');
        document.getElementById('tbl-wrapper').classList.remove('hidden');
    }
}

function renderTabel(rows, pagination) {
    document.getElementById('tbl-skeleton').classList.add('hidden');
    document.getElementById('tbl-wrapper').classList.remove('hidden');

    const tbody = document.getElementById('tbody-user');
    const empty = document.getElementById('tbl-empty');

    if (!rows.length) {
        tbody.innerHTML = '';
        empty.classList.remove('hidden');
        document.getElementById('pagination-wrapper').classList.add('hidden');
        return;
    }

    empty.classList.add('hidden');

    const roleLabel = { kasir: 'Kasir', admin_gudang: 'Admin Gudang', owner: 'Owner' };
    const roleBadgeClass = {
        kasir:        'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
        admin_gudang: 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400',
        owner:        'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400',
    };

    tbody.innerHTML = rows.map(u => {
        const roleBadge = `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${roleBadgeClass[u.role] || 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400'}">${esc(roleLabel[u.role] || u.role)}</span>`;

        const statusBadge = u.status === 'aktif'
            ? `<span id="status-badge-${u.id}" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">Aktif</span>`
            : `<span id="status-badge-${u.id}" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400">Nonaktif</span>`;

        const loginTerakhir = u.login_terakhir
            ? `<span class="text-sm text-gray-600 dark:text-gray-400">${fmtDatetime(u.login_terakhir)}</span>`
            : `<span class="text-xs text-gray-400 dark:text-gray-500">Belum pernah login</span>`;

        // Tombol Nonaktifkan hanya muncul jika user masih aktif
        const btnNonaktif = u.status === 'aktif'
            ? `<button onclick="bukaModalNonaktif(${u.id})"
                class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 hover:bg-red-100 dark:hover:bg-red-900/30 rounded-lg transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                </svg>
                Nonaktifkan
            </button>`
            : '';

        return `
        <tr id="row-${u.id}" class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
            <td class="px-4 py-3">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-indigo-100 dark:bg-indigo-900/40 flex items-center justify-center shrink-0">
                        <span class="text-sm font-bold text-indigo-600 dark:text-indigo-400">
                            ${esc((u.nama || '?').charAt(0).toUpperCase())}
                        </span>
                    </div>
                    <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">${esc(u.nama)}</p>
                </div>
            </td>
            <td class="px-4 py-3">
                <span class="text-sm font-mono text-gray-600 dark:text-gray-400">@${esc(u.username)}</span>
            </td>
            <td class="px-4 py-3 text-center">${roleBadge}</td>
            <td class="px-4 py-3 text-center">${statusBadge}</td>
            <td class="px-4 py-3">${loginTerakhir}</td>
            <td class="px-4 py-3 text-right">
                <div class="inline-flex items-center gap-1 flex-wrap justify-end">
                    <button onclick="bukaModalEdit(${u.id})"
                        class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-900/30 hover:bg-indigo-100 dark:hover:bg-indigo-900/50 rounded-lg transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        Edit
                    </button>
                    <button onclick="bukaModalReset(${u.id})"
                        class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium text-gray-600 dark:text-gray-400 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                        </svg>
                        Reset Password
                    </button>
                    ${btnNonaktif}
                </div>
            </td>
        </tr>`;
    }).join('');

    renderPagination(pagination);
}

function renderPagination(pg) {
    if (!pg || pg.total <= pg.per_page) {
        document.getElementById('pagination-wrapper').classList.add('hidden');
        return;
    }

    document.getElementById('pagination-wrapper').classList.remove('hidden');
    document.getElementById('pagination-info').textContent =
        `Menampilkan ${Math.min((pg.current_page - 1) * pg.per_page + 1, pg.total)}–${Math.min(pg.current_page * pg.per_page, pg.total)} dari ${pg.total} user`;

    const container = document.getElementById('pagination-btns');
    const pages = [];
    for (let i = 1; i <= pg.last_page; i++) {
        if (i === 1 || i === pg.last_page || Math.abs(i - pg.current_page) <= 1) pages.push(i);
        else if (pages[pages.length - 1] !== '...') pages.push('...');
    }

    container.innerHTML = pages.map(p =>
        p === '...'
            ? `<span class="px-2 py-1 text-xs text-gray-400">…</span>`
            : `<button onclick="loadUser(${p})"
                class="px-3 py-1.5 text-xs font-medium rounded-lg transition-colors ${p === pg.current_page
                    ? 'bg-indigo-600 text-white'
                    : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700'}">${p}</button>`
    ).join('');
}

// ── Modal Tambah User ─────────────────────────────────────────────────────────
function bukaModalTambah() {
    usernameValid = false;

    ['tambah-nama','tambah-username','tambah-password','tambah-konfirmasi'].forEach(id => {
        document.getElementById(id).value = '';
    });
    document.getElementById('tambah-role').value = '';

    ['tambah-err-nama','tambah-err-username','tambah-err-password','tambah-err-konfirmasi','tambah-err-role'].forEach(id => {
        const el = document.getElementById(id);
        el.classList.add('hidden');
        el.textContent = '';
    });

    ['tambah-nama','tambah-username','tambah-password','tambah-konfirmasi'].forEach(id => {
        document.getElementById(id).classList.remove('border-red-500','dark:border-red-400');
    });

    setUsernameStatusIcon(null);

    tampilkanModal('modal-tambah');
    setTimeout(() => document.getElementById('tambah-nama').focus(), 100);
}

function tutupModalTambah() {
    sembunyikanModal('modal-tambah');
    clearTimeout(usernameTimer);
}

async function simpanTambahUser() {
    const nama      = document.getElementById('tambah-nama').value.trim();
    const username  = document.getElementById('tambah-username').value.trim();
    const password  = document.getElementById('tambah-password').value;
    const konfirm   = document.getElementById('tambah-konfirmasi').value;
    const role      = document.getElementById('tambah-role').value;

    let ok = true;

    // Bersihkan semua error terlebih dahulu
    ['tambah-err-nama','tambah-err-username','tambah-err-password','tambah-err-konfirmasi','tambah-err-role'].forEach(id => {
        document.getElementById(id).classList.add('hidden');
    });
    ['tambah-nama','tambah-username','tambah-password','tambah-konfirmasi'].forEach(id => {
        document.getElementById(id).classList.remove('border-red-500','dark:border-red-400');
    });

    if (!nama)     { tampilkanErr('tambah-err-nama',      'tambah-nama',      'Nama wajib diisi'); ok = false; }
    if (!username) { tampilkanErr('tambah-err-username',  'tambah-username',  'Username wajib diisi'); ok = false; }
    if (!password) { tampilkanErr('tambah-err-password',  'tambah-password',  'Password wajib diisi'); ok = false; }
    else if (password.length < 8) { tampilkanErr('tambah-err-password', 'tambah-password', 'Password minimal 8 karakter'); ok = false; }
    if (!konfirm)  { tampilkanErr('tambah-err-konfirmasi','tambah-konfirmasi','Konfirmasi password wajib diisi'); ok = false; }
    else if (password !== konfirm) { tampilkanErr('tambah-err-konfirmasi','tambah-konfirmasi','Password dan konfirmasi tidak cocok'); ok = false; }
    if (!role)     { tampilkanErr('tambah-err-role',      'tambah-role',      'Pilih role untuk user ini'); ok = false; }

    // Tolak jika username diketahui sudah terpakai (dari cek realtime)
    if (username && !usernameValid) {
        tampilkanErr('tambah-err-username','tambah-username','Username sudah digunakan atau belum diverifikasi');
        ok = false;
    }

    if (!ok) return;

    setTambahLoading(true);

    try {
        await axios.post('/api/owner/user', { nama, username, password, role });
        showToast('success', 'User Ditambahkan', `"${nama}" berhasil ditambahkan sebagai ${roleLabelStr(role)}.`);
        tutupModalTambah();
        await loadUser(1);
    } catch (err) {
        const errors = err.response?.data?.errors;
        if (errors?.username?.[0]) tampilkanErr('tambah-err-username','tambah-username', errors.username[0]);
        else if (errors?.nama?.[0]) tampilkanErr('tambah-err-nama','tambah-nama', errors.nama[0]);
        else showToast('error', 'Gagal menyimpan', err.response?.data?.message || 'Terjadi kesalahan');
    } finally {
        setTambahLoading(false);
    }
}

function setTambahLoading(on) {
    document.getElementById('btn-tambah-simpan').disabled = on;
    document.getElementById('spinner-tambah').classList.toggle('hidden', !on);
    document.getElementById('lbl-tambah').textContent = on ? 'Menyimpan...' : 'Simpan';
}

// ── Validasi Username Realtime (debounce 500ms) ───────────────────────────────
function cekUsernameDebounce() {
    clearErr('tambah-err-username','tambah-username');
    usernameValid = false;
    setUsernameStatusIcon('loading');

    clearTimeout(usernameTimer);
    const val = document.getElementById('tambah-username').value.trim();

    if (!val) {
        setUsernameStatusIcon(null);
        return;
    }

    usernameTimer = setTimeout(() => cekUsernameUnik(val), 500);
}

async function cekUsernameUnik(username) {
    try {
        // Backend harus support GET /api/owner/user/cek-username?username=xxx
        // Response: { tersedia: true|false }
        const { data } = await axios.get('/api/owner/user/cek-username', { params: { username } });
        if (data.tersedia) {
            usernameValid = true;
            setUsernameStatusIcon('ok');
            clearErr('tambah-err-username','tambah-username');
        } else {
            usernameValid = false;
            setUsernameStatusIcon('conflict');
            tampilkanErr('tambah-err-username','tambah-username','Username sudah digunakan');
        }
    } catch (_) {
        // Jika endpoint belum ada / error network — biarkan server validasi saat submit
        usernameValid = true;
        setUsernameStatusIcon(null);
    }
}

function setUsernameStatusIcon(state) {
    const wrap     = document.getElementById('tambah-username-status');
    const spinner  = document.getElementById('tambah-username-spinner');
    const ok       = document.getElementById('tambah-username-ok');
    const conflict = document.getElementById('tambah-username-conflict');

    if (!state) {
        wrap.classList.add('hidden');
        spinner.classList.add('hidden'); ok.classList.add('hidden'); conflict.classList.add('hidden');
        return;
    }

    wrap.classList.remove('hidden');
    spinner.classList.toggle('hidden',  state !== 'loading');
    ok.classList.toggle('hidden',       state !== 'ok');
    conflict.classList.toggle('hidden', state !== 'conflict');
}

// ── Validasi Konfirmasi Password (Tambah) ─────────────────────────────────────
function validasiKonfirmasi() {
    const pw  = document.getElementById('tambah-password').value;
    const cfm = document.getElementById('tambah-konfirmasi').value;

    if (!cfm) return;

    if (pw !== cfm) {
        tampilkanErr('tambah-err-konfirmasi','tambah-konfirmasi','Password dan konfirmasi tidak cocok');
    } else {
        clearErr('tambah-err-konfirmasi','tambah-konfirmasi');
    }
}

// ── Modal Edit User ───────────────────────────────────────────────────────────
function bukaModalEdit(id) {
    const u = userList.find(u => u.id === id);
    if (!u) return;

    editingId = id;

    document.getElementById('edit-nama').value     = u.nama;
    document.getElementById('edit-username').value = u.username;
    document.getElementById('edit-role').value     = u.role;

    ['edit-err-nama','edit-err-username','edit-err-role'].forEach(id => {
        const el = document.getElementById(id);
        el.classList.add('hidden');
        el.textContent = '';
    });

    ['edit-nama','edit-username'].forEach(id => {
        document.getElementById(id).classList.remove('border-red-500','dark:border-red-400');
    });

    tampilkanModal('modal-edit');
    setTimeout(() => document.getElementById('edit-nama').focus(), 100);
}

function tutupModalEdit() {
    sembunyikanModal('modal-edit');
    editingId = null;
}

async function simpanEditUser() {
    if (!editingId) return;

    const nama     = document.getElementById('edit-nama').value.trim();
    const username = document.getElementById('edit-username').value.trim();
    const role     = document.getElementById('edit-role').value;

    ['edit-err-nama','edit-err-username','edit-err-role'].forEach(id => document.getElementById(id).classList.add('hidden'));
    ['edit-nama','edit-username'].forEach(id => document.getElementById(id).classList.remove('border-red-500','dark:border-red-400'));

    let ok = true;
    if (!nama)     { tampilkanErr('edit-err-nama',     'edit-nama',     'Nama wajib diisi'); ok = false; }
    if (!username) { tampilkanErr('edit-err-username', 'edit-username', 'Username wajib diisi'); ok = false; }
    if (!role)     { tampilkanErr('edit-err-role',     'edit-role',     'Role wajib dipilih'); ok = false; }
    if (!ok) return;

    setEditLoading(true);

    try {
        await axios.put(`/api/owner/user/${editingId}`, { nama, username, role });
        showToast('success', 'User Diperbarui', `Data "${nama}" berhasil diperbarui.`);
        tutupModalEdit();
        await loadUser(currentPage);
    } catch (err) {
        const errors = err.response?.data?.errors;
        if (errors?.username?.[0]) tampilkanErr('edit-err-username','edit-username', errors.username[0]);
        else if (errors?.nama?.[0]) tampilkanErr('edit-err-nama','edit-nama', errors.nama[0]);
        else showToast('error', 'Gagal memperbarui', err.response?.data?.message || 'Terjadi kesalahan');
    } finally {
        setEditLoading(false);
    }
}

function setEditLoading(on) {
    document.getElementById('btn-edit-simpan').disabled = on;
    document.getElementById('spinner-edit').classList.toggle('hidden', !on);
    document.getElementById('lbl-edit').textContent = on ? 'Menyimpan...' : 'Perbarui';
}

// ── Modal Reset Password ──────────────────────────────────────────────────────
function bukaModalReset(id) {
    const u = userList.find(u => u.id === id);
    if (!u) return;

    resetTargetId = id;
    document.getElementById('reset-subtitle').textContent = `${u.nama} (@${u.username})`;
    document.getElementById('reset-password').value    = '';
    document.getElementById('reset-konfirmasi').value  = '';

    ['reset-err-password','reset-err-konfirmasi'].forEach(id => {
        const el = document.getElementById(id);
        el.classList.add('hidden');
        el.textContent = '';
    });

    ['reset-password','reset-konfirmasi'].forEach(id => {
        document.getElementById(id).classList.remove('border-red-500','dark:border-red-400');
    });

    tampilkanModal('modal-reset');
    setTimeout(() => document.getElementById('reset-password').focus(), 100);
}

function tutupModalReset() {
    sembunyikanModal('modal-reset');
    resetTargetId = null;
}

function validasiResetKonfirmasi() {
    const pw  = document.getElementById('reset-password').value;
    const cfm = document.getElementById('reset-konfirmasi').value;

    if (!cfm) return;

    if (pw !== cfm) {
        tampilkanErr('reset-err-konfirmasi','reset-konfirmasi','Password dan konfirmasi tidak cocok');
    } else {
        clearErr('reset-err-konfirmasi','reset-konfirmasi');
    }
}

async function eksekusiResetPassword() {
    if (!resetTargetId) return;

    const password = document.getElementById('reset-password').value;
    const konfirm  = document.getElementById('reset-konfirmasi').value;

    ['reset-err-password','reset-err-konfirmasi'].forEach(id => document.getElementById(id).classList.add('hidden'));
    ['reset-password','reset-konfirmasi'].forEach(id => document.getElementById(id).classList.remove('border-red-500','dark:border-red-400'));

    let ok = true;
    if (!password)         { tampilkanErr('reset-err-password',   'reset-password',   'Password baru wajib diisi'); ok = false; }
    else if (password.length < 8) { tampilkanErr('reset-err-password', 'reset-password', 'Password minimal 8 karakter'); ok = false; }
    if (!konfirm)          { tampilkanErr('reset-err-konfirmasi','reset-konfirmasi', 'Konfirmasi password wajib diisi'); ok = false; }
    else if (password !== konfirm) { tampilkanErr('reset-err-konfirmasi','reset-konfirmasi','Password dan konfirmasi tidak cocok'); ok = false; }
    if (!ok) return;

    setResetLoading(true);

    try {
        await axios.patch(`/api/owner/user/${resetTargetId}/reset-password`, { password_baru: password });
        showToast('success', 'Password Direset', 'Password user berhasil diperbarui.');
        tutupModalReset();
    } catch (err) {
        const errors = err.response?.data?.errors;
        if (errors?.password_baru?.[0]) tampilkanErr('reset-err-password','reset-password', errors.password_baru[0]);
        else showToast('error', 'Gagal reset password', err.response?.data?.message || 'Terjadi kesalahan');
    } finally {
        setResetLoading(false);
    }
}

function setResetLoading(on) {
    document.getElementById('btn-reset-simpan').disabled = on;
    document.getElementById('spinner-reset').classList.toggle('hidden', !on);
    document.getElementById('lbl-reset').textContent = on ? 'Mereset...' : 'Reset Password';
}

// ── Modal Nonaktifkan ─────────────────────────────────────────────────────────
function bukaModalNonaktif(id) {
    const u = userList.find(u => u.id === id);
    if (!u) return;

    nonaktifTarget = u;

    const roleStr = roleLabelStr(u.role);
    document.getElementById('nonaktif-pesan').innerHTML =
        `User <strong class="text-gray-900 dark:text-gray-100">${esc(u.nama)}</strong> ` +
        `(${esc(roleStr)}) akan dinonaktifkan dan semua sesi aktifnya akan dihapus secara otomatis. Lanjutkan?`;

    tampilkanModal('modal-nonaktif');
}

function tutupModalNonaktif() {
    sembunyikanModal('modal-nonaktif');
    nonaktifTarget = null;
}

async function eksekusiNonaktif() {
    if (!nonaktifTarget) return;

    const btn = document.getElementById('btn-nonaktif-ok');
    btn.disabled = true;
    document.getElementById('spinner-nonaktif').classList.remove('hidden');
    document.getElementById('lbl-nonaktif').textContent = 'Memproses...';

    try {
        await axios.patch(`/api/owner/user/${nonaktifTarget.id}/nonaktifkan`);

        // Update badge status di baris tabel tanpa reload penuh — AD-15 step-by-step
        const badge = document.getElementById(`status-badge-${nonaktifTarget.id}`);
        if (badge) {
            badge.className = 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400';
            badge.textContent = 'Nonaktif';
        }

        // Sembunyikan tombol "Nonaktifkan" di baris tersebut
        const row = document.getElementById(`row-${nonaktifTarget.id}`);
        if (row) {
            const btnNonaktifEl = row.querySelector('button[onclick^="bukaModalNonaktif"]');
            if (btnNonaktifEl) btnNonaktifEl.remove();
        }

        // Perbarui state lokal agar modal edit tidak tampil data lama
        const idx = userList.findIndex(u => u.id === nonaktifTarget.id);
        if (idx !== -1) userList[idx].status = 'nonaktif';

        showToast('warning', 'User Dinonaktifkan',
            `"${nonaktifTarget.nama}" telah dinonaktifkan. Semua sesi aktif telah dihapus.`);

        tutupModalNonaktif();
    } catch (err) {
        showToast('error', 'Gagal', err.response?.data?.message || 'Terjadi kesalahan');
        tutupModalNonaktif();
    } finally {
        btn.disabled = false;
        document.getElementById('spinner-nonaktif').classList.add('hidden');
        document.getElementById('lbl-nonaktif').textContent = 'Ya, Nonaktifkan';
    }
}

// ── Utilitas Modal ────────────────────────────────────────────────────────────
function tampilkanModal(id) {
    const el = document.getElementById(id);
    el.classList.remove('hidden');
    el.classList.add('flex');
}

function sembunyikanModal(id) {
    const el = document.getElementById(id);
    el.classList.add('hidden');
    el.classList.remove('flex');
}

// ── Utilitas Error Form ───────────────────────────────────────────────────────
function tampilkanErr(errId, inputId, msg) {
    const e = document.getElementById(errId);
    if (!e) return;
    e.textContent = msg;
    e.classList.remove('hidden');
    document.getElementById(inputId)?.classList.add('border-red-500','dark:border-red-400');
}

function clearErr(errId, inputId) {
    const e = document.getElementById(errId);
    e?.classList.add('hidden');
    document.getElementById(inputId)?.classList.remove('border-red-500','dark:border-red-400');
}

// ── Toggle Password Visibility ────────────────────────────────────────────────
function togglePassword(inputId, iconId) {
    const input = document.getElementById(inputId);
    const isText = input.type === 'text';
    input.type = isText ? 'password' : 'text';

    // Ganti ikon eye / eye-off
    const icon = document.getElementById(iconId);
    if (isText) {
        icon.innerHTML = `
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>`;
    } else {
        icon.innerHTML = `
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>`;
    }
}

// ── Utilitas Format ───────────────────────────────────────────────────────────
function fmtDatetime(iso) {
    if (!iso) return '—';
    const d = new Date(iso);
    return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })
        + ', ' + d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
}

function roleLabelStr(role) {
    return { kasir: 'Kasir', admin_gudang: 'Admin Gudang', owner: 'Owner' }[role] || role;
}

function esc(s) {
    if (s == null) return '';
    return String(s)
        .replace(/&/g,'&amp;').replace(/</g,'&lt;')
        .replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
}

// ── Tutup modal dengan klik backdrop ─────────────────────────────────────────
['modal-tambah','modal-edit','modal-reset','modal-nonaktif'].forEach(id => {
    document.getElementById(id)?.addEventListener('click', function (e) {
        if (e.target === this) {
            sembunyikanModal(id);
        }
    });
});

// ── Init ──────────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function () {
    // Pastikan role yang login adalah owner
    if (authRole !== 'owner') {
        window.location.replace('/');
        return;
    }

    loadUser(1);
});
</script>
@endpush
