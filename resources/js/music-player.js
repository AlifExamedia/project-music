document.addEventListener('alpine:init', () => {
    Alpine.data('musicPlayer', (songs, favourites, playlists, session, historyIds) => ({
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
        history: historyIds ?? [],

        // UI state
        currentView: 'all', // 'all' | 'favourites' | 'playlist' | 'albums' | 'artists' | 'album' | 'artist' | 'search' | 'history'
        selectedPlaylistId: null,
        selectedAlbum: null,
        selectedArtist: null,
        showPlaylistModal: false,
        playlistForm: { id: null, title: '', description: '' },
        showAddToPlaylistModal: false,
        addToPlaylistSongId: null,
        pendingPlaylistIds: [],
        showSongPlaylistsModal: false,
        songPlaylistsModalSongId: null,
        leftSidebarOpen: true,
        rightSidebarOpen: false,
        viewMode: 'grid', // 'grid' | 'list'
        artistTab: 'songs', // 'songs' | 'albums'

        // Search state
        searchQuery: '',
        searchBy: 'all', // 'all' | 'name' | 'artist' | 'album'
        filterYear: '',
        filterGenre: '',

        // Queue
        queue: [], // array of song IDs (up next)

        // Selection & context menu
        selectedSongId: null,
        contextMenu: { show: false, songId: null, x: 0, y: 0, fromQueue: false, queueIndex: null },
        _longPressTimer: null,
        _longPressTriggered: false,

        // Navigation history (back / forward)
        navHistory: [],
        navFuture: [],

        // Queue drag-to-reorder
        _queueDragIndex: null,

        // DB sync internals
        _lastDbSave: 0,
        _dbSaveTimer: null,

        get queueSongs() {
            return this.queue.map(id => this.songs.find(s => s.id === id)).filter(Boolean);
        },

        get currentSong() {
            return this.songs.find(s => s.id === this.currentSongId) ?? null;
        },

        get contextMenuSong() {
            return this.songs.find(s => s.id === this.contextMenu.songId) ?? null;
        },

        get songPlaylistsModalSong() {
            return this.songs.find(s => s.id === this.songPlaylistsModalSongId) ?? null;
        },

        get songPlaylistsModalPlaylists() {
            if (!this.songPlaylistsModalSongId) return [];
            return this.playlists.filter(p => p.songs.includes(this.songPlaylistsModalSongId));
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
            if (this.currentView === 'album') {
                return this.songs.filter(s => s.album === this.selectedAlbum);
            }
            if (this.currentView === 'artist') {
                const name = this.selectedArtist;
                return this.songs.filter(s => (s.artist ?? 'Unknown Artist') === name);
            }
            if (this.currentView === 'history') {
                return this.history
                    .map(id => this.songs.find(s => s.id === id))
                    .filter(Boolean);
            }
            if (this.currentView === 'albums' || this.currentView === 'artists') {
                return [];
            }
            if (this.currentView === 'search') {
                let results = this.songs;
                const q = this.searchQuery.trim().toLowerCase();
                if (q) {
                    results = results.filter(s => {
                        if (this.searchBy === 'name')   return s.title?.toLowerCase().includes(q);
                        if (this.searchBy === 'artist') return s.artist?.toLowerCase().includes(q);
                        if (this.searchBy === 'album')  return s.album?.toLowerCase().includes(q);
                        return (
                            s.title?.toLowerCase().includes(q) ||
                            s.artist?.toLowerCase().includes(q) ||
                            s.album?.toLowerCase().includes(q)
                        );
                    });
                }
                if (this.filterYear) {
                    results = results.filter(s => String(s.year) === String(this.filterYear));
                }
                if (this.filterGenre) {
                    results = results.filter(s => s.genre === this.filterGenre);
                }
                return results;
            }
            return this.songs;
        },

        get selectedPlaylist() {
            return this.playlists.find(p => p.id === this.selectedPlaylistId) ?? null;
        },

        get yearList() {
            return [...new Set(this.songs.map(s => s.year).filter(Boolean))]
                .sort((a, b) => b - a);
        },

        get genreList() {
            return [...new Set(this.songs.map(s => s.genre).filter(Boolean))].sort();
        },

        get albumList() {
            const map = {};
            this.songs.forEach(s => {
                if (!s.album) return;
                if (!map[s.album]) {
                    map[s.album] = { name: s.album, artist: s.artist, artwork_url: null, count: 0 };
                }
                map[s.album].count++;
                if (!map[s.album].artwork_url && s.artwork_url) {
                    map[s.album].artwork_url = s.artwork_url;
                }
            });
            return Object.values(map).sort((a, b) => a.name.localeCompare(b.name));
        },

        get canGoBack() { return this.navHistory.length > 0; },
        get canGoForward() { return this.navFuture.length > 0; },

        get artistAlbumList() {
            if (!this.selectedArtist) return [];
            const map = {};
            this.songs.forEach(s => {
                if (!s.album) return;
                const artistName = s.artist ?? 'Unknown Artist';
                if (artistName !== this.selectedArtist) return;
                if (!map[s.album]) {
                    map[s.album] = { name: s.album, artist: s.artist, artwork_url: null, count: 0 };
                }
                map[s.album].count++;
                if (!map[s.album].artwork_url && s.artwork_url) {
                    map[s.album].artwork_url = s.artwork_url;
                }
            });
            return Object.values(map).sort((a, b) => a.name.localeCompare(b.name));
        },

        get artistList() {
            const map = {};
            this.songs.forEach(s => {
                const name = s.artist ?? 'Unknown Artist';
                if (!map[name]) {
                    map[name] = { name, artwork_url: null, count: 0 };
                }
                map[name].count++;
                if (!map[name].artwork_url && s.artwork_url) {
                    map[name].artwork_url = s.artwork_url;
                }
            });
            return Object.values(map).sort((a, b) => a.name.localeCompare(b.name));
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

        _navSnapshot() {
            return {
                view: this.currentView,
                selectedAlbum: this.selectedAlbum,
                selectedArtist: this.selectedArtist,
                selectedPlaylistId: this.selectedPlaylistId,
                scrollTop: this.$refs.mainContent?.scrollTop ?? 0,
            };
        },

        _applyNavSnapshot(snap) {
            this.currentView = snap.view;
            this.selectedAlbum = snap.selectedAlbum;
            this.selectedArtist = snap.selectedArtist;
            this.selectedPlaylistId = snap.selectedPlaylistId;
            this._saveState();
            this.$nextTick(() => {
                if (this.$refs.mainContent) this.$refs.mainContent.scrollTop = snap.scrollTop;
            });
        },

        goTo(view, opts = {}) {
            this.navHistory.push(this._navSnapshot());
            this.navFuture = [];
            this.currentView = view;
            this.selectedAlbum = opts.album ?? null;
            this.selectedArtist = opts.artist ?? null;
            this.selectedPlaylistId = opts.playlistId ?? null;
            this._saveState();
            this.$nextTick(() => {
                if (this.$refs.mainContent) this.$refs.mainContent.scrollTop = 0;
            });
        },

        navBack() {
            if (!this.navHistory.length) return;
            this.navFuture.push(this._navSnapshot());
            this._applyNavSnapshot(this.navHistory.pop());
        },

        navForward() {
            if (!this.navFuture.length) return;
            this.navHistory.push(this._navSnapshot());
            this._applyNavSnapshot(this.navFuture.pop());
        },

        viewAlbum(albumName) {
            this.goTo('album', { album: albumName });
        },

        viewArtist(artistName) {
            this.artistTab = 'songs';
            this.goTo('artist', { artist: artistName });
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

            // Restore view state from localStorage only (not DB session)
            if (local.currentView !== undefined)       this.currentView       = local.currentView;
            if (local.selectedAlbum !== undefined)     this.selectedAlbum     = local.selectedAlbum;
            if (local.selectedArtist !== undefined)    this.selectedArtist    = local.selectedArtist;
            if (local.selectedPlaylistId !== undefined) this.selectedPlaylistId = local.selectedPlaylistId;

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
                const song = songId ? this.songs.find(s => s.id === songId) : null;
                if (song) {
                    this.loadSong(song.id, false);
                }
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
                this.history = [songId, ...this.history.filter(id => id !== songId)].slice(0, 100);
                this.$wire.addToHistory(songId);
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
            if (this.loop === 'all') {
                const songs = this.filteredSongs.length ? this.filteredSongs : this.songs;
                if (songs.length) {
                    const ids = this.shuffle
                        ? (() => {
                            const shuffled = songs.map(s => s.id).filter(id => id !== this.currentSongId);
                            for (let i = shuffled.length - 1; i > 0; i--) {
                                const j = Math.floor(Math.random() * (i + 1));
                                [shuffled[i], shuffled[j]] = [shuffled[j], shuffled[i]];
                            }
                            return shuffled;
                        })()
                        : songs.map(s => s.id);
                    const [first, ...rest] = ids;
                    this.queue = rest;
                    this.loadSong(first);
                    this._syncToDbDebounced(500);
                    return;
                }
            }
            // Queue is empty — stop playback and clear now playing
            this.audio.pause();
            this.playing = false;
            this.currentSongId = null;
            this._saveState();
            this._syncToDb();
        },

        prev() {
            const songs = this.filteredSongs.length ? this.filteredSongs : this.songs;
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
            if (this.shuffle) this.buildShuffleQueue(this.filteredSongs.length ? this.filteredSongs : this.songs);
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
            this.pendingPlaylistIds = this.playlists
                .filter(p => p.songs.includes(songId))
                .map(p => p.id);
            this.showAddToPlaylistModal = true;
        },

        togglePendingPlaylist(playlistId) {
            if (this.pendingPlaylistIds.includes(playlistId)) {
                this.pendingPlaylistIds = this.pendingPlaylistIds.filter(id => id !== playlistId);
            } else {
                this.pendingPlaylistIds.push(playlistId);
            }
        },

        async confirmAddToPlaylist() {
            const songId = this.addToPlaylistSongId;
            const originalIds = this.playlists.filter(p => p.songs.includes(songId)).map(p => p.id);

            for (const pl of this.playlists) {
                const wasIn = originalIds.includes(pl.id);
                const nowIn = this.pendingPlaylistIds.includes(pl.id);
                if (!wasIn && nowIn) {
                    await this.$wire.addToPlaylist(pl.id, songId);
                    pl.songs.push(songId);
                } else if (wasIn && !nowIn) {
                    await this.$wire.removeFromPlaylist(pl.id, songId);
                    pl.songs = pl.songs.filter(id => id !== songId);
                }
            }

            this.showAddToPlaylistModal = false;
        },

        openSongPlaylists(songId) {
            this.songPlaylistsModalSongId = songId;
            this.showSongPlaylistsModal = true;
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
            const menuW = 230, menuH = 260;
            this.contextMenu = {
                show: true,
                songId,
                fromQueue: false,
                queueIndex: null,
                x: Math.min(event.clientX, window.innerWidth - menuW - 8),
                y: Math.min(event.clientY, window.innerHeight - menuH - 8),
            };
        },

        openQueueContextMenu(event, songId, index) {
            this.selectedSongId = songId;
            const menuW = 230, menuH = 430;
            this.contextMenu = {
                show: true,
                songId,
                fromQueue: true,
                queueIndex: index,
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

        startQueueLongPress(event, songId, index) {
            if (event.button !== undefined && event.button !== 0) return;
            const clientX = event.touches ? event.touches[0].clientX : event.clientX;
            const clientY = event.touches ? event.touches[0].clientY : event.clientY;
            this._longPressTriggered = false;
            clearTimeout(this._longPressTimer);
            this._longPressTimer = setTimeout(() => {
                this._longPressTriggered = true;
                this.openQueueContextMenu({ clientX, clientY }, songId, index);
            }, 500);
        },

        cancelLongPress() {
            clearTimeout(this._longPressTimer);
            this._longPressTimer = null;
        },

        // ── Queue reordering ─────────────────────────────────────────────
        moveQueueItem(index, direction) {
            const newIndex = index + direction;
            if (newIndex < 0 || newIndex >= this.queue.length) return;
            const q = [...this.queue];
            [q[index], q[newIndex]] = [q[newIndex], q[index]];
            this.queue = q;
            this._syncToDbDebounced(500);
        },

        moveQueueItemToTop(index) {
            if (index === 0) return;
            const q = [...this.queue];
            const [item] = q.splice(index, 1);
            q.unshift(item);
            this.queue = q;
            this._syncToDbDebounced(500);
        },

        moveQueueItemToBottom(index) {
            if (index === this.queue.length - 1) return;
            const q = [...this.queue];
            const [item] = q.splice(index, 1);
            q.push(item);
            this.queue = q;
            this._syncToDbDebounced(500);
        },

        // ── Queue drag-to-reorder ────────────────────────────────────────
        _clearQueueDragOver(el) {
            el.closest('.sp-queue-inner')
              ?.querySelectorAll('.sp-queue-item')
              .forEach(e => e.classList.remove('sp-queue-drag-over'));
        },

        queueDragStart(event, index) {
            this._queueDragIndex = index;
            event.dataTransfer.effectAllowed = 'move';
        },

        queueDragOver(event, el, index) {
            event.preventDefault();
            event.dataTransfer.dropEffect = 'move';
            this._clearQueueDragOver(el);
            el.classList.add('sp-queue-drag-over');
        },

        queueDrop(event, el, index) {
            event.preventDefault();
            this._clearQueueDragOver(el);
            if (this._queueDragIndex === null || this._queueDragIndex === index) {
                this._queueDragIndex = null;
                return;
            }
            const q = [...this.queue];
            const [item] = q.splice(this._queueDragIndex, 1);
            q.splice(index, 0, item);
            this.queue = q;
            this._queueDragIndex = null;
            this._syncToDbDebounced(500);
        },

        queueDragEnd(el) {
            this._clearQueueDragOver(el);
            this._queueDragIndex = null;
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

        async clearHistory() {
            this.history = [];
            await this.$wire.clearHistory();
        },

        playAll(songIds) {
            if (!songIds || !songIds.length) return;
            this.queue = [];
            const [first, ...rest] = songIds;
            this.queue = rest;
            this.loadSong(first);
            this._syncToDbDebounced(500);
        },

        addAllToQueue(songIds) {
            if (!songIds || !songIds.length) return;
            songIds.forEach(id => this.queue.push(id));
            this._syncToDbDebounced(500);
        },

        playFromQueue(index) {
            const songId = this.queue[index];
            this.queue.splice(0, index + 1);
            this.loadSong(songId);
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
                    currentView: this.currentView,
                    selectedAlbum: this.selectedAlbum,
                    selectedArtist: this.selectedArtist,
                    selectedPlaylistId: this.selectedPlaylistId,
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
