<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;

class ProjectorManualController extends Controller
{
    public function index(): View
    {
        $root = (string) config('peoplecine.article_pdf_root', public_path('legacy-article-pdf'));

        $manuals = collect();

        if (is_dir($root)) {
            $manuals = collect(File::files($root))
                ->filter(static fn (\SplFileInfo $file): bool => strtolower($file->getExtension()) === 'pdf')
                ->sortBy(static fn (\SplFileInfo $file): string => strtolower($file->getFilename()))
                ->values()
                ->map(static fn (\SplFileInfo $file): array => [
                    'name' => $file->getFilename(),
                    'url' => route('legacy-article-pdf.show', ['filename' => $file->getFilename()]),
                    'size_bytes' => $file->getSize(),
                ]);
        }

        return view('manuals.index', [
            'manuals' => $manuals,
        ]);
    }
}
