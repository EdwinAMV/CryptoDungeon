<?php

namespace App\View\Components;

use Illuminate\Support\Facades\File;
use Illuminate\View\Component;
use Illuminate\View\View;

class BackgroundMusic extends Component
{
    public array $tracks;

    public function __construct()
    {
        $directory = public_path('audio');
        $this->tracks = is_dir($directory)
            ? collect(File::files($directory))
                ->filter(fn ($file) => in_array(strtolower($file->getExtension()), ['mp3', 'm4a', 'ogg', 'wav', 'webm', 'aac'], true))
                ->sortBy(fn ($file) => $file->getFilename())
                ->map(fn ($file) => asset('audio/'.rawurlencode($file->getFilename())))
                ->values()->all()
            : [];
    }

    public function render(): View
    {
        return view('components.background-music');
    }
}
