document.addEventListener('alpine:init', () => {
    Alpine.data('musicPlayer', (songs, favourites, playlists) => ({
        // Playback state
        songs,
        currentFilename: null,
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
        addToPlaylistFilename: null,

        get currentSong() {
            return this.songs.find(s => s.filename === this.currentFilename) ?? null;
        },

        get filteredSongs() {
            if (this.currentView === 'favourites') {
                return this.songs.filter(s => this.favourites.includes(s.filename));
            }
            if (this.currentView === 'playlist' && this.selectedPlaylistId !== null) {
                const pl = this.playlists.find(p => p.id === this.selectedPlaylistId);
                if (!pl) return this.songs;
                return pl.songs
                    .map(f => this.songs.find(s => s.filename === f))
                    .filter(Boolean);
            }
            return this.songs;
        },

        get selectedPlaylist() {
            return this.playlists.find(p => p.id === this.selectedPlaylistId) ?? null;
        },

        isFavourite(filename) {
            return this.favourites.includes(filename);
        },

        isInSelectedPlaylist(filename) {
            return this.selectedPlaylist?.songs.includes(filename) ?? false;
        },

        init() {
            this.audio = new Audio();

            const saved = this._loadState();
            if (saved.volume !== undefined) this.volume = saved.volume;
            if (saved.muted !== undefined) this.muted = saved.muted;
            if (saved.shuffle !== undefined) this.shuffle = saved.shuffle;
            if (saved.loop !== undefined) this.loop = saved.loop;

            this.audio.volume = this.volume;
            this.audio.muted = this.muted;

            this.audio.addEventListener('timeupdate', () => {
                this.currentTime = this.audio.currentTime;
                this.progress = this.duration ? (this.currentTime / this.duration) * 100 : 0;
                this._saveState();
            });

            this.audio.addEventListener('loadedmetadata', () => {
                this.duration = this.audio.duration;
                if (saved.currentTime) {
                    this.audio.currentTime = saved.currentTime;
                    saved.currentTime = 0;
                }
            });

            this.audio.addEventListener('ended', () => {
                if (this.loop === 'one') {
                    this.audio.currentTime = 0;
                    this.audio.play();
                } else {
                    this.next();
                }
            });

            if (this.songs.length) {
                const filename = saved.currentFilename;
                const exists = filename && this.songs.find(s => s.filename === filename);
                this.loadSong(exists ? filename : this.songs[0].filename, false);
            }
        },

        loadSong(filename, autoplay = true) {
            const song = this.songs.find(s => s.filename === filename);
            if (!song) return;
            this.currentFilename = filename;
            this.audio.src = song.url;
            this.audio.load();
            if (autoplay) {
                this.audio.play();
                this.playing = true;
            } else {
                this.playing = false;
            }
        },

        play(filename) {
            if (this.currentFilename === filename && this.playing) {
                this.audio.pause();
                this.playing = false;
            } else if (this.currentFilename === filename) {
                this.audio.play();
                this.playing = true;
            } else {
                this.loadSong(filename);
            }
        },

        togglePlay() {
            if (!this.songs.length) return;
            if (this.playing) {
                this.audio.pause();
                this.playing = false;
            } else {
                if (!this.currentFilename && this.filteredSongs.length) {
                    this.loadSong(this.filteredSongs[0].filename);
                } else {
                    this.audio.play();
                    this.playing = true;
                }
            }
        },

        next() {
            const songs = this.filteredSongs;
            if (!songs.length) return;

            let nextFilename;
            if (this.shuffle) {
                if (!this.shuffleQueue.length) this.buildShuffleQueue(songs);
                nextFilename = this.shuffleQueue.pop();
            } else {
                const idx = songs.findIndex(s => s.filename === this.currentFilename);
                const nextIdx = idx + 1;
                if (nextIdx >= songs.length && this.loop === 'none') {
                    this.audio.pause();
                    this.playing = false;
                    return;
                }
                nextFilename = songs[nextIdx % songs.length].filename;
            }
            this.loadSong(nextFilename);
        },

        prev() {
            const songs = this.filteredSongs;
            if (!songs.length) return;
            if (this.audio.currentTime > 3) {
                this.audio.currentTime = 0;
                return;
            }
            const idx = songs.findIndex(s => s.filename === this.currentFilename);
            const prevIdx = (idx - 1 + songs.length) % songs.length;
            this.loadSong(songs[prevIdx].filename);
        },

        toggleShuffle() {
            this.shuffle = !this.shuffle;
            if (this.shuffle) this.buildShuffleQueue(this.filteredSongs);
            this._saveState();
        },

        toggleLoop() {
            if (this.loop === 'none') this.loop = 'all';
            else if (this.loop === 'all') this.loop = 'one';
            else this.loop = 'none';
            this._saveState();
        },

        buildShuffleQueue(songs) {
            const filenames = songs
                .map(s => s.filename)
                .filter(f => f !== this.currentFilename);
            for (let i = filenames.length - 1; i > 0; i--) {
                const j = Math.floor(Math.random() * (i + 1));
                [filenames[i], filenames[j]] = [filenames[j], filenames[i]];
            }
            this.shuffleQueue = filenames;
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
        },

        toggleMute() {
            this.muted = !this.muted;
            this.audio.muted = this.muted;
            this._saveState();
        },

        formatTime(seconds) {
            if (!seconds || isNaN(seconds)) return '0:00';
            const m = Math.floor(seconds / 60);
            const s = Math.floor(seconds % 60).toString().padStart(2, '0');
            return `${m}:${s}`;
        },

        // ── Favourites ──────────────────────────────────────────────────
        async toggleFavourite(filename) {
            if (this.favourites.includes(filename)) {
                this.favourites = this.favourites.filter(f => f !== filename);
            } else {
                this.favourites.push(filename);
            }
            await this.$wire.toggleFavourite(filename);
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

        openAddToPlaylist(filename) {
            this.addToPlaylistFilename = filename;
            this.showAddToPlaylistModal = true;
        },

        async toggleSongInPlaylist(playlistId, filename) {
            const pl = this.playlists.find(p => p.id === playlistId);
            if (!pl) return;

            if (pl.songs.includes(filename)) {
                await this.$wire.removeFromPlaylist(playlistId, filename);
                pl.songs = pl.songs.filter(f => f !== filename);
            } else {
                await this.$wire.addToPlaylist(playlistId, filename);
                pl.songs.push(filename);
            }
        },

        // ── State persistence ────────────────────────────────────────────
        _saveState() {
            try {
                localStorage.setItem('musicPlayerState', JSON.stringify({
                    currentFilename: this.currentFilename,
                    currentTime: this.audio.currentTime,
                    volume: this.volume,
                    muted: this.muted,
                    shuffle: this.shuffle,
                    loop: this.loop,
                }));
            } catch (_) {}
        },

        _loadState() {
            try {
                return JSON.parse(localStorage.getItem('musicPlayerState') || '{}');
            } catch (_) { return {}; }
        },
    }));
});
