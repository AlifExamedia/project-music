<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;
use App\Models\Song;
use App\Models\Artist;
use App\Models\Album;
use App\Models\Genre;

class UploadMusic extends Component
{
    use WithFileUploads;

    public $mp3File = null;
    public $coverFile = null;

    public string $title = '';
    public string $artist = '';
    public string $album = '';
    public string $genre = '';
    public string $year = '';
    public string $track = '';
    public ?string $duration = null;
    public bool $hasCoverArt = false;

    public ?string $successMessage = null;
    public ?string $errorMessage = null;

    public array $existingSongs = [];
    public int $totalFiles = 0;

    public function mount(): void
    {
        $this->loadExistingSongs();
    }

    public function loadExistingSongs(): void
    {
        $this->existingSongs = Song::with('artist')
            ->orderBy('title')
            ->get()
            ->map(fn($s) => [
                'id'            => $s->id,
                'filename'      => $s->filename,
                'title'         => $s->title,
                'artist'        => $s->artist?->name,
                'cover_art_path' => $s->cover_art_path,
            ])
            ->toArray();

        $this->totalFiles = count(array_filter(
            Storage::disk('music')->files(),
            fn($f) => str_ends_with($f, '.mp3')
        ));
    }

    public function updatedMp3File(): void
    {
        $this->successMessage = null;
        $this->errorMessage = null;

        if (! $this->mp3File) return;

        $path = $this->mp3File->getRealPath();
        $id3  = new \getID3();
        $tags = $id3->analyze($path);
        \getid3_lib::CopyTagsToComments($tags);

        $comments = $tags['comments'] ?? [];
        $filename = $this->mp3File->getClientOriginalName();

        $this->title    = $comments['title'][0]        ?? pathinfo($filename, PATHINFO_FILENAME);
        $this->artist   = $comments['artist'][0]       ?? '';
        $this->album    = $comments['album'][0]        ?? '';
        $this->genre    = $comments['genre'][0]        ?? '';
        $this->year     = (string) ($comments['year'][0] ?? '');
        $this->track    = (string) ($comments['track_number'][0] ?? '');
        $this->duration = isset($tags['playtime_seconds'])
            ? gmdate('i:s', (int) $tags['playtime_seconds'])
            : null;
        $this->hasCoverArt = isset($tags['id3v2']['APIC'][0]['data']);
    }

    public function upload(): void
    {
        $this->validate([
            'mp3File'   => 'required|file|mimes:mp3,mpga|max:102400',
            'coverFile' => 'nullable|file|image|max:5120',
            'title'     => 'required|string|max:255',
            'artist'    => 'nullable|string|max:255',
            'album'     => 'nullable|string|max:255',
            'genre'     => 'nullable|string|max:255',
            'year'      => 'nullable|string|max:10',
            'track'     => 'nullable|string|max:20',
        ]);

        $filename = $this->mp3File->getClientOriginalName();
        $filename = preg_replace('/[\/\\\\]/', '_', $filename);

        if (Song::where('filename', $filename)->exists()) {
            $this->errorMessage = "A song with filename \"{$filename}\" already exists.";
            return;
        }

        $this->mp3File->storeAs('', $filename, 'music');

        $coverPath = $this->extractOrStoreCover($filename);

        [$artistId, $albumId, $genreId] = $this->resolveRelations(
            trim($this->artist),
            trim($this->album),
            trim($this->genre),
            trim($this->year),
            $coverPath
        );

        Song::create([
            'filename'       => $filename,
            'title'          => trim($this->title),
            'artist_id'      => $artistId,
            'album_id'       => $albumId,
            'genre_id'       => $genreId,
            'year'           => trim($this->year) ?: null,
            'track'          => trim($this->track) ?: null,
            'duration'       => $this->duration,
            'cover_art_path' => $coverPath,
        ]);

        $this->reset(['mp3File', 'coverFile', 'title', 'artist', 'album', 'genre', 'year', 'track', 'duration', 'hasCoverArt']);
        $this->successMessage = "\"{$filename}\" uploaded successfully.";
        $this->loadExistingSongs();
    }

