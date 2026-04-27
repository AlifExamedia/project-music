<div class="max-w-4xl mx-auto py-8 px-4">

    {{-- Header --}}
    <div class="mb-8 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-white">Upload Music</h1>
            <p class="text-sm text-zinc-400 mt-1">
                Library: {{ count($existingSongs) }} songs &bull;
                Files on disk: {{ $totalFiles }}
                @if($totalFiles > count($existingSongs))
                    &bull; <span class="text-yellow-400">{{ $totalFiles - count($existingSongs) }} unimported</span>
                @endif
            </p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('player') }}" class="um-btn um-btn-ghost">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2z"/></svg>
                Player
            </a>
            <button wire:click="scanExisting" wire:loading.attr="disabled" class="um-btn um-btn-secondary">
                <span wire:loading.remove wire:target="scanExisting">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    Scan &amp; Import Existing
                </span>
                <span wire:loading wire:target="scanExisting">Scanning…</span>
            </button>
        </div>
    </div>

    {{-- Flash messages --}}
    @if($successMessage)
        <div class="um-alert um-alert-success mb-6">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            {{ $successMessage }}
        </div>
    @endif
    @if($errorMessage)
        <div class="um-alert um-alert-error mb-6">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            {{ $errorMessage }}
        </div>
    @endif

    {{-- Upload form --}}
    <div class="um-card mb-8">
        <h2 class="text-lg font-semibold text-white mb-5">Add New Song</h2>

        <form wire:submit="upload" class="space-y-5">

            {{-- File inputs row --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="um-label">MP3 File <span class="text-red-400">*</span></label>
                    <input
                        type="file"
                        wire:model="mp3File"
                        accept=".mp3,audio/mpeg"
                        class="um-file-input"
                    >
                    <div wire:loading wire:target="mp3File" class="text-xs text-zinc-400 mt-1">Analysing file…</div>
                    @error('mp3File') <p class="um-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="um-label">
                        Cover Art
                        <span class="text-zinc-500 font-normal">(optional)</span>
                        @if($hasCoverArt && !$coverFile)
                            <span class="ml-2 text-xs text-green-400">✓ embedded in MP3</span>
                        @endif
                    </label>
                    <input
                        type="file"
                        wire:model="coverFile"
                        accept="image/*"
                        class="um-file-input"
                    >
                    @error('coverFile') <p class="um-error">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- Metadata --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="um-label">Title <span class="text-red-400">*</span></label>
                    <input type="text" wire:model="title" placeholder="Song title" class="um-input">
                    @error('title') <p class="um-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="um-label">Artist</label>
                    <input type="text" wire:model="artist" placeholder="Artist name" class="um-input">
                </div>
                <div>
                    <label class="um-label">Album</label>
                    <input type="text" wire:model="album" placeholder="Album name" class="um-input">
                </div>
                <div>
                    <label class="um-label">Genre</label>
                    <input type="text" wire:model="genre" placeholder="Genre" class="um-input">
                </div>
                <div>
                    <label class="um-label">Year</label>
                    <input type="text" wire:model="year" placeholder="e.g. 2024" class="um-input">
                </div>
                <div>
                    <label class="um-label">Track #</label>
                    <input type="text" wire:model="track" placeholder="e.g. 1" class="um-input">
                </div>
            </div>

            @if($duration)
                <p class="text-sm text-zinc-400">Duration: <span class="text-white font-mono">{{ $duration }}</span></p>
            @endif

            <div class="flex justify-end pt-1">
                <button type="submit" wire:loading.attr="disabled" class="um-btn um-btn-primary">
                    <span wire:loading.remove wire:target="upload">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                        Upload Song
                    </span>
                    <span wire:loading wire:target="upload">Uploading…</span>
                </button>
            </div>
        </form>
    </div>

    {{-- Library table --}}
    @if(count($existingSongs) > 0)
        <div class="um-card">
            <h2 class="text-lg font-semibold text-white mb-4">Library ({{ count($existingSongs) }})</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-zinc-700">
                            <th class="text-left py-2 px-3 text-zinc-400 font-medium w-10">#</th>
                            <th class="text-left py-2 px-3 text-zinc-400 font-medium">Title</th>
                            <th class="text-left py-2 px-3 text-zinc-400 font-medium hidden md:table-cell">Artist</th>
                            <th class="text-left py-2 px-3 text-zinc-400 font-medium w-10"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($existingSongs as $song)
                            <tr class="border-b border-zinc-800 hover:bg-zinc-800/50 group">
                                <td class="py-2 px-3">
                                    @if($song['cover_art_path'])
                                        <img
                                            src="{{ route('audio.cover', ['filename' => $song['cover_art_path']]) }}"
                                            class="w-8 h-8 rounded object-cover"
                                            alt=""
                                        >
                                    @else
                                        <div class="w-8 h-8 rounded bg-zinc-700 flex items-center justify-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-zinc-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2z"/></svg>
                                        </div>
                                    @endif
                                </td>
                                <td class="py-2 px-3 text-white font-medium truncate max-w-xs">{{ $song['title'] }}</td>
                                <td class="py-2 px-3 text-zinc-400 hidden md:table-cell">{{ $song['artist'] ?? '—' }}</td>
                                <td class="py-2 px-3 text-right">
                                    <button
                                        wire:click="deleteSong({{ $song['id'] }})"
                                        wire:confirm="Remove '{{ $song['title'] }}' from library?"
                                        class="opacity-0 group-hover:opacity-100 transition-opacity text-zinc-500 hover:text-red-400"
                                        title="Remove from library"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

</div>

<style>
    .um-card {
        background: #1e1e1e;
        border: 1px solid #333;
        border-radius: 12px;
        padding: 1.5rem;
    }
    .um-label {
        display: block;
        font-size: .78rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: .04em;
        color: #a1a1aa;
        margin-bottom: 6px;
    }
    .um-input {
        width: 100%;
        background: #2a2a2a;
        border: 1px solid #444;
        border-radius: 8px;
        color: #fff;
        padding: 9px 12px;
        font-size: .9rem;
        outline: none;
        transition: border-color .15s;
    }
    .um-input:focus { border-color: #1db954; }
    .um-input::placeholder { color: #666; }
    .um-file-input {
        width: 100%;
        background: #2a2a2a;
        border: 1px dashed #444;
        border-radius: 8px;
        color: #a1a1aa;
        padding: 9px 12px;
        font-size: .85rem;
        outline: none;
        cursor: pointer;
        transition: border-color .15s;
    }
    .um-file-input:hover { border-color: #1db954; }
    .um-error {
        font-size: .78rem;
        color: #f87171;
        margin-top: 4px;
    }
    .um-btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 18px;
        border-radius: 20px;
        font-size: .85rem;
        font-weight: 600;
        border: none;
        cursor: pointer;
        transition: background .15s, opacity .15s;
        text-decoration: none;
    }
    .um-btn:disabled { opacity: .5; cursor: not-allowed; }
    .um-btn-primary { background: #1db954; color: #000; }
    .um-btn-primary:hover { background: #1ed760; }
    .um-btn-secondary { background: #333; color: #fff; }
    .um-btn-secondary:hover { background: #444; }
    .um-btn-ghost { background: transparent; color: #a1a1aa; border: 1px solid #444; }
    .um-btn-ghost:hover { color: #fff; border-color: #666; }
    .um-alert {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 12px 16px;
        border-radius: 8px;
        font-size: .88rem;
        font-weight: 500;
    }
    .um-alert-success { background: rgba(29,185,84,.15); color: #1db954; border: 1px solid rgba(29,185,84,.3); }
    .um-alert-error   { background: rgba(248,113,113,.12); color: #f87171; border: 1px solid rgba(248,113,113,.25); }
</style>
