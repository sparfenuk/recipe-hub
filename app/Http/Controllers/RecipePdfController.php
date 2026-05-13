<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Recipe;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class RecipePdfController extends Controller
{
    public function __invoke(string $slug): Response
    {
        $recipe = Recipe::query()
            ->where('slug', $slug)
            ->where('status', 'published')
            ->with([
                'author',
                'category',
                'cuisine',
                'tags',
                'recipeIngredients.ingredient',
                'recipeIngredients.unit',
                'steps',
            ])
            ->firstOrFail();

        $pdf = Pdf::loadView('pdf.recipe', ['recipe' => $recipe]);

        $pdf->setPaper('a4', 'portrait');

        return $pdf->download($recipe->slug.'.pdf');
    }
}
