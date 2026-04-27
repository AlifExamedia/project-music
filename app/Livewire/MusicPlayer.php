<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Song;
use App\Models\Favourite;
use App\Models\Playlist;
use App\Models\PlaylistSong;
use App\Models\PlayerSession;
use App\Models\QueueItem;

class MusicPlayer extends Component
{
    public array $songs = [];
    public array $favourites = [];
    public array $playlists = [];
    public array $session = [];

    public function mount(): void
    {
        $user = auth()->user();

        $this->songs = Song::all()
            ->map(fn($song) => [
                'id'          => $song->id,
                'filename'    => $song->filename,
                'title'       => $song->title,
                'artist'      => $song->artist,
                'album'       => $song->album,
                'genre'       => $song->genre,
                'year'        => $song->year,
                'track'       => $song->track,
                'url'         => route('audio.stream', ['filename' => $song->filename]),
                'artwork_url' => $song->cover_art_path
                                    ? route('audio.cover', ['filename' => $song->cover_art_path])
                                    : null,
                'duration'    => $song->duration,
            ])
            ->values()
            ->toArray();

        $this->favourites = Favourite::where('user_id', $user->id)
            ->pluck('song_id')
            ->toArray();

        $this->playlists = Playlist::where('user_id', $user->id)
            ->with('songs')
            ->get()
            ->map(fn($pl) => [
                'id'          => $pl->id,
                'title'       => $pl->title,
                'description' => $pl->description ?? '',
                'songs'       => $pl->songs->pluck('song_id')->toArray(),
            ])
            ->toArray();

        $dbSession = PlayerSession::where('user_id', $user->id)->first();
        $queue     = QueueItem::where('user_id', $user->id)
            ->orderBy('position')
            ->pluck('song_id')
            ->toArray();

        if ($dbSession) {
            $this->session = [
                'current_song_id'    => $dbSession->current_song_id,
                'current_time'       => $dbSession->current_time,
                'volume'             => $dbSession->volume,
                'muted'              => $dbSession->muted,
                'shuffle'            => $dbSession->shuffle,
                'loop'               => $dbSession->loop,
                'left_sidebar_open'  => $dbSession->left_sidebar_open,
                'right_sidebar_open' => $dbSession->right_sidebar_open,
                'view_mode'          => $dbSession->view_mode ?? 'grid',
                'queue'              => $queue,
            ];
        }
    }

    public function toggleFavourite(int $songId): void
    {
        $user     = auth()->user();
        $existing = Favourite::where('user_id', $user->id)->where('song_id', $songId)->first();

        if ($existing) {
            $existing->delete();
        } else {
            Favourite::create(['user_id' => $user->id, 'song_id' => $songId]);
        }
    }

    public function createPlaylist(string $title, string $description = ''): array
    {
        $playlist = Playlist::create([
            'user_id'     => auth()->id(),
            'title'       => trim($title),
            'description' => trim($description),
        ]);

        return [
            'id'          => $playlist->id,
            'title'       => $playlist->title,
            'description' => $playlist->description ?? '',
            'songs'       => [],
        ];
    }

    public function updatePlaylist(int $id, string $title, string $description): void
    {
        Playlist::where('id', $id)
            ->where('user_id', auth()->id())
            ->update([
                'title'       => trim($title),
                'description' => trim($description),
            ]);
    }

    public function deletePlaylist(int $id): void
    {
        Playlist::where('id', $id)
            ->where('user_id', auth()->id())
            ->delete();
    }

    public function addToPlaylist(int $playlistId, int $songId): void
    {
        Playlist::where('id', $playlistId)->where('user_id', auth()->id())->firstOrFail();

        $maxPos = PlaylistSong::where('playlist_id', $playlistId)->max('position') ?? 0;

        PlaylistSong::firstOrCreate(
            ['playlist_id' => $playlistId, 'song_id' => $songId],
            ['position' => $maxPos + 1]
        );
    }

    public function removeFromPlaylist(int $playlistId, int $songId): void
    {
        Playlist::where('id', $playlistId)->where('user_id', auth()->id())->firstOrFail();

        PlaylistSong::where('playlist_id', $playlistId)
            ->where('song_id', $songId)
            ->delete();
    }

    public function saveSession(
        ?int $currentSongId,
        float $currentTime,
        float $volume,
        bool $muted,
        bool $shuffle,
        string $loop,
        bool $leftSidebarOpen,
        bool $rightSidebarOpen,
        array $queue,
        string $viewMode = 'grid'
    ): void {
        $userId = auth()->id();

        PlayerSession::updateOrCreate(
            ['user_id' => $userId],
            [
                'current_song_id'    => $currentSongId,
                'current_time'       => $currentTime,
                'volume'             => $volume,
                'muted'              => $muted,
                'shuffle'            => $shuffle,
                'loop'               => $loop,
                'left_sidebar_open'  => $leftSidebarOpen,
                'right_sidebar_open' => $rightSidebarOpen,
                'view_mode'          => $viewMode,
            ]
        );

        QueueItem::where('user_id', $userId)->delete();

        if (! empty($queue)) {
            $rows = array_map(fn($songId, $position) => [
                'user_id'    => $userId,
                'position'   => $position,
                'song_id'    => $songId,
                'created_at' => now(),
                'updated_at' => now(),
            ], $queue, array_keys($queue));

            QueueItem::insert($rows);
        }
    }

    public function render()
    {
        return view('livewire.music-player')
            ->layout('layouts.player');
    }
}