    public function scanExisting(): void
    {
        $id3      = new \getID3();
        $existing = Song::pluck('filename')->toArray();
        $files    = array_filter(
            Storage::disk('music')->files(),
            fn($f) => str_ends_with($f, '.mp3')
        );

        $imported = 0;

        foreach ($files as $file) {
            $filename = basename($file);
            if (in_array($filename, $existing)) continue;

            $path = storage_path('app/music/' . $filename);
            $tags = $id3->analyze($path);
            \getid3_lib::CopyTagsToComments($tags);
            $comments = $tags['comments'] ?? [];

            $coverPath = null;
            $artData   = $tags['id3v2']['APIC'][0]['data'] ?? null;
            if ($artData) {
                $coverFilename = pathinfo($filename, PATHINFO_FILENAME) . '.jpg';
                Storage::disk('music')->put('cover/' . $coverFilename, $artData);
                $coverPath = $coverFilename;
            }

            $artistName = $comments['artist'][0] ?? null;
            $albumName  = $comments['album'][0]  ?? null;
            $genreName  = $comments['genre'][0]  ?? null;
            $year       = $comments['year'][0]   ?? null;

            [$artistId, $albumId, $genreId] = $this->resolveRelations(
                $artistName ?? '',
                $albumName  ?? '',
                $genreName  ?? '',
                $year       ?? '',
                $coverPath
            );

            Song::create([
                'filename'       => $filename,
                'title'          => $comments['title'][0] ?? pathinfo($filename, PATHINFO_FILENAME),
                'artist_id'      => $artistId,
                'album_id'       => $albumId,
                'genre_id'       => $genreId,
                'year'           => $year,
                'track'          => $comments['track_number'][0] ?? null,
                'duration'       => isset($tags['playtime_seconds'])
                                        ? gmdate('i:s', (int) $tags['playtime_seconds'])
                                        : null,
                'cover_art_path' => $coverPath,
            ]);

            $imported++;
        }

        $this->successMessage = $imported > 0
            ? "Imported {$imported} song(s) from the music directory."
            : 'All files are already in the library.';

        $this->loadExistingSongs();
    }

    public function deleteSong(int $id): void
    {
        $song = Song::findOrFail($id);

        if ($song->cover_art_path && Storage::disk('music')->exists('cover/' . $song->cover_art_path)) {
            Storage::disk('music')->delete('cover/' . $song->cover_art_path);
        }

        $song->delete();

        $this->successMessage = 'Song removed from library.';
        $this->loadExistingSongs();
    }

    private function resolveRelations(
        string $artistName,
        string $albumName,
        string $genreName,
        string $year,
        ?string $coverPath
    ): array {
        $artistId = null;
        if ($artistName !== '') {
            $artist   = Artist::firstOrCreate(['name' => $artistName]);
            $artistId = $artist->id;
        }

        $genreId = null;
        if ($genreName !== '') {
            $genre   = Genre::firstOrCreate(['name' => $genreName]);
            $genreId = $genre->id;
        }

        $albumId = null;
        if ($albumName !== '') {
            $album = Album::firstOrCreate(
                ['name' => $albumName, 'artist_id' => $artistId],
                [
                    'year'          => $year ?: null,
                    'cover_art_path' => $coverPath,
                ]
            );
            $albumId = $album->id;
        }

        return [$artistId, $albumId, $genreId];
    }

    private function extractOrStoreCover(string $filename): ?string
    {
        if ($this->coverFile) {
            $ext           = $this->coverFile->getClientOriginalExtension() ?: 'jpg';
            $coverFilename = pathinfo($filename, PATHINFO_FILENAME) . '.' . $ext;
            $this->coverFile->storeAs('cover', $coverFilename, 'music');
            return $coverFilename;
        }

        $mp3Path = storage_path('app/music/' . $filename);
        $id3     = new \getID3();
        $tags    = $id3->analyze($mp3Path);
        $artData = $tags['id3v2']['APIC'][0]['data'] ?? null;

        if ($artData) {
            $coverFilename = pathinfo($filename, PATHINFO_FILENAME) . '.jpg';
            Storage::disk('music')->put('cover/' . $coverFilename, $artData);
            return $coverFilename;
        }

        return null;
    }

    public function render()
    {
        return view('livewire.upload-music')
            ->layout('layouts.app', ['title' => 'Upload Music']);
    }
}
