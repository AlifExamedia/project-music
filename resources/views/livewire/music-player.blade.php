<div
    x-data="musicPlayer({{ Js::from($songs) }}, {{ Js::from($favourites) }}, {{ Js::from($playlists) }})"
    x-init="init()"
    style="height:100vh; display:flex; flex-direction:column;"
>
    {{-- ── TOP SECTION: sidebar + main content ── --}}
    <div style="flex:1; display:flex; overflow:hidden; gap:8px; padding:8px 8px 0;">

        {{-- ═══ LEFT SIDEBAR ═══ --}}
        <div style="width:var(--sp-sidebar-w); flex-shrink:0; display:flex; flex-direction:column; gap:8px;">

            {{-- Navigation --}}
            <div style="background:var(--sp-card); border-radius:8px; padding:16px 12px;">
                <div class="d-flex align-items-center gap-2 px-2 mb-4">
                    <i class="bi bi-music-note-beamed" style="font-size:1.6rem; color:var(--sp-green);"></i>
                    <span style="font-size:1.1rem; font-weight:700; color:var(--sp-text);">Laravel</span>
                </div>
                <nav class="d-flex flex-column gap-1">
                    <a href="#" class="sp-nav-link" :class="currentView === 'all' ? 'active' : ''" @click.prevent="currentView = 'all'; selectedPlaylistId = null">
                        <i class="bi bi-house-fill"></i>
                        <span>Home</span>
                    </a>
                    <a href="#" class="sp-nav-link" :class="currentView === 'favourites' ? 'active' : ''" @click.prevent="currentView = 'favourites'; selectedPlaylistId = null">
                        <i class="bi bi-heart-fill"></i>
                        <span>Liked Songs</span>
                    </a>
                </nav>
            </div>

            {{-- Library --}}
            <div style="background:var(--sp-card); border-radius:8px; flex:1; overflow:hidden; display:flex; flex-direction:column;">
                <div class="d-flex align-items-center justify-content-between px-3 py-3">
                    <div class="d-flex align-items-center gap-2" style="color:var(--sp-muted); font-weight:600; font-size:0.85rem; text-transform:uppercase; letter-spacing:.04em;">
                        <i class="bi bi-collection-fill"></i>
                        <span>Your Library</span>
                    </div>
                    <button
                        class="sp-icon-btn"
                        title="Create Playlist"
                        @click="openCreatePlaylist()"
                        style="font-size:1.1rem;"
                    >
                        <i class="bi bi-plus-lg"></i>
                    </button>
                </div>

                <div style="overflow-y:auto; flex:1;">
                    {{-- Liked Songs item --}}
                    <div
                        class="sp-playlist-item"
                        :class="currentView === 'favourites' ? 'sp-playlist-active' : ''"
                        @click="currentView = 'favourites'; selectedPlaylistId = null"
                    >
                        <div class="sp-playlist-art" style="background:linear-gradient(135deg,#450af5,#c4efd9);">
                            <i class="bi bi-heart-fill" style="font-size:0.85rem; color:#fff;"></i>
                        </div>
                        <div style="overflow:hidden; flex:1;">
                            <div class="text-truncate" style="font-size:0.82rem; font-weight:500;" :class="currentView === 'favourites' ? 'sp-active-text' : ''">Liked Songs</div>
                            <div class="text-truncate" style="font-size:0.75rem; color:var(--sp-muted);" x-text="favourites.length + ' songs'"></div>
                        </div>
                    </div>

                    {{-- Playlists --}}
                    <template x-for="playlist in playlists" :key="playlist.id">
                        <div
                            class="sp-playlist-item"
                            :class="currentView === 'playlist' && selectedPlaylistId === playlist.id ? 'sp-playlist-active' : ''"
                            @click="currentView = 'playlist'; selectedPlaylistId = playlist.id"
                        >
                            <div class="sp-playlist-art" style="background:#333;">
                                <i class="bi bi-music-note-list" style="font-size:0.85rem; color:var(--sp-muted);"></i>
                            </div>
                            <div style="overflow:hidden; flex:1;">
                                <div class="text-truncate" style="font-size:0.82rem; font-weight:500;" :class="currentView === 'playlist' && selectedPlaylistId === playlist.id ? 'sp-active-text' : ''" x-text="playlist.title"></div>
                                <div class="text-truncate" style="font-size:0.75rem; color:var(--sp-muted);" x-text="playlist.songs.length + ' songs'"></div>
                            </div>
                        </div>
                    </template>

                    {{-- Empty playlists state --}}
                    <div x-show="playlists.length === 0" class="px-3 py-2" style="font-size:0.78rem; color:var(--sp-muted);">
                        No playlists yet. Hit <strong>+</strong> to create one.
                    </div>
                </div>
            </div>

        </div>

        {{-- ═══ MAIN CONTENT ═══ --}}
        <div style="flex:1; background:var(--sp-card); border-radius:8px; overflow-y:auto; display:flex; flex-direction:column;">

            {{-- Header: All Songs --}}
            <template x-if="currentView === 'all'">
                <div style="background:linear-gradient(180deg,#1a6535 0%,var(--sp-card) 100%); padding:32px 24px 20px; flex-shrink:0;">
                    <p style="font-size:0.75rem; font-weight:600; text-transform:uppercase; letter-spacing:.06em; color:rgba(255,255,255,0.7);">Good Evening</p>
                    <h1 style="font-size:2rem; font-weight:700; margin:0; line-height:1.2;">Your Music</h1>
                </div>
            </template>

            {{-- Header: Liked Songs --}}
            <template x-if="currentView === 'favourites'">
                <div style="background:linear-gradient(180deg,#450af5 0%,var(--sp-card) 100%); padding:32px 24px 20px; flex-shrink:0;">
                    <p style="font-size:0.75rem; font-weight:600; text-transform:uppercase; letter-spacing:.06em; color:rgba(255,255,255,0.7);">Playlist</p>
                    <h1 style="font-size:2rem; font-weight:700; margin:0; line-height:1.2; display:flex; align-items:center; gap:12px;">
                        <i class="bi bi-heart-fill" style="color:#fff; font-size:1.6rem;"></i>
                        Liked Songs
                    </h1>
                    <p style="margin:8px 0 0; font-size:0.85rem; color:rgba(255,255,255,0.7);" x-text="favourites.length + ' songs'"></p>
                </div>
            </template>

            {{-- Header: Playlist view --}}
            <template x-if="currentView === 'playlist' && selectedPlaylist">
                <div style="background:linear-gradient(180deg,#3d3d3d 0%,var(--sp-card) 100%); padding:32px 24px 20px; flex-shrink:0;">
                    <p style="font-size:0.75rem; font-weight:600; text-transform:uppercase; letter-spacing:.06em; color:rgba(255,255,255,0.7);">Playlist</p>
                    <h1 style="font-size:2rem; font-weight:700; margin:0; line-height:1.2;" x-text="selectedPlaylist.title"></h1>
                    <p style="margin:6px 0 0; font-size:0.85rem; color:rgba(255,255,255,0.6);" x-text="selectedPlaylist.description || ''"></p>
                    <div class="d-flex align-items-center gap-2 mt-3">
                        <span style="font-size:0.8rem; color:rgba(255,255,255,0.5);" x-text="selectedPlaylist.songs.length + ' songs'"></span>
                        <button class="sp-pill-btn" @click="openEditPlaylist(selectedPlaylist)">
                            <i class="bi bi-pencil"></i> Edit
                        </button>
                        <button class="sp-pill-btn sp-pill-btn-danger" @click="deletePlaylist(selectedPlaylist.id)">
                            <i class="bi bi-trash"></i> Delete
                        </button>
                    </div>
                </div>
            </template>

            {{-- Song grid --}}
            <div class="px-4 pb-2 pt-1" style="flex-shrink:0;">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h2 style="font-size:1.25rem; font-weight:700; margin:0;">Songs</h2>
                    <button class="btn btn-sm" style="color:var(--sp-muted); font-size:0.8rem; font-weight:600; text-transform:uppercase; letter-spacing:.05em; border:none; background:none; padding:0;"
                        @click="togglePlay()" x-text="playing ? 'Pause all' : 'Play all'"></button>
                </div>

                <div class="sp-song-grid">
                    <template x-for="song in filteredSongs" :key="song.filename">
                        <div
                            class="sp-song-card"
                            :class="currentFilename === song.filename ? 'sp-song-card-active' : ''"
                            @click="play(song.filename)"
                        >
                            <div class="sp-song-card-art">
                                <template x-if="song.artwork_url">
                                    <img :src="song.artwork_url" style="width:100%;height:100%;object-fit:cover;border-radius:4px;" alt="">
                                </template>
                                <template x-if="!song.artwork_url">
                                    <div class="sp-song-card-art-placeholder">
                                        <i class="bi bi-music-note-beamed" style="font-size:1.8rem; color:var(--sp-muted);"></i>
                                    </div>
                                </template>

                                {{-- Play button --}}
                                <button class="sp-song-card-play-btn" @click.stop="play(song.filename)">
                                    <i class="bi" :class="currentFilename === song.filename && playing ? 'bi-pause-fill' : 'bi-play-fill'"></i>
                                </button>

                                {{-- Favourite button --}}
                                <button
                                    class="sp-song-fav-btn"
                                    :class="isFavourite(song.filename) ? 'sp-song-fav-btn-active' : ''"
                                    @click.stop="toggleFavourite(song.filename)"
                                    :title="isFavourite(song.filename) ? 'Remove from Liked Songs' : 'Add to Liked Songs'"
                                >
                                    <i class="bi" :class="isFavourite(song.filename) ? 'bi-heart-fill' : 'bi-heart'"></i>
                                </button>

                                {{-- Add to playlist button --}}
                                <button
                                    class="sp-song-add-btn"
                                    @click.stop="openAddToPlaylist(song.filename)"
                                    title="Add to Playlist"
                                    x-show="playlists.length > 0"
                                >
                                    <i class="bi bi-plus-lg"></i>
                                </button>

                                {{-- Remove from playlist button (only in playlist view) --}}
                                <template x-if="currentView === 'playlist'">
                                    <button
                                        class="sp-song-remove-btn"
                                        @click.stop="toggleSongInPlaylist(selectedPlaylistId, song.filename)"
                                        title="Remove from Playlist"
                                    >
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                </template>
                            </div>
                            <div style="padding:12px 4px 4px;">
                                <div class="text-truncate" :class="currentFilename === song.filename ? 'sp-active-text' : ''" x-text="song.title" style="font-size:0.88rem; font-weight:600;"></div>
                                <div class="text-truncate" x-text="song.artist ?? 'Unknown Artist'" style="font-size:0.8rem; color:var(--sp-muted); margin-top:2px;"></div>
                            </div>
                        </div>
                    </template>
                </div>

                {{-- Empty state --}}
                <div x-show="filteredSongs.length === 0" class="text-center py-5">
                    <template x-if="currentView === 'favourites'">
                        <div>
                            <i class="bi bi-heart" style="font-size:3rem; color:var(--sp-muted);"></i>
                            <p style="color:var(--sp-muted); margin-top:12px;">No liked songs yet.</p>
                            <p style="font-size:0.8rem; color:var(--sp-muted);">Click the heart on any song to add it here.</p>
                        </div>
                    </template>
                    <template x-if="currentView === 'playlist'">
                        <div>
                            <i class="bi bi-music-note-list" style="font-size:3rem; color:var(--sp-muted);"></i>
                            <p style="color:var(--sp-muted); margin-top:12px;">This playlist is empty.</p>
                            <p style="font-size:0.8rem; color:var(--sp-muted);">Go to Home and use the <strong>+</strong> button on a song to add it.</p>
                        </div>
                    </template>
                    <template x-if="currentView === 'all'">
                        <div>
                            <i class="bi bi-folder-x" style="font-size:3rem; color:var(--sp-muted);"></i>
                            <p style="color:var(--sp-muted); margin-top:12px;">No songs found in your library.</p>
                            <p style="font-size:0.8rem; color:var(--sp-muted);">Add MP3 files to the <code>storage/app/music</code> folder.</p>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>

    {{-- ── BOTTOM PLAYER BAR ── --}}
    <div style="height:var(--sp-player-h); background:var(--sp-card); border-top:1px solid #282828; display:flex; align-items:center; padding:0 16px; gap:16px; flex-shrink:0;">

        {{-- Left: Now playing info --}}
        <div style="flex:1; min-width:0; display:flex; align-items:center; gap:12px;">
            <div class="sp-bar-art">
                <template x-if="currentSong && currentSong.artwork_url">
                    <img :src="currentSong.artwork_url" style="width:100%;height:100%;object-fit:cover;border-radius:4px;" alt="">
                </template>
                <template x-if="!currentSong || !currentSong.artwork_url">
                    <div style="width:100%;height:100%;background:var(--sp-hover);border-radius:4px;display:flex;align-items:center;justify-content:center;">
                        <i class="bi bi-music-note" style="color:var(--sp-muted);"></i>
                    </div>
                </template>
            </div>
            <div style="overflow:hidden; min-width:0;">
                <div class="text-truncate" style="font-size:0.85rem; font-weight:600; color:var(--sp-text);" x-text="currentSong ? currentSong.title : 'Not playing'"></div>
                <div class="text-truncate" style="font-size:0.75rem; color:var(--sp-muted);" x-text="currentSong ? (currentSong.artist ?? 'Unknown Artist') : ''"></div>
            </div>
            <button
                class="sp-icon-btn"
                :class="currentSong && isFavourite(currentSong.filename) ? 'sp-icon-btn-active' : ''"
                :style="!currentSong ? 'opacity:0.3;pointer-events:none;' : 'opacity:1;'"
                @click="currentSong && toggleFavourite(currentSong.filename)"
                :title="currentSong && isFavourite(currentSong.filename) ? 'Remove from Liked Songs' : 'Save to Liked Songs'"
            >
                <i class="bi" :class="currentSong && isFavourite(currentSong.filename) ? 'bi-heart-fill' : 'bi-heart'"></i>
            </button>
        </div>

        {{-- Center: Controls --}}
        <div style="flex:2; max-width:720px; display:flex; flex-direction:column; align-items:center; gap:6px;">
            <div class="d-flex align-items-center gap-3">
                <button class="sp-icon-btn" :class="shuffle ? 'sp-icon-btn-active' : ''" @click="toggleShuffle()" title="Shuffle">
                    <i class="bi bi-shuffle"></i>
                </button>
                <button class="sp-icon-btn sp-icon-btn-lg" @click="prev()" title="Previous">
                    <i class="bi bi-skip-start-fill"></i>
                </button>
                <button class="sp-play-btn" @click="togglePlay()" title="Play / Pause">
                    <i class="bi" :class="playing ? 'bi-pause-fill' : 'bi-play-fill'" style="font-size:1.2rem;"></i>
                </button>
                <button class="sp-icon-btn sp-icon-btn-lg" @click="next()" title="Next">
                    <i class="bi bi-skip-end-fill"></i>
                </button>
                <button
                    class="sp-icon-btn"
                    :class="loop !== 'none' ? 'sp-icon-btn-active' : ''"
                    @click="toggleLoop()"
                    :title="loop === 'one' ? 'Repeat One' : (loop === 'all' ? 'Repeat All' : 'Repeat Off')"
                >
                    <i class="bi" :class="loop === 'one' ? 'bi-repeat-1' : 'bi-repeat'"></i>
                </button>
            </div>

            <div class="d-flex align-items-center gap-2 w-100">
                <span style="font-size:0.7rem; color:var(--sp-muted); width:32px; text-align:right;" x-text="formatTime(currentTime)">0:00</span>
                <input
                    type="range"
                    class="flex-grow-1 progress-bar-input"
                    min="0" max="100" step="0.1"
                    :value="progress"
                    :style="'--progress:' + progress + '%'"
                    @input="seek($event.target.value)"
                >
                <span style="font-size:0.7rem; color:var(--sp-muted); width:32px;" x-text="formatTime(duration)">0:00</span>
            </div>
        </div>

        {{-- Right: Volume --}}
        <div style="flex:1; display:flex; align-items:center; justify-content:flex-end; gap:8px;">
            <button class="sp-icon-btn" @click="toggleMute()" title="Mute">
                <i class="bi" :class="muted ? 'bi-volume-mute-fill' : (volume > 0.5 ? 'bi-volume-up-fill' : 'bi-volume-down-fill')"></i>
            </button>
            <input
                type="range"
                class="volume-input"
                style="width:90px;"
                min="0" max="1" step="0.01"
                :value="muted ? 0 : volume"
                :style="'--vol:' + (muted ? 0 : volume * 100) + '%'"
                @input="setVolume($event.target.value)"
            >
        </div>
    </div>

    {{-- ── CREATE / EDIT PLAYLIST MODAL ── --}}
    <div
        x-show="showPlaylistModal"
        x-transition.opacity
        class="sp-modal-backdrop"
        style="position:fixed;inset:0;z-index:1000;background:rgba(0,0,0,0.7);"
        @click.self="showPlaylistModal = false"
        @keydown.escape.window="showPlaylistModal = false"
    >
        <div style="background:#282828;border-radius:12px;padding:28px 24px;width:420px;max-width:90vw;box-shadow:0 24px 64px rgba(0,0,0,0.5);">
            <h3 style="font-size:1.3rem;font-weight:700;margin:0 0 20px;" x-text="playlistForm.id ? 'Edit Playlist' : 'Create Playlist'"></h3>

            <div class="mb-3">
                <label style="font-size:0.78rem;font-weight:600;color:var(--sp-muted);text-transform:uppercase;letter-spacing:.04em;display:block;margin-bottom:6px;">Title</label>
                <input
                    x-model="playlistForm.title"
                    type="text"
                    placeholder="My Playlist"
                    class="sp-input"
                    @keydown.enter="savePlaylist()"
                >
            </div>
            <div class="mb-4">
                <label style="font-size:0.78rem;font-weight:600;color:var(--sp-muted);text-transform:uppercase;letter-spacing:.04em;display:block;margin-bottom:6px;">Description <span style="font-weight:400;text-transform:none;">(optional)</span></label>
                <textarea
                    x-model="playlistForm.description"
                    placeholder="Add an optional description"
                    class="sp-input"
                    rows="3"
                    style="resize:vertical;"
                ></textarea>
            </div>

            <div class="d-flex justify-content-end gap-2">
                <button class="sp-modal-btn sp-modal-btn-cancel" @click="showPlaylistModal = false">Cancel</button>
                <button class="sp-modal-btn sp-modal-btn-save" @click="savePlaylist()" :disabled="!playlistForm.title.trim()">Save</button>
            </div>
        </div>
    </div>

    {{-- ── ADD TO PLAYLIST MODAL ── --}}
    <div
        x-show="showAddToPlaylistModal"
        x-transition.opacity
        class="sp-modal-backdrop"
        style="position:fixed;inset:0;z-index:1000;background:rgba(0,0,0,0.7);"
        @click.self="showAddToPlaylistModal = false"
        @keydown.escape.window="showAddToPlaylistModal = false"
    >
        <div style="background:#282828;border-radius:12px;padding:28px 24px;width:360px;max-width:90vw;box-shadow:0 24px 64px rgba(0,0,0,0.5);">
            <h3 style="font-size:1.1rem;font-weight:700;margin:0 0 16px;">Add to Playlist</h3>

            <div style="max-height:300px;overflow-y:auto;display:flex;flex-direction:column;gap:4px;">
                <template x-for="playlist in playlists" :key="playlist.id">
                    <div
                        class="sp-playlist-select-item"
                        :class="playlist.songs.includes(addToPlaylistFilename) ? 'sp-playlist-select-item-active' : ''"
                        @click="toggleSongInPlaylist(playlist.id, addToPlaylistFilename)"
                    >
                        <div style="width:36px;height:36px;background:#333;border-radius:4px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i class="bi bi-music-note-list" style="color:var(--sp-muted);font-size:0.85rem;"></i>
                        </div>
                        <div style="flex:1;overflow:hidden;">
                            <div class="text-truncate" x-text="playlist.title" style="font-size:0.88rem;font-weight:500;"></div>
                            <div x-text="playlist.songs.length + ' songs'" style="font-size:0.75rem;color:var(--sp-muted);"></div>
                        </div>
                        <i class="bi" :class="playlist.songs.includes(addToPlaylistFilename) ? 'bi-check-circle-fill' : 'bi-circle'" style="font-size:1rem;flex-shrink:0;" :style="playlist.songs.includes(addToPlaylistFilename) ? 'color:var(--sp-green)' : 'color:var(--sp-muted)'"></i>
                    </div>
                </template>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-4">
                <button class="sp-pill-btn" @click="showAddToPlaylistModal = false; openCreatePlaylist()">
                    <i class="bi bi-plus-lg"></i> New Playlist
                </button>
                <button class="sp-modal-btn sp-modal-btn-save" @click="showAddToPlaylistModal = false">Done</button>
            </div>
        </div>
    </div>
