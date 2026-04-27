<div
    x-data="musicPlayer({{ Js::from($songs) }}, {{ Js::from($favourites) }}, {{ Js::from($playlists) }}, {{ Js::from($session) }}, {{ Js::from($history) }})"
    x-init="init()"
    class="d-flex flex-column sp-root"
>
    {{-- ── TOP SECTION ── --}}
    <div class="sp-top-section flex-grow-1 d-flex overflow-hidden px-2 pt-2 gap-0">

        {{-- ═══ LEFT SIDEBAR (collapsible) ═══ --}}
        <div
            class="sp-sidebar-wrapper"
            :class="leftSidebarOpen ? 'sp-sidebar-open' : 'sp-sidebar-closed'"
        >
            <div class="sp-sidebar-inner d-flex flex-column gap-2 h-100">

                {{-- Navigation --}}
                <div class="sp-card rounded-3 p-3">
                    <div class="d-flex align-items-center gap-2 px-2 mb-3">
                        <i class="bi bi-music-note-beamed text-success fs-4"></i>
                        <span class="fw-bold text-white">Examedia Music</span>
                    </div>
                    <nav class="d-flex flex-column gap-1">
                        <a href="#" class="sp-nav-link" :class="currentView === 'search' ? 'active' : ''"
                           @click.prevent="goTo('search'); $nextTick(() => $refs.searchInput && $refs.searchInput.focus())">
                            <i class="bi bi-search"></i>
                            <span>Search</span>
                        </a>
                        <a href="#" class="sp-nav-link" :class="currentView === 'all' ? 'active' : ''"
                           @click.prevent="goTo('all')">
                            <i class="bi bi-house-fill"></i>
                            <span>Home</span>
                        </a>
                        <a href="#" class="sp-nav-link" :class="currentView === 'favourites' ? 'active' : ''"
                           @click.prevent="goTo('favourites')">
                            <i class="bi bi-heart-fill"></i>
                            <span>Liked Songs</span>
                        </a>
                        <a href="#" class="sp-nav-link" :class="currentView === 'albums' ? 'active' : ''"
                           @click.prevent="goTo('albums')">
                            <i class="bi bi-disc-fill"></i>
                            <span>Albums</span>
                        </a>
                        <a href="#" class="sp-nav-link" :class="currentView === 'artists' ? 'active' : ''"
                           @click.prevent="goTo('artists')">
                            <i class="bi bi-person-fill"></i>
                            <span>Artists</span>
                        </a>
                        <a href="#" class="sp-nav-link" :class="currentView === 'history' ? 'active' : ''"
                           @click.prevent="goTo('history')">
                            <i class="bi bi-clock-history"></i>
                            <span>History</span>
                        </a>
                        <a href="{{ route('music.upload') }}" class="sp-nav-link">
                            <i class="bi bi-cloud-upload-fill"></i>
                            <span>Upload</span>
                        </a>
                    </nav>
                </div>

                {{-- Library --}}
                <div class="sp-card rounded-3 d-flex flex-column flex-grow-1 overflow-hidden">
                    <div class="d-flex align-items-center justify-content-between px-3 py-2 border-bottom border-secondary border-opacity-25">
                        <div class="d-flex align-items-center gap-2 text-secondary small fw-semibold text-uppercase" style="letter-spacing:.04em;">
                            <i class="bi bi-collection-fill"></i>
                            <span>Your Library</span>
                        </div>
                        <button class="sp-icon-btn" title="Create Playlist" @click="openCreatePlaylist()">
                            <i class="bi bi-plus-lg"></i>
                        </button>
                    </div>

                    <div class="overflow-y-auto flex-grow-1">
                        <div
                            class="sp-playlist-item rounded mx-2 my-1"
                            :class="currentView === 'favourites' ? 'sp-playlist-active' : ''"
                            @click="goTo('favourites')"
                        >
                            <div class="sp-playlist-art rounded" style="background:linear-gradient(135deg,#450af5,#c4efd9);">
                                <i class="bi bi-heart-fill text-white" style="font-size:.85rem;"></i>
                            </div>
                            <div class="overflow-hidden flex-grow-1">
                                <div class="text-truncate small fw-medium" :class="currentView === 'favourites' ? 'sp-active-text' : ''">Liked Songs</div>
                                <div class="text-truncate" style="font-size:.75rem;color:var(--sp-muted);" x-text="favourites.length + ' songs'"></div>
                            </div>
                        </div>

                        <template x-for="playlist in playlists" :key="playlist.id">
                            <div
                                class="sp-playlist-item rounded mx-2 my-1"
                                :class="currentView === 'playlist' && selectedPlaylistId === playlist.id ? 'sp-playlist-active' : ''"
                                @click="goTo('playlist', { playlistId: playlist.id })"
                            >
                                <div class="sp-playlist-art rounded" style="background:#333;">
                                    <i class="bi bi-music-note-list" style="font-size:.85rem;color:var(--sp-muted);"></i>
                                </div>
                                <div class="overflow-hidden flex-grow-1">
                                    <div class="text-truncate small fw-medium"
                                         :class="currentView === 'playlist' && selectedPlaylistId === playlist.id ? 'sp-active-text' : ''"
                                         x-text="playlist.title"></div>
                                    <div class="text-truncate" style="font-size:.75rem;color:var(--sp-muted);" x-text="playlist.songs.length + ' songs'"></div>
                                </div>
                            </div>
                        </template>

                        <div x-show="playlists.length === 0" class="px-3 py-2 text-secondary" style="font-size:.78rem;">
                            No playlists yet. Hit <strong>+</strong> to create one.
                        </div>
                    </div>
                </div>

            </div>
        </div>

        {{-- ═══ MAIN CONTENT ═══ --}}
        <div class="sp-card rounded-3 flex-grow-1 overflow-y-auto d-flex flex-column mx-2" x-ref="mainContent">

            {{-- ─── Back / Forward navigation ─── --}}
            <div class="sp-nav-history d-flex align-items-center gap-1 px-3 py-2 flex-shrink-0">
                <button class="sp-hist-btn" :disabled="!canGoBack" @click="navBack()" title="Go back">
                    <i class="bi bi-chevron-left"></i>
                </button>
                <button class="sp-hist-btn" :disabled="!canGoForward" @click="navForward()" title="Go forward">
                    <i class="bi bi-chevron-right"></i>
                </button>
            </div>

            {{-- ─── Headers ─── --}}

            {{-- Header: All Songs --}}
            <template x-if="currentView === 'all'">
                <div class="sp-header-gradient sp-header-green flex-shrink-0 px-4 pb-3 pt-4">
                    <p class="text-uppercase fw-semibold small mb-1" style="color:rgba(255,255,255,.7);letter-spacing:.06em;">Good Evening</p>
                    <h1 class="fw-bold mb-3" style="font-size:2rem;">Your Music</h1>
                    <div class="d-flex align-items-center gap-3" x-show="songs.length">
                        <button class="sp-play-all-btn" @click="playAll(songs.map(s => s.id))" title="Play all songs">
                            <i class="bi bi-play-fill"></i>
                        </button>
                        <span class="text-white-50 small fw-semibold" x-text="songs.length + ' songs'"></span>
                    </div>
                </div>
            </template>

            {{-- Header: Liked Songs --}}
            <template x-if="currentView === 'favourites'">
                <div class="sp-header-gradient sp-header-purple flex-shrink-0 px-4 pb-3 pt-4">
                    <p class="text-uppercase fw-semibold small mb-1" style="color:rgba(255,255,255,.7);letter-spacing:.06em;">Playlist</p>
                    <h1 class="fw-bold mb-1 d-flex align-items-center gap-3" style="font-size:2rem;">
                        <i class="bi bi-heart-fill text-white fs-3"></i>
                        Liked Songs
                    </h1>
                    <p class="mb-3 small" style="color:rgba(255,255,255,.7);" x-text="favourites.length + ' songs'"></p>
                    <div class="d-flex align-items-center gap-3" x-show="favourites.length">
                        <button class="sp-play-all-btn" @click="playAll(filteredSongs.map(s => s.id))" title="Play liked songs">
                            <i class="bi bi-play-fill"></i>
                        </button>
                        <span class="text-white-50 small fw-semibold">Play all liked songs</span>
                    </div>
                </div>
            </template>

            {{-- Header: Playlist view --}}
            <template x-if="currentView === 'playlist' && selectedPlaylist">
                <div class="sp-header-gradient sp-header-dark flex-shrink-0 px-4 pb-3 pt-4">
                    <p class="text-uppercase fw-semibold small mb-1" style="color:rgba(255,255,255,.7);letter-spacing:.06em;">Playlist</p>
                    <h1 class="fw-bold mb-1" style="font-size:2rem;" x-text="selectedPlaylist.title"></h1>
                    <p class="mb-3 small" style="color:rgba(255,255,255,.6);" x-text="selectedPlaylist.description || ''"></p>
                    <div class="d-flex align-items-center gap-3 flex-wrap">
                        <button class="sp-play-all-btn" @click="playAll(filteredSongs.map(s => s.id))" title="Play playlist" x-show="filteredSongs.length">
                            <i class="bi bi-play-fill"></i>
                        </button>
                        <span class="small" style="color:rgba(255,255,255,.5);" x-text="selectedPlaylist.songs.length + ' songs'"></span>
                        <button class="sp-pill-btn" @click="openEditPlaylist(selectedPlaylist)">
                            <i class="bi bi-pencil"></i> Edit
                        </button>
                        <button class="sp-pill-btn sp-pill-btn-danger" @click="deletePlaylist(selectedPlaylist.id)">
                            <i class="bi bi-trash"></i> Delete
                        </button>
                    </div>
                </div>
            </template>

            {{-- Header: Albums browse --}}
            <template x-if="currentView === 'albums'">
                <div class="sp-header-gradient sp-header-dark flex-shrink-0 px-4 pb-3 pt-4">
                    <p class="text-uppercase fw-semibold small mb-1" style="color:rgba(255,255,255,.7);letter-spacing:.06em;">Browse</p>
                    <h1 class="fw-bold mb-0 d-flex align-items-center gap-3" style="font-size:2rem;">
                        <i class="bi bi-disc-fill fs-3"></i> Albums
                    </h1>
                    <p class="mb-0 small mt-1" style="color:rgba(255,255,255,.7);" x-text="albumList.length + ' albums'"></p>
                </div>
            </template>

            {{-- Header: Artists browse --}}
            <template x-if="currentView === 'artists'">
                <div class="sp-header-gradient sp-header-dark flex-shrink-0 px-4 pb-3 pt-4">
                    <p class="text-uppercase fw-semibold small mb-1" style="color:rgba(255,255,255,.7);letter-spacing:.06em;">Browse</p>
                    <h1 class="fw-bold mb-0 d-flex align-items-center gap-3" style="font-size:2rem;">
                        <i class="bi bi-person-fill fs-3"></i> Artists
                    </h1>
                    <p class="mb-0 small mt-1" style="color:rgba(255,255,255,.7);" x-text="artistList.length + ' artists'"></p>
                </div>
            </template>

            {{-- Header: Album detail --}}
            <template x-if="currentView === 'album'">
                <div class="sp-header-gradient sp-header-dark flex-shrink-0 px-4 pb-3 pt-3">
                    <button class="btn btn-link p-0 text-decoration-none mb-2 d-inline-flex align-items-center gap-1"
                            style="color:rgba(255,255,255,.6);font-size:.82rem;"
                            @click="goTo('albums')">
                        <i class="bi bi-chevron-left"></i> Albums
                    </button>
                    <div class="d-flex align-items-end gap-4">
                        <div class="rounded-3 overflow-hidden flex-shrink-0 shadow" style="width:130px;height:130px;background:#333;">
                            <template x-if="filteredSongs.length && filteredSongs[0].artwork_url">
                                <img :src="filteredSongs[0].artwork_url" class="w-100 h-100 object-fit-cover" alt="">
                            </template>
                            <template x-if="!filteredSongs.length || !filteredSongs[0].artwork_url">
                                <div class="w-100 h-100 d-flex align-items-center justify-content-center">
                                    <i class="bi bi-disc text-secondary" style="font-size:3rem;"></i>
                                </div>
                            </template>
                        </div>
                        <div>
                            <p class="text-uppercase fw-semibold small mb-1" style="color:rgba(255,255,255,.7);letter-spacing:.06em;">Album</p>
                            <h1 class="fw-bold mb-1" style="font-size:1.8rem;line-height:1.1;" x-text="selectedAlbum"></h1>
                            <p class="mb-1 small" style="color:rgba(255,255,255,.7);">
                                <span
                                    x-text="filteredSongs[0]?.artist ?? ''"
                                    x-show="filteredSongs[0]?.artist"
                                    @click="viewArtist(filteredSongs[0].artist)"
                                    style="cursor:pointer;"
                                    class="fw-semibold text-white"
                                    :title="'See all songs by ' + (filteredSongs[0]?.artist ?? '')">
                                </span>
                            </p>
                            <p class="mb-3 small" style="color:rgba(255,255,255,.5);" x-text="filteredSongs.length + ' songs'"></p>
                            <div class="d-flex align-items-center gap-3" x-show="filteredSongs.length">
                                <button class="sp-play-all-btn" @click="playAll(filteredSongs.map(s => s.id))" title="Play album">
                                    <i class="bi bi-play-fill"></i>
                                </button>
                                <span class="text-white-50 small fw-semibold">Play album</span>
                            </div>
                        </div>
                    </div>
                </div>
            </template>

            {{-- Header: Artist detail --}}
            <template x-if="currentView === 'artist'">
                <div class="sp-header-gradient sp-header-dark flex-shrink-0 px-4 pb-3 pt-3">
                    <button class="btn btn-link p-0 text-decoration-none mb-2 d-inline-flex align-items-center gap-1"
                            style="color:rgba(255,255,255,.6);font-size:.82rem;"
                            @click="goTo('artists')">
                        <i class="bi bi-chevron-left"></i> Artists
                    </button>
                    <div class="d-flex align-items-end gap-4">
                        <div class="rounded-circle overflow-hidden flex-shrink-0 shadow" style="width:130px;height:130px;background:#333;">
                            <template x-if="filteredSongs.length && filteredSongs[0].artwork_url">
                                <img :src="filteredSongs[0].artwork_url" class="w-100 h-100 object-fit-cover" alt="">
                            </template>
                            <template x-if="!filteredSongs.length || !filteredSongs[0].artwork_url">
                                <div class="w-100 h-100 d-flex align-items-center justify-content-center">
                                    <i class="bi bi-person text-secondary" style="font-size:3rem;"></i>
                                </div>
                            </template>
                        </div>
                        <div>
                            <p class="text-uppercase fw-semibold small mb-1" style="color:rgba(255,255,255,.7);letter-spacing:.06em;">Artist</p>
                            <h1 class="fw-bold mb-1" style="font-size:1.8rem;line-height:1.1;" x-text="selectedArtist"></h1>
                            <p class="mb-3 small" style="color:rgba(255,255,255,.5);" x-text="filteredSongs.length + ' songs'"></p>
                            <div class="d-flex align-items-center gap-3" x-show="filteredSongs.length">
                                <button class="sp-play-all-btn" @click="playAll(filteredSongs.map(s => s.id))" title="Play all songs by this artist">
                                    <i class="bi bi-play-fill"></i>
                                </button>
                                <span class="text-white-50 small fw-semibold">Play all</span>
                            </div>
                        </div>
                    </div>
                </div>
            </template>

            {{-- Header: Search --}}
            <template x-if="currentView === 'search'">
                <div class="sp-header-gradient sp-header-teal flex-shrink-0 px-4 pb-3 pt-4">
                    <p class="text-uppercase fw-semibold small mb-1" style="color:rgba(255,255,255,.7);letter-spacing:.06em;">Find</p>
                    <h1 class="fw-bold mb-0 d-flex align-items-center gap-3" style="font-size:2rem;">
                        <i class="bi bi-search fs-3"></i> Search
                    </h1>
                </div>
            </template>

            {{-- Header: History --}}
            <template x-if="currentView === 'history'">
                <div class="sp-header-gradient sp-header-dark flex-shrink-0 px-4 pb-3 pt-4">
                    <p class="text-uppercase fw-semibold small mb-1" style="color:rgba(255,255,255,.7);letter-spacing:.06em;">Recent</p>
                    <h1 class="fw-bold mb-1 d-flex align-items-center gap-3" style="font-size:2rem;">
                        <i class="bi bi-clock-history fs-3"></i> History
                    </h1>
                    <p class="mb-3 small" style="color:rgba(255,255,255,.7);" x-text="history.length + ' songs'"></p>
                    <div class="d-flex align-items-center gap-3 flex-wrap">
                        <button class="sp-play-all-btn" @click="playAll(filteredSongs.map(s => s.id))" title="Play all history" x-show="filteredSongs.length">
                            <i class="bi bi-play-fill"></i>
                        </button>
                        <span class="text-white-50 small fw-semibold" x-show="filteredSongs.length">Play all</span>
                        <button class="sp-pill-btn sp-pill-btn-danger" @click="clearHistory()" x-show="history.length">
                            <i class="bi bi-trash"></i> Clear History
                        </button>
                    </div>
                </div>
            </template>

            {{-- ─── Search Filter Bar ─── --}}
            <div x-show="currentView === 'search'" class="px-4 pt-3 pb-1 flex-shrink-0">
                <div class="row g-2 align-items-center">
                    {{-- Text input --}}
                    <div class="col-12 col-md">
                        <div class="input-group">
                            <span class="input-group-text sp-filter-addon">
                                <i class="bi bi-search"></i>
                            </span>
                            <input
                                type="text"
                                x-model="searchQuery"
                                x-ref="searchInput"
                                class="form-control sp-filter-input"
                                placeholder="Search songs…"
                                @keydown.escape="searchQuery = ''"
                            >
                            <button
                                class="input-group-text sp-filter-clear-btn"
                                x-show="searchQuery"
                                @click="searchQuery = ''"
                                title="Clear"
                            >
                                <i class="bi bi-x-lg" style="font-size:.75rem;"></i>
                            </button>
                        </div>
                    </div>

                    {{-- Search by --}}
                    <div class="col-6 col-sm-auto">
                        <select x-model="searchBy" class="form-select sp-filter-select" title="Search by field">
                            <option value="all">All Fields</option>
                            <option value="name">Title</option>
                            <option value="artist">Artist</option>
                            <option value="album">Album</option>
                        </select>
                    </div>

                    {{-- Year --}}
                    <div class="col-6 col-sm-auto">
                        <select x-model="filterYear" class="form-select sp-filter-select" title="Filter by year">
                            <option value="">All Years</option>
                            <template x-for="year in yearList" :key="year">
                                <option :value="year" x-text="year"></option>
                            </template>
                        </select>
                    </div>

                    {{-- Genre --}}
                    <div class="col-6 col-sm-auto">
                        <select x-model="filterGenre" class="form-select sp-filter-select" title="Filter by genre">
                            <option value="">All Genres</option>
                            <template x-for="genre in genreList" :key="genre">
                                <option :value="genre" x-text="genre"></option>
                            </template>
                        </select>
                    </div>

                    {{-- Clear all --}}
                    <div class="col-auto" x-show="searchQuery || filterYear || filterGenre">
                        <button
                            class="sp-pill-btn"
                            @click="searchQuery = ''; searchBy = 'all'; filterYear = ''; filterGenre = ''"
                            title="Clear all filters"
                        >
                            <i class="bi bi-x-circle"></i> Clear
                        </button>
                    </div>
                </div>

                {{-- Result count + play button --}}
                <div class="mt-2 d-flex align-items-center gap-3"
                     x-show="searchQuery || filterYear || filterGenre">
                    <span class="text-secondary" style="font-size:.8rem;"
                          x-text="filteredSongs.length + ' result' + (filteredSongs.length !== 1 ? 's' : '') + ' found'">
                    </span>
                    <button class="sp-play-all-btn sp-play-all-btn-sm"
                            x-show="filteredSongs.length"
                            @click="playAll(filteredSongs.map(s => s.id))"
                            title="Play all results">
                        <i class="bi bi-play-fill"></i>
                    </button>
                    <span class="text-white-50 small" x-show="filteredSongs.length">Play all results</span>
                </div>
            </div>

            {{-- ─── Albums Browse Grid ─── --}}
            <div x-show="currentView === 'albums'" class="px-4 pb-4 pt-3 flex-grow-1">
                <div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-xl-5 g-3">
                    <template x-for="album in albumList" :key="album.name">
                        <div class="col">
                            <div class="sp-song-card rounded-3 h-100" @click="viewAlbum(album.name)" style="cursor:pointer;">
                                <div class="sp-song-card-art rounded-2">
                                    <template x-if="album.artwork_url">
                                        <img :src="album.artwork_url" class="w-100 h-100 object-fit-cover rounded-2" alt="">
                                    </template>
                                    <template x-if="!album.artwork_url">
                                        <div class="sp-song-card-art-placeholder">
                                            <i class="bi bi-disc text-secondary fs-4"></i>
                                        </div>
                                    </template>
                                </div>
                                <div class="pt-3 px-1 pb-1">
                                    <div class="text-truncate small fw-semibold" x-text="album.name"></div>
                                    <div class="text-truncate mt-1" x-text="album.artist ?? 'Various Artists'" style="font-size:.8rem;color:var(--sp-muted);"></div>
                                    <div class="mt-1" style="font-size:.75rem;color:var(--sp-muted);" x-text="album.count + ' songs'"></div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
                <div x-show="albumList.length === 0" class="text-center py-5">
                    <i class="bi bi-disc text-secondary" style="font-size:3rem;"></i>
                    <p class="text-secondary mt-3 mb-1">No albums found.</p>
                    <p class="text-secondary small">Songs with album metadata will appear here.</p>
                </div>
            </div>

            {{-- ─── Artists Browse Grid ─── --}}
            <div x-show="currentView === 'artists'" class="px-4 pb-4 pt-3 flex-grow-1">
                <div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-xl-5 g-3">
                    <template x-for="artist in artistList" :key="artist.name">
                        <div class="col">
                            <div class="sp-song-card rounded-3 h-100 text-center" @click="viewArtist(artist.name)" style="cursor:pointer;">
                                <div class="sp-song-card-art rounded-circle mx-auto" style="border-radius:50%!important;">
                                    <template x-if="artist.artwork_url">
                                        <img :src="artist.artwork_url" class="w-100 h-100 object-fit-cover" style="border-radius:50%;" alt="">
                                    </template>
                                    <template x-if="!artist.artwork_url">
                                        <div class="sp-song-card-art-placeholder" style="border-radius:50%;">
                                            <i class="bi bi-person text-secondary fs-4"></i>
                                        </div>
                                    </template>
                                </div>
                                <div class="pt-3 px-1 pb-1">
                                    <div class="text-truncate small fw-semibold" x-text="artist.name"></div>
                                    <div class="mt-1" style="font-size:.75rem;color:var(--sp-muted);" x-text="artist.count + ' songs'"></div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
                <div x-show="artistList.length === 0" class="text-center py-5">
                    <i class="bi bi-person text-secondary" style="font-size:3rem;"></i>
                    <p class="text-secondary mt-3 mb-1">No artists found.</p>
                    <p class="text-secondary small">Songs with artist metadata will appear here.</p>
                </div>
            </div>

            {{-- ─── Song grid / list (all, favourites, playlist, album, artist, search views) ─── --}}
            <div x-show="!['albums','artists'].includes(currentView)" class="px-4 pb-2 pt-1 flex-shrink-0">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h2 class="fw-bold mb-0 fs-5">
                        <span x-show="currentView !== 'search'">Songs</span>
                        <span x-show="currentView === 'search'" x-text="filteredSongs.length + (filteredSongs.length !== 1 ? ' Songs' : ' Song')"></span>
                    </h2>
                    <div class="d-flex align-items-center gap-2">
                        <button class="btn btn-link text-secondary text-decoration-none small fw-semibold text-uppercase p-0"
                                style="letter-spacing:.05em;"
                                @click="togglePlay()" x-text="playing ? 'Pause all' : 'Play all'"></button>
                        <div class="sp-view-toggle ms-3">
                            <button class="sp-view-btn" :class="viewMode === 'grid' ? 'sp-view-btn-active' : ''"
                                    @click="viewMode = 'grid'; _saveState(); _syncToDb()" title="Grid view">
                                <i class="bi bi-grid-fill"></i>
                            </button>
                            <button class="sp-view-btn" :class="viewMode === 'list' ? 'sp-view-btn-active' : ''"
                                    @click="viewMode = 'list'; _saveState(); _syncToDb()" title="List view">
                                <i class="bi bi-list-ul"></i>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Grid view --}}
                <div class="sp-song-grid" x-show="viewMode === 'grid'">
                    <template x-for="song in filteredSongs" :key="song.id">
                        <div
                            class="sp-song-card rounded-3"
                            :class="currentSongId === song.id ? 'sp-song-card-active' : (selectedSongId === song.id ? 'sp-song-card-selected' : '')"
                            @click="selectSong(song.id)"
                            @contextmenu.prevent="openContextMenu($event, song.id)"
                            @mousedown="startLongPress($event, song.id)"
                            @mouseup="cancelLongPress()"
                            @mouseleave="cancelLongPress()"
                            @touchstart.passive="startLongPress($event, song.id)"
                            @touchend="cancelLongPress()"
                            @touchmove="cancelLongPress()"
                        >
                            <div class="sp-song-card-art rounded-2">
                                <template x-if="song.artwork_url">
                                    <img :src="song.artwork_url" class="w-100 h-100 object-fit-cover rounded-2" alt="">
                                </template>
                                <template x-if="!song.artwork_url">
                                    <div class="sp-song-card-art-placeholder">
                                        <i class="bi bi-music-note-beamed text-secondary fs-4"></i>
                                    </div>
                                </template>

                                {{-- Play button --}}
                                <button class="sp-song-card-play-btn" @click.stop="play(song.id)">
                                    <i class="bi" :class="currentSongId === song.id && playing ? 'bi-pause-fill' : 'bi-play-fill'"></i>
                                </button>

                                {{-- Right-side action buttons (hover) --}}
                                <div class="sp-song-actions">
                                    <button
                                        class="sp-song-action-btn"
                                        :class="isFavourite(song.id) ? 'sp-song-action-btn-active' : ''"
                                        @click.stop="toggleFavourite(song.id)"
                                        :title="isFavourite(song.id) ? 'Remove from Liked Songs' : 'Add to Liked Songs'"
                                    >
                                        <i class="bi" :class="isFavourite(song.id) ? 'bi-heart-fill' : 'bi-heart'"></i>
                                    </button>
                                    <button
                                        class="sp-song-action-btn"
                                        @click.stop="addToQueue(song.id)"
                                        title="Add to Queue"
                                    >
                                        <i class="bi bi-collection-play"></i>
                                    </button>
                                    <button
                                        class="sp-song-action-btn"
                                        @click.stop="openAddToPlaylist(song.id)"
                                        title="Add to Playlist"
                                        x-show="playlists.length > 0"
                                    >
                                        <i class="bi bi-plus-lg"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="pt-3 px-1 pb-1">
                                <div class="text-truncate small fw-semibold" :class="currentSongId === song.id ? 'sp-active-text' : ''" x-text="song.title"></div>
                                {{-- Clickable artist name --}}
                                <div
                                    class="text-truncate mt-1"
                                    style="font-size:.8rem;color:var(--sp-muted);"
                                    :style="song.artist ? 'cursor:pointer;' : ''"
                                    :title="song.artist ? 'See all songs by ' + song.artist : ''"
                                    @click.stop="song.artist && viewArtist(song.artist)"
                                    x-text="song.artist ?? 'Unknown Artist'"
                                ></div>
                                <template x-if="songPlaylists(song.id).length > 0">
                                    <div class="d-flex flex-wrap gap-1 mt-1">
                                        <template x-for="pl in songPlaylists(song.id)" :key="pl.id">
                                            <span class="badge rounded-pill text-bg-success" style="font-size:.6rem;font-weight:600;max-width:90px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" x-text="pl.title"></span>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>

                {{-- List view (Spotify style) --}}
                <div x-show="viewMode === 'list'" class="sp-list-view">
                    {{-- List header --}}
                    <div class="sp-list-header d-flex align-items-center px-3 py-2 mb-1">
                        <div class="sp-list-col-num text-secondary" style="font-size:.8rem;">#</div>
                        <div class="sp-list-col-title text-secondary text-uppercase fw-semibold" style="font-size:.72rem;letter-spacing:.05em;">Title</div>
                        <div class="sp-list-col-album text-secondary text-uppercase fw-semibold d-none d-md-block" style="font-size:.72rem;letter-spacing:.05em;">Album</div>
                        <div class="sp-list-col-actions"></div>
                        <div class="sp-list-col-dur text-secondary" style="font-size:.8rem;"><i class="bi bi-clock"></i></div>
                    </div>
                    <div class="border-bottom border-secondary border-opacity-25 mb-2"></div>

                    <template x-for="(song, index) in filteredSongs" :key="song.id">
                        <div
                            class="sp-list-row d-flex align-items-center px-3 rounded-2"
                            :class="currentSongId === song.id ? 'sp-list-row-active' : (selectedSongId === song.id ? 'sp-list-row-selected' : '')"
                            @click="selectSong(song.id)"
                            @contextmenu.prevent="openContextMenu($event, song.id)"
                            @mousedown="startLongPress($event, song.id)"
                            @mouseup="cancelLongPress()"
                            @mouseleave="cancelLongPress()"
                            @touchstart.passive="startLongPress($event, song.id)"
                            @touchend="cancelLongPress()"
                            @touchmove="cancelLongPress()"
                        >
                            {{-- # / play indicator --}}
                            <div class="sp-list-col-num position-relative">
                                <span class="sp-list-num" :class="currentSongId === song.id ? 'sp-active-text' : 'text-secondary'"
                                      x-text="currentSongId === song.id && playing ? '' : (index + 1)"></span>
                                <i class="bi bi-volume-up-fill sp-list-playing-icon sp-active-text"
                                   x-show="currentSongId === song.id && playing" style="font-size:.85rem;"></i>
                                <button class="sp-list-play-btn" @click.stop="play(song.id)">
                                    <i class="bi" :class="currentSongId === song.id && playing ? 'bi-pause-fill' : 'bi-play-fill'"></i>
                                </button>
                            </div>

                            {{-- Artwork + Title + Artist --}}
                            <div class="sp-list-col-title d-flex align-items-center gap-3 overflow-hidden">
                                <div class="sp-list-art rounded-1 flex-shrink-0">
                                    <template x-if="song.artwork_url">
                                        <img :src="song.artwork_url" class="w-100 h-100 object-fit-cover rounded-1" alt="">
                                    </template>
                                    <template x-if="!song.artwork_url">
                                        <div class="w-100 h-100 d-flex align-items-center justify-content-center">
                                            <i class="bi bi-music-note text-secondary" style="font-size:.8rem;"></i>
                                        </div>
                                    </template>
                                </div>
                                <div class="overflow-hidden">
                                    <div class="text-truncate fw-semibold" style="font-size:.88rem;"
                                         :class="currentSongId === song.id ? 'sp-active-text' : ''"
                                         x-text="song.title"></div>
                                    {{-- Clickable artist in list view --}}
                                    <div
                                        class="text-truncate"
                                        style="font-size:.78rem;color:var(--sp-muted);"
                                        :style="song.artist ? 'cursor:pointer;' : ''"
                                        :title="song.artist ? 'See all songs by ' + song.artist : ''"
                                        @click.stop="song.artist && viewArtist(song.artist)"
                                        x-text="song.artist ?? 'Unknown Artist'"
                                    ></div>
                                    <template x-if="songPlaylists(song.id).length > 0">
                                        <div class="d-flex flex-wrap gap-1 mt-1">
                                            <template x-for="pl in songPlaylists(song.id)" :key="pl.id">
                                                <span class="badge rounded-pill text-bg-success" style="font-size:.6rem;font-weight:600;max-width:90px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" x-text="pl.title"></span>
                                            </template>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            {{-- Album (clickable) --}}
                            <div class="sp-list-col-album d-none d-md-block overflow-hidden">
                                <span
                                    class="text-truncate d-block"
                                    style="font-size:.82rem;color:var(--sp-muted);"
                                    :style="song.album ? 'cursor:pointer;' : ''"
                                    :title="song.album ? 'See album: ' + song.album : ''"
                                    @click.stop="song.album && viewAlbum(song.album)"
                                    x-text="song.album ?? ''">
                                </span>
                            </div>

                            {{-- Action buttons --}}
                            <div class="sp-list-col-actions d-flex align-items-center justify-content-end gap-1 sp-list-actions">
                                <button
                                    class="sp-icon-btn sp-list-action-btn"
                                    :class="isFavourite(song.id) ? 'sp-icon-btn-active' : ''"
                                    @click.stop="toggleFavourite(song.id)"
                                    :title="isFavourite(song.id) ? 'Remove from Liked Songs' : 'Add to Liked Songs'"
                                    style="font-size:.85rem;"
                                >
                                    <i class="bi" :class="isFavourite(song.id) ? 'bi-heart-fill' : 'bi-heart'"></i>
                                </button>
                                <button
                                    class="sp-icon-btn sp-list-action-btn"
                                    @click.stop="addToQueue(song.id)"
                                    title="Add to Queue"
                                    style="font-size:.85rem;"
                                >
                                    <i class="bi bi-collection-play"></i>
                                </button>
                                <button
                                    class="sp-icon-btn sp-list-action-btn"
                                    @click.stop="openAddToPlaylist(song.id)"
                                    title="Add to Playlist"
                                    x-show="playlists.length > 0"
                                    style="font-size:.85rem;"
                                >
                                    <i class="bi bi-plus-lg"></i>
                                </button>
                                <template x-if="currentView === 'playlist'">
                                    <button
                                        class="sp-icon-btn sp-list-action-btn"
                                        @click.stop="toggleSongInPlaylist(selectedPlaylistId, song.id)"
                                        title="Remove from Playlist"
                                        style="font-size:.85rem;color:#e55;"
                                    >
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                </template>
                            </div>

                            {{-- Duration --}}
                            <div class="sp-list-col-dur text-secondary text-end" style="font-size:.82rem;"
                                 x-text="song.duration ?? '—'"></div>
                        </div>
                    </template>
                </div>

                {{-- Empty state --}}
                <div x-show="filteredSongs.length === 0" class="text-center py-5">
                    <template x-if="currentView === 'favourites'">
                        <div>
                            <i class="bi bi-heart text-secondary" style="font-size:3rem;"></i>
                            <p class="text-secondary mt-3 mb-1">No liked songs yet.</p>
                            <p class="text-secondary small">Click the heart on any song to add it here.</p>
                        </div>
                    </template>
                    <template x-if="currentView === 'playlist'">
                        <div>
                            <i class="bi bi-music-note-list text-secondary" style="font-size:3rem;"></i>
                            <p class="text-secondary mt-3 mb-1">This playlist is empty.</p>
                            <p class="text-secondary small">Go to Home and use the <strong>+</strong> button on a song to add it.</p>
                        </div>
                    </template>
                    <template x-if="currentView === 'all'">
                        <div>
                            <i class="bi bi-folder-x text-secondary" style="font-size:3rem;"></i>
                            <p class="text-secondary mt-3 mb-1">No songs in your library.</p>
                            <p class="text-secondary small">
                                <a href="{{ route('music.upload') }}" style="color:var(--sp-green);">Upload songs</a>
                                or use Scan &amp; Import on the upload page.
                            </p>
                        </div>
                    </template>
                    <template x-if="currentView === 'album'">
                        <div>
                            <i class="bi bi-disc text-secondary" style="font-size:3rem;"></i>
                            <p class="text-secondary mt-3 mb-1">No songs in this album.</p>
                        </div>
                    </template>
                    <template x-if="currentView === 'artist'">
                        <div>
                            <i class="bi bi-person text-secondary" style="font-size:3rem;"></i>
                            <p class="text-secondary mt-3 mb-1">No songs from this artist.</p>
                        </div>
                    </template>
                    <template x-if="currentView === 'search'">
                        <div>
                            <i class="bi bi-search text-secondary" style="font-size:3rem;"></i>
                            <p class="text-secondary mt-3 mb-1">No results found.</p>
                            <p class="text-secondary small">Try a different keyword or adjust the filters.</p>
                        </div>
                    </template>
                    <template x-if="currentView === 'history'">
                        <div>
                            <i class="bi bi-clock-history text-secondary" style="font-size:3rem;"></i>
                            <p class="text-secondary mt-3 mb-1">No songs played yet.</p>
                            <p class="text-secondary small">Songs you play will appear here.</p>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        {{-- ═══ RIGHT QUEUE SIDEBAR ═══ --}}
        <div
            class="sp-queue-wrapper"
            :class="rightSidebarOpen ? 'sp-queue-open' : 'sp-queue-closed'"
        >
            <div class="sp-card rounded-3 d-flex flex-column h-100 sp-queue-inner">

                {{-- Queue header --}}
                <div class="d-flex align-items-center justify-content-between px-3 py-2 border-bottom border-secondary border-opacity-25 flex-shrink-0">
                    <span class="fw-bold small text-uppercase" style="letter-spacing:.04em;">Queue</span>
                    <button class="sp-icon-btn" @click="clearQueue()" x-show="queue.length > 0" title="Clear queue">
                        <i class="bi bi-x-circle"></i>
                    </button>
                </div>

                {{-- Now Playing in queue --}}
                <div x-show="!currentSong" class="px-3 py-4 flex-shrink-0 border-bottom border-secondary border-opacity-10 text-center">
                    <p class="text-uppercase fw-semibold mb-2" style="font-size:.68rem;letter-spacing:.05em;color:var(--sp-muted);">Now Playing</p>
                    <div class="d-flex flex-column align-items-center gap-2 py-2">
                        <div class="rounded-2 d-flex align-items-center justify-content-center" style="width:52px;height:52px;background:var(--sp-hover);">
                            <i class="bi bi-music-note text-secondary" style="font-size:1.4rem;"></i>
                        </div>
                        <p class="text-secondary mb-0" style="font-size:.78rem;">Nothing is playing</p>
                    </div>
                </div>
                <div x-show="currentSong" class="px-3 py-2 flex-shrink-0 border-bottom border-secondary border-opacity-10">
                    <p class="text-uppercase fw-semibold mb-2" style="font-size:.68rem;letter-spacing:.05em;color:var(--sp-muted);">Now Playing</p>
                    <div
                        class="d-flex align-items-center gap-2 rounded-2 px-1 py-1"
                        style="cursor:default;"
                        @contextmenu.prevent="currentSong && openContextMenu($event, currentSong.id)"
                        @mousedown="currentSong && startLongPress($event, currentSong.id)"
                        @mouseup="cancelLongPress()"
                        @mouseleave="cancelLongPress()"
                        @touchstart.passive="currentSong && startLongPress($event, currentSong.id)"
                        @touchend="cancelLongPress()"
                        @touchmove="cancelLongPress()"
                    >
                        <div class="sp-queue-art rounded-2">
                            <template x-if="currentSong && currentSong.artwork_url">
                                <img :src="currentSong.artwork_url" class="w-100 h-100 object-fit-cover rounded-2" alt="">
                            </template>
                            <template x-if="!currentSong || !currentSong.artwork_url">
                                <i class="bi bi-music-note text-secondary" style="font-size:.85rem;"></i>
                            </template>
                        </div>
                        <div class="overflow-hidden flex-grow-1">
                            <div class="text-truncate sp-active-text fw-semibold" style="font-size:.82rem;" x-text="currentSong ? currentSong.title : ''"></div>
                            <div class="text-truncate" style="font-size:.74rem;color:var(--sp-muted);"
                                 :style="currentSong?.artist ? 'cursor:pointer;' : ''"
                                 @click="currentSong?.artist && viewArtist(currentSong.artist)"
                                 x-text="currentSong ? (currentSong.artist ?? 'Unknown Artist') : ''"></div>
                        </div>
                        <button
                            class="sp-icon-btn flex-shrink-0"
                            :class="currentSong && isFavourite(currentSong.id) ? 'sp-icon-btn-active' : ''"
                            @click.stop="currentSong && toggleFavourite(currentSong.id)"
                            :title="currentSong && isFavourite(currentSong.id) ? 'Remove from Liked Songs' : 'Add to Liked Songs'"
                            style="font-size:.85rem;"
                        >
                            <i class="bi" :class="currentSong && isFavourite(currentSong.id) ? 'bi-heart-fill' : 'bi-heart'"></i>
                        </button>
                    </div>
                </div>

                {{-- Queue list --}}
                <div class="flex-grow-1 overflow-y-auto py-1">
                    <template x-if="queue.length > 0">
                        <div>
                            <p class="text-uppercase fw-semibold px-3 pt-2 mb-1" style="font-size:.68rem;letter-spacing:.05em;color:var(--sp-muted);">Next in Queue</p>
                            <template x-for="(song, index) in queueSongs" :key="index">
                                <div
                                    class="sp-queue-item d-flex align-items-center gap-2 px-2 py-2 rounded-2 mx-1"
                                    @dragover="queueDragOver($event, $el, index)"
                                    @drop="queueDrop($event, $el, index)"
                                    @contextmenu.prevent="openQueueContextMenu($event, song.id, index)"
                                    @mousedown="startQueueLongPress($event, song.id, index)"
                                    @mouseup="cancelLongPress()"
                                    @mouseleave="cancelLongPress()"
                                    @touchstart.passive="startQueueLongPress($event, song.id, index)"
                                    @touchend="cancelLongPress()"
                                    @touchmove="cancelLongPress()"
                                >
                                    {{-- Drag handle --}}
                                    <div class="sp-queue-drag-handle flex-shrink-0"
                                         draggable="true"
                                         @dragstart="cancelLongPress(); queueDragStart($event, index)"
                                         @dragend="queueDragEnd($el)">
                                        <i class="bi bi-grip-vertical"></i>
                                    </div>

                                    {{-- Artwork: click to play --}}
                                    <div class="sp-queue-art rounded-2 flex-shrink-0 position-relative"
                                         @click.stop="playFromQueue(index)"
                                         style="cursor:pointer;">
                                        <template x-if="song && song.artwork_url">
                                            <img :src="song.artwork_url" class="w-100 h-100 object-fit-cover rounded-2" alt="">
                                        </template>
                                        <template x-if="!song || !song.artwork_url">
                                            <i class="bi bi-music-note text-secondary" style="font-size:.8rem;"></i>
                                        </template>
                                        <div class="sp-queue-art-play">
                                            <i class="bi bi-play-fill" style="font-size:.65rem;color:#000;"></i>
                                        </div>
                                    </div>

                                    {{-- Title + clickable artist --}}
                                    <div class="overflow-hidden flex-grow-1">
                                        <div class="text-truncate fw-medium" style="font-size:.82rem;" x-text="song ? song.title : ''"></div>
                                        <div
                                            class="text-truncate"
                                            style="font-size:.74rem;color:var(--sp-muted);"
                                            :style="song?.artist ? 'cursor:pointer;' : ''"
                                            @click.stop="song?.artist && viewArtist(song.artist)"
                                            :title="song?.artist ? 'Go to ' + song.artist : ''"
                                            x-text="song ? (song.artist ?? 'Unknown Artist') : ''"
                                        ></div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </template>

                    <template x-if="queue.length === 0">
                        <div class="text-center py-4 px-3">
                            <i class="bi bi-music-note-list text-secondary" style="font-size:2rem;"></i>
                            <p class="text-secondary small mt-2 mb-1 fw-semibold">Queue is empty</p>
                            <p class="text-secondary" style="font-size:.75rem;">Use the <i class="bi bi-collection-play"></i> button on any song card.</p>
                        </div>
                    </template>
                </div>

            </div>
        </div>

    </div>

    {{-- ── BOTTOM PLAYER BAR ── --}}
    <div class="sp-player-bar d-flex align-items-center px-3 gap-3 flex-shrink-0 border-top border-secondary border-opacity-25">

        {{-- Left: Sidebar toggle + Now playing info --}}
        <div class="d-flex align-items-center gap-2 flex-grow-1" style="min-width:0;">
            <button class="sp-icon-btn" @click="leftSidebarOpen = !leftSidebarOpen; _saveState(); _syncToDb()" title="Toggle sidebar"
                    :class="leftSidebarOpen ? 'sp-icon-btn-active' : ''">
                <i class="bi bi-layout-sidebar"></i>
            </button>

            <div class="sp-bar-art rounded-2 flex-shrink-0">
                <template x-if="currentSong && currentSong.artwork_url">
                    <img :src="currentSong.artwork_url" class="w-100 h-100 object-fit-cover rounded-2" alt="">
                </template>
                <template x-if="!currentSong || !currentSong.artwork_url">
                    <div class="w-100 h-100 rounded-2 d-flex align-items-center justify-content-center" style="background:var(--sp-hover);">
                        <i class="bi bi-music-note text-secondary"></i>
                    </div>
                </template>
            </div>

            <div class="overflow-hidden" style="min-width:0;" x-show="currentSong">
                <div class="text-truncate fw-semibold" style="font-size:.85rem;" x-text="currentSong ? currentSong.title : ''"></div>
                <div class="text-truncate"
                     style="font-size:.75rem;color:var(--sp-muted);"
                     :style="currentSong?.artist ? 'cursor:pointer;' : ''"
                     @click="currentSong?.artist && viewArtist(currentSong.artist)"
                     :title="currentSong?.artist ? 'See all songs by ' + currentSong.artist : ''"
                     x-text="currentSong ? (currentSong.artist ?? 'Unknown Artist') : ''"></div>
            </div>

            <button
                class="sp-icon-btn"
                :class="currentSong && isFavourite(currentSong.id) ? 'sp-icon-btn-active' : ''"
                :style="!currentSong ? 'opacity:.3;pointer-events:none;' : ''"
                @click="currentSong && toggleFavourite(currentSong.id)"
                :title="currentSong && isFavourite(currentSong.id) ? 'Remove from Liked Songs' : 'Save to Liked Songs'"
            >
                <i class="bi" :class="currentSong && isFavourite(currentSong.id) ? 'bi-heart-fill' : 'bi-heart'"></i>
            </button>
        </div>

        {{-- Center: Controls --}}
        <div class="d-flex flex-column align-items-center gap-2 sp-player-center"
             :style="!currentSong ? 'opacity:.35;pointer-events:none;' : ''">
            <div class="d-flex align-items-center gap-3">
                <button class="sp-icon-btn" :class="shuffle ? 'sp-icon-btn-active' : ''" @click="toggleShuffle()" title="Shuffle">
                    <i class="bi bi-shuffle"></i>
                </button>
                <button class="sp-icon-btn sp-icon-btn-lg" @click="prev()" title="Previous">
                    <i class="bi bi-skip-start-fill"></i>
                </button>
                <button class="sp-play-btn" @click="togglePlay()" title="Play / Pause">
                    <i class="bi fs-5" :class="playing ? 'bi-pause-fill' : 'bi-play-fill'"></i>
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
                <span class="text-secondary" style="font-size:.7rem;width:32px;text-align:right;" x-text="currentSong ? formatTime(currentTime) : '0:00'">0:00</span>
                <input
                    type="range"
                    class="flex-grow-1 progress-bar-input"
                    min="0" max="100" step="0.1"
                    :value="currentSong ? progress : 0"
                    :style="'--progress:' + (currentSong ? progress : 0) + '%'"
                    @input="seek($event.target.value)"
                >
                <span class="text-secondary" style="font-size:.7rem;width:32px;" x-text="currentSong ? formatTime(duration) : '0:00'">0:00</span>
            </div>
        </div>

        {{-- Right: Volume + Queue toggle --}}
        <div class="d-flex align-items-center justify-content-end gap-2 flex-grow-1">
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
            <button class="sp-icon-btn" @click="rightSidebarOpen = !rightSidebarOpen; _saveState(); _syncToDb()" title="Queue"
                    :class="rightSidebarOpen ? 'sp-icon-btn-active' : ''">
                <i class="bi bi-music-note-list"></i>
            </button>
        </div>
    </div>

    {{-- ── CREATE / EDIT PLAYLIST MODAL ── --}}
    <div
        x-cloak
        x-show="showPlaylistModal"
        x-transition.opacity
        class="sp-modal-backdrop"
        @click.self="showPlaylistModal = false"
        @keydown.escape.window="showPlaylistModal = false"
    >
        <div class="sp-modal-box rounded-3 p-4 shadow-lg">
            <h3 class="fw-bold mb-4" style="font-size:1.3rem;" x-text="playlistForm.id ? 'Edit Playlist' : 'Create Playlist'"></h3>

            <div class="mb-3">
                <label class="form-label text-secondary text-uppercase fw-semibold" style="font-size:.78rem;letter-spacing:.04em;">Title</label>
                <input x-model="playlistForm.title" type="text" placeholder="My Playlist" class="sp-input" @keydown.enter="savePlaylist()">
            </div>
            <div class="mb-4">
                <label class="form-label text-secondary text-uppercase fw-semibold" style="font-size:.78rem;letter-spacing:.04em;">
                    Description <span class="text-lowercase fw-normal">(optional)</span>
                </label>
                <textarea x-model="playlistForm.description" placeholder="Add an optional description" class="sp-input" rows="3" style="resize:vertical;"></textarea>
            </div>

            <div class="d-flex justify-content-end gap-2">
                <button class="sp-modal-btn sp-modal-btn-cancel" @click="showPlaylistModal = false">Cancel</button>
                <button class="sp-modal-btn sp-modal-btn-save" @click="savePlaylist()" :disabled="!playlistForm.title.trim()">Save</button>
            </div>
        </div>
    </div>

    {{-- ── CONTEXT MENU BACKDROP ── --}}
    <div
        x-cloak
        x-show="contextMenu.show"
        class="sp-ctx-backdrop"
        @click="closeContextMenu()"
        @contextmenu.prevent="closeContextMenu()"
    ></div>

    {{-- ── CONTEXT MENU ── --}}
    <div
        x-cloak
        x-show="contextMenu.show"
        x-transition:enter="sp-ctx-enter"
        :style="`top:${contextMenu.y}px;left:${contextMenu.x}px;`"
        class="sp-context-menu"
    >
        {{-- Regular song context menu --}}
        <template x-if="!contextMenu.fromQueue">
            <div>
                <button class="sp-ctx-item" @click="play(contextMenu.songId); closeContextMenu()">
                    <i class="bi" :class="currentSongId === contextMenu.songId && playing ? 'bi-pause-fill' : 'bi-play-fill'"></i>
                    <span x-text="currentSongId === contextMenu.songId && playing ? 'Pause' : 'Play'"></span>
                </button>
                <div class="sp-ctx-divider"></div>
                <button class="sp-ctx-item" @click="toggleFavourite(contextMenu.songId); closeContextMenu()">
                    <i class="bi" :class="isFavourite(contextMenu.songId) ? 'bi-heart-fill sp-active-text' : 'bi-heart'"></i>
                    <span x-text="isFavourite(contextMenu.songId) ? 'Remove from Liked Songs' : 'Add to Liked Songs'"></span>
                </button>
                <button class="sp-ctx-item" @click="addToQueue(contextMenu.songId); closeContextMenu()">
                    <i class="bi bi-collection-play"></i>
                    <span>Add to Queue</span>
                </button>
                <button class="sp-ctx-item" @click="openAddToPlaylist(contextMenu.songId); closeContextMenu()" x-show="playlists.length > 0">
                    <i class="bi bi-plus-square"></i>
                    <span>Add to Playlist</span>
                </button>
                <button class="sp-ctx-item sp-ctx-item-playlist-info"
                        x-show="songPlaylists(contextMenu.songId).length > 0"
                        @click="openSongPlaylists(contextMenu.songId); closeContextMenu()">
                    <i class="bi bi-collection-fill" style="color:var(--sp-green);"></i>
                    <span x-text="'In ' + songPlaylists(contextMenu.songId).length + ' playlist' + (songPlaylists(contextMenu.songId).length !== 1 ? 's' : '')"></span>
                    <i class="bi bi-chevron-right ms-auto" style="font-size:.7rem;color:var(--sp-muted);"></i>
                </button>
                <div class="sp-ctx-divider"></div>
                <button class="sp-ctx-item"
                        x-show="contextMenuSong && contextMenuSong.artist"
                        @click="contextMenuSong && viewArtist(contextMenuSong.artist); closeContextMenu()">
                    <i class="bi bi-person"></i>
                    <span>Go to Artist</span>
                </button>
                <button class="sp-ctx-item"
                        x-show="contextMenuSong && contextMenuSong.album"
                        @click="contextMenuSong && viewAlbum(contextMenuSong.album); closeContextMenu()">
                    <i class="bi bi-disc"></i>
                    <span>Go to Album</span>
                </button>
                <template x-if="currentView === 'playlist'">
                    <div>
                        <div class="sp-ctx-divider"></div>
                        <button class="sp-ctx-item sp-ctx-item-danger" @click="toggleSongInPlaylist(selectedPlaylistId, contextMenu.songId); closeContextMenu()">
                            <i class="bi bi-x-circle"></i>
                            <span>Remove from Playlist</span>
                        </button>
                    </div>
                </template>
            </div>
        </template>

        {{-- Queue item context menu --}}
        <template x-if="contextMenu.fromQueue">
            <div>
                {{-- Primary action --}}
                <button class="sp-ctx-item" @click="playFromQueue(contextMenu.queueIndex); closeContextMenu()">
                    <i class="bi bi-play-circle-fill sp-active-text"></i>
                    <span>Play Now</span>
                </button>

                <div class="sp-ctx-divider"></div>

                {{-- Liked & Playlist --}}
                <button class="sp-ctx-item" @click="toggleFavourite(contextMenu.songId); closeContextMenu()">
                    <i class="bi" :class="isFavourite(contextMenu.songId) ? 'bi-heart-fill sp-active-text' : 'bi-heart'"></i>
                    <span x-text="isFavourite(contextMenu.songId) ? 'Remove from Liked Songs' : 'Add to Liked Songs'"></span>
                </button>
                <button class="sp-ctx-item" @click="openAddToPlaylist(contextMenu.songId); closeContextMenu()" x-show="playlists.length > 0">
                    <i class="bi bi-plus-square"></i>
                    <span>Add to Playlist</span>
                </button>
                <button class="sp-ctx-item sp-ctx-item-playlist-info"
                        x-show="songPlaylists(contextMenu.songId).length > 0"
                        @click="openSongPlaylists(contextMenu.songId); closeContextMenu()">
                    <i class="bi bi-collection-fill" style="color:var(--sp-green);"></i>
                    <span x-text="'In ' + songPlaylists(contextMenu.songId).length + ' playlist' + (songPlaylists(contextMenu.songId).length !== 1 ? 's' : '')"></span>
                    <i class="bi bi-chevron-right ms-auto" style="font-size:.7rem;color:var(--sp-muted);"></i>
                </button>

                <div class="sp-ctx-divider"></div>

                {{-- Reorder actions --}}
                <button class="sp-ctx-item"
                        :class="contextMenu.queueIndex === 0 ? 'sp-ctx-item-disabled' : ''"
                        @click="contextMenu.queueIndex > 0 && (moveQueueItemToTop(contextMenu.queueIndex), closeContextMenu())">
                    <i class="bi bi-chevron-double-up"></i>
                    <span>Move to Top</span>
                </button>
                <button class="sp-ctx-item"
                        :class="contextMenu.queueIndex === 0 ? 'sp-ctx-item-disabled' : ''"
                        @click="contextMenu.queueIndex > 0 && (moveQueueItem(contextMenu.queueIndex, -1), closeContextMenu())">
                    <i class="bi bi-chevron-up"></i>
                    <span>Move Up</span>
                </button>
                <button class="sp-ctx-item"
                        :class="contextMenu.queueIndex === queue.length - 1 ? 'sp-ctx-item-disabled' : ''"
                        @click="contextMenu.queueIndex < queue.length - 1 && (moveQueueItem(contextMenu.queueIndex, 1), closeContextMenu())">
                    <i class="bi bi-chevron-down"></i>
                    <span>Move Down</span>
                </button>
                <button class="sp-ctx-item"
                        :class="contextMenu.queueIndex === queue.length - 1 ? 'sp-ctx-item-disabled' : ''"
                        @click="contextMenu.queueIndex < queue.length - 1 && (moveQueueItemToBottom(contextMenu.queueIndex), closeContextMenu())">
                    <i class="bi bi-chevron-double-down"></i>
                    <span>Move to Bottom</span>
                </button>

                <div class="sp-ctx-divider"></div>

                {{-- Remove --}}
                <button class="sp-ctx-item sp-ctx-item-danger" @click="removeFromQueue(contextMenu.queueIndex); closeContextMenu()">
                    <i class="bi bi-x-circle"></i>
                    <span>Remove from Queue</span>
                </button>

                {{-- Navigation (conditional) --}}
                <template x-if="contextMenuSong && (contextMenuSong.artist || contextMenuSong.album)">
                    <div>
                        <div class="sp-ctx-divider"></div>
                        <button class="sp-ctx-item"
                                x-show="contextMenuSong && contextMenuSong.artist"
                                @click="contextMenuSong && viewArtist(contextMenuSong.artist); closeContextMenu()">
                            <i class="bi bi-person"></i>
                            <span>Go to Artist</span>
                        </button>
                        <button class="sp-ctx-item"
                                x-show="contextMenuSong && contextMenuSong.album"
                                @click="contextMenuSong && viewAlbum(contextMenuSong.album); closeContextMenu()">
                            <i class="bi bi-disc"></i>
                            <span>Go to Album</span>
                        </button>
                    </div>
                </template>
            </div>
        </template>
    </div>

    {{-- ── SONG PLAYLISTS MODAL ── --}}
    <div
        x-cloak
        x-show="showSongPlaylistsModal"
        x-transition.opacity
        class="sp-modal-backdrop"
        @click.self="showSongPlaylistsModal = false"
        @keydown.escape.window="showSongPlaylistsModal = false"
    >
        <div class="sp-modal-box rounded-3 shadow-lg overflow-hidden" style="width:400px;">
            {{-- Header --}}
            <div class="d-flex align-items-center gap-3 px-4 pt-4 pb-3 border-bottom border-secondary border-opacity-25">
                <div class="sp-spm-art rounded-2 flex-shrink-0">
                    <template x-if="songPlaylistsModalSong && songPlaylistsModalSong.artwork_url">
                        <img :src="songPlaylistsModalSong.artwork_url" class="w-100 h-100 object-fit-cover rounded-2" alt="">
                    </template>
                    <template x-if="!songPlaylistsModalSong || !songPlaylistsModalSong.artwork_url">
                        <div class="w-100 h-100 d-flex align-items-center justify-content-center">
                            <i class="bi bi-music-note text-secondary"></i>
                        </div>
                    </template>
                </div>
                <div class="overflow-hidden flex-grow-1">
                    <div class="text-truncate fw-bold" style="font-size:.95rem;" x-text="songPlaylistsModalSong ? songPlaylistsModalSong.title : ''"></div>
                    <div class="text-truncate" style="font-size:.8rem;color:var(--sp-muted);" x-text="songPlaylistsModalSong ? (songPlaylistsModalSong.artist ?? 'Unknown Artist') : ''"></div>
                </div>
                <button @click="showSongPlaylistsModal = false" class="sp-icon-btn flex-shrink-0" style="color:var(--sp-muted);font-size:1.1rem;" title="Close">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            {{-- Subtitle --}}
            <div class="px-4 pt-3 pb-1">
                <p class="text-uppercase fw-semibold mb-0" style="font-size:.72rem;letter-spacing:.05em;color:var(--sp-muted);">
                    <i class="bi bi-collection-fill me-1" style="color:var(--sp-green);"></i>
                    Saved in <span x-text="songPlaylistsModalPlaylists.length"></span> playlist<span x-text="songPlaylistsModalPlaylists.length !== 1 ? 's' : ''"></span>
                </p>
            </div>

            {{-- Playlist list --}}
            <div style="max-height:320px;" class="overflow-y-auto px-3 pb-2 pt-1">
                <template x-for="pl in songPlaylistsModalPlaylists" :key="pl.id">
                    <div class="sp-spm-row rounded-2 d-flex align-items-center gap-3 px-3 py-2 my-1">
                        <div class="d-flex align-items-center justify-content-center rounded-2 flex-shrink-0" style="width:40px;height:40px;background:#333;">
                            <i class="bi bi-music-note-list text-secondary" style="font-size:.9rem;"></i>
                        </div>
                        <div class="flex-grow-1 overflow-hidden">
                            <div class="text-truncate fw-semibold" style="font-size:.88rem;" x-text="pl.title"></div>
                            <div class="text-secondary" style="font-size:.75rem;" x-text="pl.songs.length + ' songs'"></div>
                        </div>
                        <div class="d-flex align-items-center gap-1 flex-shrink-0">
                            <button
                                class="btn btn-sm sp-spm-go-btn rounded-pill px-3"
                                title="Go to playlist"
                                @click="showSongPlaylistsModal = false; goTo('playlist', { playlistId: pl.id })"
                            >
                                <i class="bi bi-arrow-right me-1"></i>Go
                            </button>
                            <button
                                class="sp-icon-btn sp-spm-remove-btn"
                                title="Remove from this playlist"
                                @click="toggleSongInPlaylist(pl.id, songPlaylistsModalSongId)"
                            >
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </div>
                    </div>
                </template>

                <div x-show="songPlaylistsModalPlaylists.length === 0" class="text-center py-4">
                    <i class="bi bi-collection text-secondary" style="font-size:2rem;"></i>
                    <p class="text-secondary small mt-2 mb-0">Not in any playlist.</p>
                </div>
            </div>

            <div class="px-4 pb-4 pt-2">
                <button class="sp-modal-btn sp-modal-btn-save w-100" @click="showSongPlaylistsModal = false">Close</button>
            </div>
        </div>
    </div>

    {{-- ── ADD TO PLAYLIST MODAL ── --}}
    <div
        x-cloak
        x-show="showAddToPlaylistModal"
        x-transition.opacity
        class="sp-modal-backdrop"
        @click.self="showAddToPlaylistModal = false; pendingPlaylistIds = []"
        @keydown.escape.window="showAddToPlaylistModal = false; pendingPlaylistIds = []"
    >
        <div class="sp-modal-box rounded-3 p-4 shadow-lg" style="width:360px;">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h3 class="fw-bold mb-0" style="font-size:1.1rem;">Add to Playlist</h3>
                <button @click="showAddToPlaylistModal = false; pendingPlaylistIds = []" class="sp-icon-btn" style="color:var(--sp-muted);font-size:1.1rem;" title="Close">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            <div style="max-height:300px;" class="overflow-y-auto d-flex flex-column gap-1">
                <template x-for="playlist in playlists" :key="playlist.id">
                    <div
                        class="sp-playlist-select-item rounded-2 d-flex align-items-center gap-3 px-3 py-2"
                        :class="pendingPlaylistIds.includes(playlist.id) ? 'sp-playlist-select-item-active' : ''"
                        @click="togglePendingPlaylist(playlist.id)"
                    >
                        <div class="d-flex align-items-center justify-content-center rounded-2 flex-shrink-0" style="width:36px;height:36px;background:#333;">
                            <i class="bi bi-music-note-list text-secondary" style="font-size:.85rem;"></i>
                        </div>
                        <div class="flex-grow-1 overflow-hidden">
                            <div class="text-truncate fw-medium" style="font-size:.88rem;" x-text="playlist.title"></div>
                            <div x-text="playlist.songs.length + ' songs'" class="text-secondary" style="font-size:.75rem;"></div>
                        </div>
                        <i class="bi flex-shrink-0"
                           :class="pendingPlaylistIds.includes(playlist.id) ? 'bi-check-circle-fill' : 'bi-circle'"
                           :style="pendingPlaylistIds.includes(playlist.id) ? 'color:var(--sp-green)' : 'color:var(--sp-muted)'"></i>
                    </div>
                </template>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-4">
                <button class="sp-pill-btn" @click="showAddToPlaylistModal = false; openCreatePlaylist()">
                    <i class="bi bi-plus-lg"></i> New Playlist
                </button>
                <button class="sp-modal-btn sp-modal-btn-save" @click="confirmAddToPlaylist()">Done</button>
            </div>
        </div>
    </div>
</div>

<style>
    /* ── Layout ── */
    .sp-root { height: 100vh; }
    .sp-top-section { padding-bottom: 0; }

    /* ── Left sidebar ── */
    .sp-sidebar-wrapper {
        flex-shrink: 0;
        overflow: hidden;
        transition: width .3s ease, margin .3s ease, opacity .2s ease;
    }
    .sp-sidebar-open  { width: var(--sp-sidebar-w); margin-right: 8px; opacity: 1; }
    .sp-sidebar-closed { width: 0; margin-right: 0; opacity: 0; }
    .sp-sidebar-inner { width: var(--sp-sidebar-w); }

    /* ── Right queue sidebar ── */
    .sp-queue-wrapper {
        flex-shrink: 0;
        overflow: hidden;
        transition: width .3s ease, margin .3s ease, opacity .2s ease;
    }
    .sp-queue-open  { width: 270px; margin-left: 8px; opacity: 1; }
    .sp-queue-closed { width: 0; margin-left: 0; opacity: 0; }
    .sp-queue-inner { width: 270px; }

    /* ── Player bar ── */
    .sp-player-bar { height: var(--sp-player-h); background: var(--sp-card); }
    .sp-player-center { flex: 2; max-width: 720px; }

    /* ── Cards ── */
    .sp-card { background: var(--sp-card); }

    /* ── Back / Forward history buttons ── */
    .sp-nav-history { position: sticky; top: 0; z-index: 10; background: var(--sp-card); }
    .sp-hist-btn {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: rgba(0,0,0,.55);
        border: none;
        color: #fff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        font-size: .8rem;
        transition: background .15s;
        flex-shrink: 0;
    }
    .sp-hist-btn:hover:not(:disabled) { background: rgba(255,255,255,.18); }
    .sp-hist-btn:disabled { opacity: 0.35; cursor: default; }

    /* ── Nav links ── */
    .sp-nav-link {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 8px 12px;
        border-radius: 6px;
        color: var(--sp-muted);
        text-decoration: none;
        font-weight: 600;
        font-size: .9rem;
        transition: color .15s;
    }
    .sp-nav-link:hover { color: var(--sp-text); }
    .sp-nav-link.active { color: var(--sp-text); }
    .sp-nav-link i { font-size: 1.3rem; }

    /* ── Sidebar playlist items ── */
    .sp-playlist-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 6px 10px;
        cursor: pointer;
        transition: background .15s;
    }
    .sp-playlist-item:hover { background: var(--sp-hover); }
    .sp-playlist-active { background: var(--sp-hover) !important; }
    .sp-active-text { color: var(--sp-green) !important; }

    .sp-playlist-art {
        width: 38px; height: 38px;
        flex-shrink: 0;
        display: flex; align-items: center; justify-content: center;
        overflow: hidden;
    }

    /* ── Section headers ── */
    .sp-header-gradient { padding-top: 2rem; padding-bottom: 1.25rem; }
    .sp-header-green  { background: linear-gradient(180deg,#1a6535 0%,var(--sp-card) 100%); }
    .sp-header-purple { background: linear-gradient(180deg,#450af5 0%,var(--sp-card) 100%); }
    .sp-header-dark   { background: linear-gradient(180deg,#3d3d3d 0%,var(--sp-card) 100%); }

    /* ── Play all button ── */
    .sp-play-all-btn {
        width: 56px; height: 56px;
        border-radius: 50%;
        background: var(--sp-green);
        border: none; color: #000; font-size: 1.5rem;
        display: inline-flex; align-items: center; justify-content: center;
        box-shadow: 0 8px 24px rgba(0,0,0,.5);
        cursor: pointer;
        transition: background .15s, transform .15s, box-shadow .15s;
        flex-shrink: 0;
    }
    .sp-play-all-btn:hover {
        background: var(--sp-green-hover);
        transform: scale(1.06);
        box-shadow: 0 12px 32px rgba(0,0,0,.6);
    }
    .sp-play-all-btn:active { transform: scale(.97); }
    .sp-play-all-btn-sm { width: 38px; height: 38px; font-size: 1rem; }

    /* ── Song grid ── */
    .sp-song-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
        gap: 16px;
        padding-bottom: 24px;
    }

    .sp-song-card {
        background: var(--sp-hover);
        padding: 12px;
        cursor: pointer;
        transition: background .2s;
        position: relative;
    }
    .sp-song-card:hover { background: #333; }
    .sp-song-card-active { background: #333 !important; }

    .sp-song-card-art {
        position: relative;
        width: 100%; padding-top: 100%;
        overflow: hidden;
        background: #333;
        box-shadow: 0 8px 24px rgba(0,0,0,.5);
    }
    .sp-song-card-art > img,
    .sp-song-card-art-placeholder {
        position: absolute; inset: 0;
        width: 100%; height: 100%;
        display: flex; align-items: center; justify-content: center;
        background: var(--sp-hover);
    }

    /* ── Song card overlay buttons ── */
    .sp-song-card-play-btn {
        position: absolute; bottom: 8px; right: 8px;
        width: 40px; height: 40px;
        border-radius: 50%;
        background: var(--sp-green);
        border: none; color: #000; font-size: 1rem;
        display: flex; align-items: center; justify-content: center;
        opacity: 0; transform: translateY(8px);
        transition: opacity .2s, transform .2s;
        box-shadow: 0 8px 16px rgba(0,0,0,.4);
        cursor: pointer; z-index: 2;
    }
    .sp-song-card:hover .sp-song-card-play-btn,
    .sp-song-card-active .sp-song-card-play-btn { opacity: 1; transform: translateY(0); }
    .sp-song-card-play-btn:hover { background: var(--sp-green-hover); transform: scale(1.04) translateY(0) !important; }

    /* ── Song card right-side action buttons ── */
    .sp-song-actions {
        position: absolute; top: 6px; right: 6px;
        display: flex; flex-direction: column; gap: 4px;
        opacity: 0; transition: opacity .2s; z-index: 3;
    }
    .sp-song-card:hover .sp-song-actions { opacity: 1; }
    .sp-song-action-btn {
        width: 28px; height: 28px; border-radius: 50%;
        background: rgba(0,0,0,.55); border: none; color: var(--sp-muted); font-size: .8rem;
        display: flex; align-items: center; justify-content: center;
        cursor: pointer; transition: color .15s, background .15s;
    }
    .sp-song-action-btn:hover { color: #fff; background: rgba(0,0,0,.75); }
    .sp-song-action-btn-active { color: var(--sp-green) !important; }

    /* ── Song card selected (not playing) ── */
    .sp-song-card-selected { background: rgba(255,255,255,.06) !important; }

    /* ── List row selected (not playing) ── */
    .sp-list-row-selected { background: rgba(255,255,255,.04) !important; }

    /* ── Context menu backdrop ── */
    .sp-ctx-backdrop {
        position: fixed; inset: 0; z-index: 9998;
    }

    /* ── Context menu ── */
    .sp-context-menu {
        position: fixed; z-index: 9999;
        background: #282828;
        border: 1px solid rgba(255,255,255,.12);
        border-radius: 8px;
        padding: 4px;
        min-width: 220px;
        box-shadow: 0 8px 32px rgba(0,0,0,.7);
        user-select: none;
    }
    .sp-ctx-enter { animation: sp-ctx-pop .12s ease; }
    @keyframes sp-ctx-pop {
        from { opacity: 0; transform: scale(.94); }
        to   { opacity: 1; transform: scale(1); }
    }
    .sp-ctx-item {
        display: flex; align-items: center; gap: 10px;
        width: 100%; padding: 8px 12px;
        background: none; border: none; border-radius: 4px;
        color: var(--sp-text); font-size: .88rem; text-align: left;
        cursor: pointer; transition: background .1s;
    }
    .sp-ctx-item:hover { background: var(--sp-hover); }
    .sp-ctx-item i { width: 16px; text-align: center; font-size: .9rem; flex-shrink: 0; }
    .sp-ctx-item-danger { color: #e55; }
    .sp-ctx-item-danger:hover { background: rgba(220,50,50,.12); }
    .sp-ctx-item-disabled { opacity: .35; cursor: not-allowed !important; }
    .sp-ctx-item-disabled:hover { background: none !important; }
    .sp-ctx-divider { height: 1px; background: rgba(255,255,255,.1); margin: 4px 0; }

    /* ── Queue items ── */
    .sp-queue-art {
        width: 38px; height: 38px;
        flex-shrink: 0;
        background: var(--sp-hover);
        display: flex; align-items: center; justify-content: center;
        overflow: hidden;
        transition: opacity .15s;
    }
    .sp-queue-art:hover { opacity: .85; }
    .sp-queue-art-play {
        position: absolute; inset: 0;
        background: rgba(0,0,0,.5);
        border-radius: inherit;
        display: flex; align-items: center; justify-content: center;
        opacity: 0; transition: opacity .15s;
    }
    .sp-queue-art:hover .sp-queue-art-play { opacity: 1; }

    .sp-queue-item {
        transition: background .15s;
        user-select: none;
    }
    .sp-queue-item:hover { background: var(--sp-hover); }

    .sp-queue-drag-handle {
        color: var(--sp-muted);
        font-size: .85rem;
        opacity: 0;
        transition: opacity .15s;
        cursor: grab;
        padding: 2px;
    }
    .sp-queue-item:hover .sp-queue-drag-handle { opacity: 1; }
    .sp-queue-drag-over {
        background: rgba(29,185,84,.08) !important;
        outline: 1px dashed rgba(29,185,84,.35);
    }

    /* ── Bottom bar art ── */
    .sp-bar-art { width: 52px; height: 52px; flex-shrink: 0; }

    /* ── Icon buttons ── */
    .sp-icon-btn {
        background: none; border: none; color: var(--sp-muted); font-size: 1rem;
        padding: 4px; cursor: pointer;
        transition: color .15s, transform .1s;
        display: flex; align-items: center;
    }
    .sp-icon-btn:hover { color: var(--sp-text); transform: scale(1.08); }
    .sp-icon-btn-active { color: var(--sp-green) !important; }
    .sp-icon-btn-lg { font-size: 1.3rem; }

    /* ── Play button ── */
    .sp-play-btn {
        width: 36px; height: 36px; border-radius: 50%;
        background: var(--sp-text); border: none; color: #000;
        display: flex; align-items: center; justify-content: center;
        cursor: pointer; transition: transform .1s, background .15s;
    }
    .sp-play-btn:hover { transform: scale(1.06); background: #e0e0e0; }

    /* ── Pill buttons ── */
    .sp-pill-btn {
        background: rgba(255,255,255,.1); border: none; color: var(--sp-text);
        border-radius: 20px; padding: 6px 14px; font-size: .8rem; font-weight: 600;
        cursor: pointer; transition: background .15s;
        display: inline-flex; align-items: center; gap: 6px;
    }
    .sp-pill-btn:hover { background: rgba(255,255,255,.2); }
    .sp-pill-btn-danger { color: #e55; }
    .sp-pill-btn-danger:hover { background: rgba(220,50,50,.15); }

    [x-cloak] { display: none !important; }

    /* ── Modal ── */
    .sp-modal-backdrop {
        position: fixed; inset: 0; z-index: 1000;
        background: rgba(0,0,0,.7);
        display: flex; align-items: center; justify-content: center;
    }
    .sp-modal-box { background: #282828; width: 420px; max-width: 90vw; }

    .sp-input {
        width: 100%; background: #3e3e3e; border: 1px solid #555; border-radius: 6px;
        color: var(--sp-text); padding: 10px 12px; font-size: .9rem; outline: none;
        transition: border-color .15s;
    }
    .sp-input:focus { border-color: var(--sp-green); }
    .sp-input::placeholder { color: var(--sp-muted); }

    .sp-modal-btn {
        border: none; border-radius: 20px; padding: 8px 20px;
        font-size: .88rem; font-weight: 700; cursor: pointer;
        transition: background .15s, transform .1s;
    }
    .sp-modal-btn:hover { transform: scale(1.02); }
    .sp-modal-btn-cancel { background: transparent; color: var(--sp-muted); }
    .sp-modal-btn-cancel:hover { color: var(--sp-text); }
    .sp-modal-btn-save { background: var(--sp-text); color: #000; }
    .sp-modal-btn-save:hover { background: #e0e0e0; }
    .sp-modal-btn-save:disabled { opacity: .4; cursor: not-allowed; transform: none; }

    /* ── Add to playlist select items ── */
    .sp-playlist-select-item { cursor: pointer; transition: background .15s; }
    .sp-playlist-select-item:hover { background: var(--sp-hover); }
    .sp-playlist-select-item-active { background: rgba(29,185,84,.1); }

    /* ── Song Playlists modal ── */
    .sp-spm-art {
        width: 48px; height: 48px;
        background: var(--sp-hover);
        overflow: hidden;
        display: flex; align-items: center; justify-content: center;
    }
    .sp-spm-row {
        cursor: default;
        transition: background .15s;
    }
    .sp-spm-row:hover { background: var(--sp-hover); }
    .sp-spm-go-btn {
        background: rgba(255,255,255,.1);
        border: none;
        color: var(--sp-text);
        font-size: .8rem;
        font-weight: 600;
        transition: background .15s;
    }
    .sp-spm-go-btn:hover { background: rgba(255,255,255,.2); color: var(--sp-text); }
    .sp-spm-remove-btn {
        color: #e55;
        font-size: .85rem;
    }
    .sp-spm-remove-btn:hover { color: #ff6666; }

    /* ── Context menu playlist-info item ── */
    .sp-ctx-item-playlist-info { color: var(--sp-text); }
    .sp-ctx-item-playlist-info:hover { background: rgba(29,185,84,.1); }

    /* ── View toggle ── */
    .sp-view-toggle {
        display: flex;
        align-items: center;
        background: var(--sp-hover);
        border-radius: 6px;
        padding: 2px;
        gap: 2px;
    }
    .sp-view-btn {
        background: none; border: none; color: var(--sp-muted);
        width: 28px; height: 28px; border-radius: 4px;
        display: flex; align-items: center; justify-content: center;
        font-size: .85rem; cursor: pointer; transition: color .15s, background .15s;
    }
    .sp-view-btn:hover { color: var(--sp-text); }
    .sp-view-btn-active { background: rgba(255,255,255,.12); color: var(--sp-text) !important; }

    /* ── List view ── */
    .sp-list-view { padding-bottom: 24px; }

    .sp-list-header { user-select: none; }
    .sp-list-col-num  { width: 40px; flex-shrink: 0; text-align: center; }
    .sp-list-col-title { flex: 1 1 0; min-width: 0; }
    .sp-list-col-album { flex: 0 0 28%; min-width: 0; }
    .sp-list-col-actions { width: 120px; flex-shrink: 0; }
    .sp-list-col-dur  { width: 52px; flex-shrink: 0; }

    .sp-list-row {
        height: 56px;
        cursor: pointer;
        transition: background .15s;
    }
    .sp-list-row:hover { background: var(--sp-hover); }
    .sp-list-row-active { background: rgba(29,185,84,.08) !important; }

    .sp-list-art {
        width: 40px; height: 40px; flex-shrink: 0;
        background: var(--sp-hover); overflow: hidden;
    }

    /* number / play indicator */
    .sp-list-num { display: block; line-height: 1; }
    .sp-list-playing-icon { display: block; position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; }
    .sp-list-play-btn {
        position: absolute; inset: 0;
        background: none; border: none; color: var(--sp-text);
        font-size: .95rem;
        display: flex; align-items: center; justify-content: center;
        opacity: 0; transition: opacity .15s; cursor: pointer;
    }
    .sp-list-col-num { position: relative; }
    .sp-list-row:hover .sp-list-play-btn { opacity: 1; }
    .sp-list-row:hover .sp-list-num,
    .sp-list-row:hover .sp-list-playing-icon { opacity: 0; }

    /* action buttons hidden until row hovered */
    .sp-list-actions .sp-list-action-btn { opacity: 0; transition: opacity .15s; }
    .sp-list-row:hover .sp-list-actions .sp-list-action-btn { opacity: 1; }
    .sp-list-actions .sp-icon-btn-active { opacity: 1 !important; }

    /* ── Search header gradient ── */
    .sp-header-teal { background: linear-gradient(180deg,#0d6e6e 0%,var(--sp-card) 100%); }

    /* ── Search filter bar ── */
    .sp-filter-input,
    .sp-filter-select {
        background: #2a2a2a;
        border: 1px solid rgba(255,255,255,.15);
        color: var(--sp-text);
        font-size: .88rem;
        transition: border-color .15s, box-shadow .15s;
    }
    .sp-filter-input:focus,
    .sp-filter-select:focus {
        background: #2a2a2a;
        border-color: var(--sp-green);
        box-shadow: 0 0 0 2px rgba(29,185,84,.25);
        color: var(--sp-text);
        outline: none;
    }
    .sp-filter-input::placeholder { color: var(--sp-muted); }
    .sp-filter-select option { background: #2a2a2a; color: var(--sp-text); }

    .sp-filter-addon {
        background: #2a2a2a;
        border: 1px solid rgba(255,255,255,.15);
        border-right: none;
        color: var(--sp-muted);
        font-size: .9rem;
    }
    .sp-filter-clear-btn {
        background: #2a2a2a;
        border: 1px solid rgba(255,255,255,.15);
        border-left: none;
        color: var(--sp-muted);
        cursor: pointer;
        transition: color .15s;
    }
    .sp-filter-clear-btn:hover { color: var(--sp-text); }

    /* Keep Bootstrap select arrow visible on dark bg */
    .sp-filter-select {
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23adb5bd' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2 5l6 6 6-6'/%3e%3c/svg%3e");
        background-repeat: no-repeat;
        background-position: right .75rem center;
        background-size: 16px 12px;
        padding-right: 2.5rem;
        -webkit-appearance: none;
        -moz-appearance: none;
        appearance: none;
    }
</style>
