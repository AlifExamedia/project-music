document.addEventListener('alpine:init', () => {
    Alpine.data('musicPlayer', (songs, favourites, playlists, session) => ({
        // Playback state
        songs,
        currentSongId: null,
        playing: false,
        shuffle: false,
        loop: 'none', // 'none' | 'all' | 'one'
        muted: false,
        volume: 0.8,
        progress: 0,
        currentTime: 0,
        duration: 0,
        audio: null,
        shuffleQueue: [],

        // DB-backed state (synced via Livewire)
        favourites,
        playlists,

        // UI state
        currentView: 'all', // 'all' | 'favourites' | 'playlist'
        selectedPlaylistId: null,
        showPlaylistModal: false,
        playlistForm: { id: null, title: '', description: '' },
        showAddToPlaylistModal: false,
        addToPlaylistSongId: null,
        leftSidebarOpen: true,
        rightSidebarOpen: false,
        viewMode: 'grid', // 'grid' | 'list'

        // Queue
        queue: [], // array of song IDs (up next)

        // Selection & context menu
        selectedSongId: null,
        contextMenu: { show: false, songId: null, x: 0, y: 0 },
        _longPressTimer: null,
        _longPressTriggered: false,

        // DB sync internals
        _lastDbSave: 0,
        _dbSaveTimer: null,

        get queueSongs() {
            return this.queue.map(id => this.songs.find(s => s.id === id)).filter(Boolean);
        },

        get currentSong() {
            return this.songs.find(s => s.id === this.currentSongId) ?? null;
        },

        get filteredSongs() {
            if (this.currentView === 'favourites') {
                return this.songs.filter(s => this.favourites.includes(s.id));
            }
            if (this.currentView === 'playlist' && this.selectedPlaylistId !== null) {
                const pl = this.playlists.find(p => p.id === this.selectedPlaylistId);
                if (!pl) return this.songs;
                return pl.songs
                    .map(id => this.songs.find(s => s.id === id))
                    .filter(Boolean);
            }
            return this.songs;
        },

        get selectedPlaylist() {
            return this.playlists.find(p => p.id === this.selectedPlaylistId) ?? null;
        },

        isFavourite(songId) {
            return this.favourites.includes(songId);
        },

        isInSelectedPlaylist(songId) {
            return this.selectedPlaylist?.songs.includes(songId) ?? false;
        },

        songPlaylists(songId) {
            return this.playlists.filter(p => p.songs.includes(songId));
        },

        init() {
            this.audio = new Audio();

            // DB session takes priority over localStorage
            const local = this._loadState();
            const src = (session && Object.keys(session).length > 0) ? session : local;

            if (src.volume !== undefined)             this.volume            = src.volume;
            if (src.muted !== undefined)              this.muted             = src.muted;
            if (src.shuffle !== undefined)            this.shuffle           = src.shuffle;
            if (src.loop !== undefined)               this.loop              = src.loop;
            if (src.left_sidebar_open !== undefined)  this.leftSidebarOpen   = src.left_sidebar_open;
            if (src.leftSidebarOpen !== undefined)    this.leftSidebarOpen   = src.leftSidebarOpen;
            if (src.right_sidebar_open !== undefined) this.rightSidebarOpen  = src.right_sidebar_open;
            if (src.rightSidebarOpen !== undefined)   this.rightSidebarOpen  = src.rightSidebarOpen;
            if (src.view_mode !== undefined)          this.viewMode          = src.view_mode;
            if (src.viewMode !== undefined)           this.viewMode          = src.viewMode;
            if (Array.isArray(src.queue) && src.queue.length) this.queue    = src.queue;

            this.audio.volume = this.volume;
            this.audio.muted  = this.muted;

            this.audio.addEventListener('timeupdate', () => {
                this.currentTime = this.audio.currentTime;
                this.progress = this.duration ? (this.currentTime / this.duration) * 100 : 0;
                this._saveState();

                // Sync to DB every 15 seconds during playback
                const now = Date.now();
                if (now - this._lastDbSave > 15000) {
                    this._lastDbSave = now;
                    this._syncToDb();
                }
            });

            this.audio.addEventListener('loadedmetadata', () => {
                this.duration = this.audio.duration;
                const dbTime    = src.current_time ?? src.currentTime ?? 0;
                const localTime = local.currentTime ?? 0;
                // localStorage is saved every ~250ms; DB only every 15s — prefer the larger value
                const restoreTime = Math.max(dbTime, localTime);
                if (restoreTime > 0) {
                    this.audio.currentTime = restoreTime;
                }
                // zero out both so subsequent song loads start at 0:00
                src.current_time = 0;
                src.currentTime  = 0;
                local.currentTime = 0;
            });

            this.audio.addEventListener('ended', () => {
                if (this.loop === 'one') {
                    this.audio.currentTime = 0;
                    this.audio.play();
                } else {
                    this.next();
                }
            });

            this.audio.addEventListener('pause', () => {
                this._syncToDbDebounced(500);
            });

            if (this.songs.length) {
                const songId = src.current_song_id ?? src.currentSongId;
                const exists = songId && this.songs.find(s => s.id === songId);
                this.loadSong(exists ? songId : this.songs[0].id, false);
            }
        },

        loadSong(songId, autoplay = true) {
            const song = this.songs.find(s => s.id === songId);
            if (!song) return;
            this.currentSongId = songId;
            this.selectedSongId = songId;
            this.audio.src = song.url;
            this.audio.load();
            if (autoplay) {
                this.audio.play();
                this.playing = true;
            } else {
                this.playing = false;
            }
            this._syncToDbDebounced(800);
        },

        play(songId) {
            if (this.currentSongId === songId && this.playing) {
                this.audio.pause();
                this.playing = false;
            } else if (this.currentSongId === songId) {
                this.audio.play();
                this.playing = true;
            } else {
                this.loadSong(songId);
            }
        },

        togglePlay() {
            if (!this.songs.length) return;
            if (this.playing) {
                this.audio.pause();
                this.playing = false;
            } else {
                if (!this.currentSongId && this.filteredSongs.length) {
                    this.loadSong(this.filteredSongs[0].id);
                } else {
                    this.audio.play();
                    this.playing = true;
                }
            }
        },

        next() {
            if (this.queue.length > 0) {
                this.loadSong(this.queue.shift());
                return;
            }

            const songs = this.filteredSongs;
            if (!songs.length) return;

            let nextId;
            if (this.shuffle) {
                if (!this.shuffleQueue.length) this.buildShuffleQueue(songs);
                nextId = this.shuffleQueue.pop();
            } else {
                const idx = songs.findIndex(s => s.id === this.currentSongId);
                const nextIdx = idx + 1;
                if (nextIdx >= songs.length && this.loop === 'none') {
                    this.audio.pause();
                    this.playing = false;
                    return;
                }
                nextId = songs[nextIdx % songs.length].id;
            }
            this.loadSong(nextId);
        },

        prev() {
            const songs = this.filteredSongs;
            if (!songs.length) return;
            if (this.audio.currentTime > 3) {
                this.audio.currentTime = 0;
                return;
            }
            const idx = songs.findIndex(s => s.id === this.currentSongId);
            const prevIdx = (idx - 1 + songs.length) % songs.length;
            this.loadSong(songs[prevIdx].id);
        },

        toggleShuffle() {
            this.shuffle = !this.shuffle;
            if (this.shuffle) this.buildShuffleQueue(this.filteredSongs);
            this._saveState();
            this._syncToDb();
        },

        toggleLoop() {
            if (this.loop === 'none') this.loop = 'all';
            else if (this.loop === 'all') this.loop = 'one';
            else this.loop = 'none';
            this._saveState();
            this._syncToDb();
        },

        buildShuffleQueue(songs) {
            const ids = songs
                .map(s => s.id)
                .filter(id => id !== this.currentSongId);
            for (let i = ids.length - 1; i > 0; i--) {
                const j = Math.floor(Math.random() * (i + 1));
                [ids[i], ids[j]] = [ids[j], ids[i]];
            }
            this.shuffleQueue = ids;
        },

        seek(value) {
            if (this.duration) {
                this.audio.currentTime = (value / 100) * this.duration;
            }
        },

        setVolume(value) {
            this.volume = parseFloat(value);
            this.audio.volume = this.volume;
            this.muted = this.volume === 0;
            this._saveState();
            this._syncToDbDebounced(1500);
        },

        toggleMute() {
            this.muted = !this.muted;
            this.audio.muted = this.muted;
            this._saveState();
            this._syncToDb();
        },

        formatTime(seconds) {
            if (!seconds || isNaN(seconds)) return '0:00';
            const m = Math.floor(seconds / 60);
            const s = Math.floor(seconds % 60).toString().padStart(2, '0');
            return `${m}:${s}`;
        },

        // ── Favourites ──────────────────────────────────────────────────
        async toggleFavourite(songId) {
            if (this.favourites.includes(songId)) {
                this.favourites = this.favourites.filter(id => id !== songId);
            } else {
                this.favourites.push(songId);
            }
            await this.$wire.toggleFavourite(songId);
        },

        // ── Playlists ────────────────────────────────────────────────────
        openCreatePlaylist() {
            this.playlistForm = { id: null, title: '', description: '' };
            this.showPlaylistModal = true;
        },

        openEditPlaylist(playlist) {
            this.playlistForm = {
                id: playlist.id,
                title: playlist.title,
                description: playlist.description,
            };
            this.showPlaylistModal = true;
        },

        async savePlaylist() {
            if (!this.playlistForm.title.trim()) return;

            if (this.playlistForm.id) {
                await this.$wire.updatePlaylist(
                    this.playlistForm.id,
                    this.playlistForm.title,
                    this.playlistForm.description
                );
                this.playlists = this.playlists.map(p =>
                    p.id === this.playlistForm.id
                        ? { ...p, title: this.playlistForm.title, description: this.playlistForm.description }
                        : p
                );
            } else {
                const created = await this.$wire.createPlaylist(
                    this.playlistForm.title,
                    this.playlistForm.description
                );
                this.playlists.push(created);
            }

            this.showPlaylistModal = false;
        },

        async deletePlaylist(id) {
            if (!confirm('Delete this playlist?')) return;
            await this.$wire.deletePlaylist(id);
            this.playlists = this.playlists.filter(p => p.id !== id);
            if (this.selectedPlaylistId === id) {
                this.selectedPlaylistId = null;
                this.currentView = 'all';
            }
        },

        openAddToPlaylist(songId) {
            this.addToPlaylistSongId = songId;
            this.showAddToPlaylistModal = true;
        },

        async toggleSongInPlaylist(playlistId, songId) {
            const pl = this.playlists.find(p => p.id === playlistId);
            if (!pl) return;

            if (pl.songs.includes(songId)) {
                await this.$wire.removeFromPlaylist(playlistId, songId);
                pl.songs = pl.songs.filter(id => id !== songId);
            } else {
                await this.$wire.addToPlaylist(playlistId, songId);
                pl.songs.push(songId);
            }
        },

        // ── Selection & context menu ─────────────────────────────────────
        selectSong(songId) {
            if (this._longPressTriggered) { this._longPressTriggered = false; return; }
            this.selectedSongId = songId;
        },

        openContextMenu(event, songId) {
            this.selectedSongId = songId;
            const menuW = 230, menuH = 220;
            this.contextMenu = {
                show: true,
                songId,
                x: Math.min(event.clientX, window.innerWidth - menuW - 8),
                y: Math.min(event.clientY, window.innerHeight - menuH - 8),
            };
        },

        closeContextMenu() {
            this.contextMenu.show = false;
        },

        startLongPress(event, songId) {
            if (event.button !== undefined && event.button !== 0) return;
            const clientX = event.touches ? event.touches[0].clientX : event.clientX;
            const clientY = event.touches ? event.touches[0].clientY : event.clientY;
            this._longPressTriggered = false;
            clearTimeout(this._longPressTimer);
            this._longPressTimer = setTimeout(() => {
                this._longPressTriggered = true;
                this.openContextMenu({ clientX, clientY }, songId);
            }, 500);
        },

        cancelLongPress() {
            clearTimeout(this._longPressTimer);
            this._longPressTimer = null;
        },

        // ── Queue ────────────────────────────────────────────────────────
        addToQueue(songId) {
            this.queue.push(songId);
            this._syncToDbDebounced(500);
        },

        removeFromQueue(index) {
            this.queue.splice(index, 1);
            this._syncToDbDebounced(500);
        },

        clearQueue() {
            this.queue = [];
            this._syncToDb();
        },

        playFromQueue(index) {
            const songId = this.queue[index];
            this.queue.splice(0, index + 1);
            this.loadSong(songId);
            // loadSong already triggers _syncToDbDebounced
        },

        // ── State persistence ────────────────────────────────────────────
        _saveState() {
            try {
                localStorage.setItem('musicPlayerState', JSON.stringify({
                    currentSongId: this.currentSongId,
                    currentTime: this.audio.currentTime,
                    volume: this.volume,
                    muted: this.muted,
                    shuffle: this.shuffle,
                    loop: this.loop,
                    leftSidebarOpen: this.leftSidebarOpen,
                    rightSidebarOpen: this.rightSidebarOpen,
                    viewMode: this.viewMode,
                    queue: this.queue,
                }));
            } catch (_) {}
        },

        _loadState() {
            try {
                return JSON.parse(localStorage.getItem('musicPlayerState') || '{}');
            } catch (_) { return {}; }
        },

        _syncToDb() {
            this.$wire.saveSession(
                this.currentSongId,
                this.audio ? this.audio.currentTime : 0,
                this.volume,
                this.muted,
                this.shuffle,
                this.loop,
                this.leftSidebarOpen,
                this.rightSidebarOpen,
                [...this.queue],
                this.viewMode
            );
        },

        _syncToDbDebounced(delay = 1000) {
            clearTimeout(this._dbSaveTimer);
            this._dbSaveTimer = setTimeout(() => this._syncToDb(), delay);
        },
    }));
});