</div>

<style>
    /* Modal backdrop — display managed here so Alpine x-show doesn't clobber it */
    .sp-modal-backdrop {
        display: flex;
        align-items: center;
        justify-content: center;
    }

    /* Nav links */
    .sp-nav-link {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 8px 12px;
        border-radius: 6px;
        color: var(--sp-muted);
        text-decoration: none;
        font-weight: 600;
        font-size: 0.9rem;
        transition: color 0.15s;
    }
    .sp-nav-link:hover { color: var(--sp-text); }
    .sp-nav-link.active { color: var(--sp-text); }
    .sp-nav-link i { font-size: 1.3rem; }

    /* Sidebar playlist items */
    .sp-playlist-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 6px 12px;
        cursor: pointer;
        transition: background 0.15s;
        border-radius: 4px;
        margin: 0 6px;
    }
    .sp-playlist-item:hover { background: var(--sp-hover); }
    .sp-playlist-active { background: var(--sp-hover) !important; }
    .sp-active-text { color: var(--sp-green) !important; }

    .sp-playlist-art {
        position: relative;
        width: 38px; height: 38px;
        border-radius: 4px;
        background: var(--sp-hover);
        flex-shrink: 0;
        display: flex; align-items: center; justify-content: center;
        overflow: hidden;
    }

    /* Song grid */
    .sp-song-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
        gap: 16px;
        padding-bottom: 24px;
    }

    .sp-song-card {
        background: var(--sp-hover);
        border-radius: 8px;
        padding: 12px;
        cursor: pointer;
        transition: background 0.2s;
        position: relative;
    }
    .sp-song-card:hover { background: #333; }
    .sp-song-card-active { background: #333 !important; }

    .sp-song-card-art {
        position: relative;
        width: 100%; padding-top: 100%;
        border-radius: 4px;
        overflow: hidden;
        background: #333;
        box-shadow: 0 8px 24px rgba(0,0,0,0.5);
    }
    .sp-song-card-art > img,
    .sp-song-card-art-placeholder {
        position: absolute; inset: 0;
        width: 100%; height: 100%;
        display: flex; align-items: center; justify-content: center;
        background: var(--sp-hover);
    }

    .sp-song-card-play-btn {
        position: absolute;
        bottom: 8px; right: 8px;
        width: 40px; height: 40px;
        border-radius: 50%;
        background: var(--sp-green);
        border: none;
        color: #000;
        font-size: 1rem;
        display: flex; align-items: center; justify-content: center;
        opacity: 0;
        transform: translateY(8px);
        transition: opacity 0.2s, transform 0.2s;
        box-shadow: 0 8px 16px rgba(0,0,0,0.4);
        cursor: pointer;
        z-index: 2;
    }
    .sp-song-card:hover .sp-song-card-play-btn,
    .sp-song-card-active .sp-song-card-play-btn {
        opacity: 1;
        transform: translateY(0);
    }
    .sp-song-card-play-btn:hover { background: var(--sp-green-hover); transform: scale(1.04) translateY(0) !important; }

    /* Favourite button on card */
    .sp-song-fav-btn {
        position: absolute;
        top: 6px; right: 6px;
        width: 28px; height: 28px;
        border-radius: 50%;
        background: rgba(0,0,0,0.5);
        border: none;
        color: var(--sp-muted);
        font-size: 0.8rem;
        display: flex; align-items: center; justify-content: center;
        opacity: 0;
        transition: opacity 0.2s, color 0.15s;
        cursor: pointer;
        z-index: 3;
    }
    .sp-song-card:hover .sp-song-fav-btn { opacity: 1; }
    .sp-song-fav-btn-active { opacity: 1 !important; color: var(--sp-green) !important; }
    .sp-song-fav-btn:hover { color: #fff; }

    /* Add to playlist button on card */
    .sp-song-add-btn {
        position: absolute;
        top: 6px; left: 6px;
        width: 28px; height: 28px;
        border-radius: 50%;
        background: rgba(0,0,0,0.5);
        border: none;
        color: var(--sp-muted);
        font-size: 0.85rem;
        display: flex; align-items: center; justify-content: center;
        opacity: 0;
        transition: opacity 0.2s, color 0.15s;
        cursor: pointer;
        z-index: 3;
    }
    .sp-song-card:hover .sp-song-add-btn { opacity: 1; }
    .sp-song-add-btn:hover { color: #fff; }

    /* Remove from playlist button on card */
    .sp-song-remove-btn {
        position: absolute;
        top: 38px; left: 6px;
        width: 28px; height: 28px;
        border-radius: 50%;
        background: rgba(0,0,0,0.5);
        border: none;
        color: #e55;
        font-size: 0.75rem;
        display: flex; align-items: center; justify-content: center;
        opacity: 0;
        transition: opacity 0.2s;
        cursor: pointer;
        z-index: 3;
    }
    .sp-song-card:hover .sp-song-remove-btn { opacity: 1; }

    /* Bottom bar art */
    .sp-bar-art {
        width: 54px; height: 54px;
        flex-shrink: 0;
    }

    /* Icon buttons */
    .sp-icon-btn {
        background: none;
        border: none;
        color: var(--sp-muted);
        font-size: 1rem;
        padding: 4px;
        cursor: pointer;
        transition: color 0.15s, transform 0.1s;
        display: flex; align-items: center;
    }
    .sp-icon-btn:hover { color: var(--sp-text); transform: scale(1.08); }
    .sp-icon-btn-active { color: var(--sp-green) !important; }
    .sp-icon-btn-lg { font-size: 1.3rem; }

    /* Play button */
    .sp-play-btn {
        width: 36px; height: 36px;
        border-radius: 50%;
        background: var(--sp-text);
        border: none;
        color: #000;
        display: flex; align-items: center; justify-content: center;
        cursor: pointer;
        transition: transform 0.1s, background 0.15s;
    }
    .sp-play-btn:hover { transform: scale(1.06); background: #e0e0e0; }

    /* Pill buttons (edit/delete) */
    .sp-pill-btn {
        background: rgba(255,255,255,0.1);
        border: none;
        color: var(--sp-text);
        border-radius: 20px;
        padding: 6px 14px;
        font-size: 0.8rem;
        font-weight: 600;
        cursor: pointer;
        transition: background 0.15s;
        display: inline-flex; align-items: center; gap: 6px;
    }
    .sp-pill-btn:hover { background: rgba(255,255,255,0.2); }
    .sp-pill-btn-danger { color: #e55; }
    .sp-pill-btn-danger:hover { background: rgba(220,50,50,0.15); }

    /* Modal inputs */
    .sp-input {
        width: 100%;
        background: #3e3e3e;
        border: 1px solid #555;
        border-radius: 6px;
        color: var(--sp-text);
        padding: 10px 12px;
        font-size: 0.9rem;
        outline: none;
        transition: border-color 0.15s;
    }
    .sp-input:focus { border-color: var(--sp-green); }
    .sp-input::placeholder { color: var(--sp-muted); }

    /* Modal buttons */
    .sp-modal-btn {
        border: none;
        border-radius: 20px;
        padding: 8px 20px;
        font-size: 0.88rem;
        font-weight: 700;
        cursor: pointer;
        transition: background 0.15s, transform 0.1s;
    }
    .sp-modal-btn:hover { transform: scale(1.02); }
    .sp-modal-btn-cancel { background: transparent; color: var(--sp-muted); }
    .sp-modal-btn-cancel:hover { color: var(--sp-text); }
    .sp-modal-btn-save { background: var(--sp-text); color: #000; }
    .sp-modal-btn-save:hover { background: #e0e0e0; }
    .sp-modal-btn-save:disabled { opacity: 0.4; cursor: not-allowed; transform: none; }

    /* Add to playlist select items */
    .sp-playlist-select-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px 12px;
        border-radius: 6px;
        cursor: pointer;
        transition: background 0.15s;
    }
    .sp-playlist-select-item:hover { background: var(--sp-hover); }
    .sp-playlist-select-item-active { background: rgba(29,185,84,0.1); }
</style>
