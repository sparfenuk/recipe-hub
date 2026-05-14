<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Recipe;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $recipes = Recipe::query()
            ->where('status', 'published')
            ->select(['slug', 'updated_at'])
            ->orderByDesc('updated_at')
            ->get();

        $content = view('sitemap', [
            'recipes' => $recipes,
        ])->render();

        return response($content)
            ->header('Content-Type', 'application/xml');
    }
}
