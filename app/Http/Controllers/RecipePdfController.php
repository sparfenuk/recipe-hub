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

        $pdf = Pdf::loadView('pdf.recipe', [
            'recipe' => $recipe,
            'heroDataUri' => $this->renderHeroDataUri($recipe),
        ]);

        $pdf->setPaper('a4', 'portrait');

        return $pdf->download($recipe->slug.'.pdf');
    }

    private function renderHeroDataUri(Recipe $recipe, int $targetW = 700, int $targetH = 500): ?string
    {
        $media = $recipe->getFirstMedia('hero');
        if (! $media) {
            return null;
        }

        $sourcePath = null;
        foreach (['card', 'full', ''] as $conversion) {
            $candidate = $conversion ? $media->getPath($conversion) : $media->getPath();
            if ($candidate && is_file($candidate)) {
                $sourcePath = $candidate;
                break;
            }
        }
        if ($sourcePath === null || ! extension_loaded('gd')) {
            return null;
        }

        $info = @getimagesize($sourcePath);
        if (! $info) {
            return null;
        }
        [$srcW, $srcH] = $info;

        $src = match ($info[2]) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($sourcePath),
            IMAGETYPE_PNG => @imagecreatefrompng($sourcePath),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($sourcePath) : false,
            default => false,
        };
        if (! $src) {
            return null;
        }

        $srcRatio = $srcW / $srcH;
        $targetRatio = $targetW / $targetH;
        if ($srcRatio > $targetRatio) {
            $cropH = $srcH;
            $cropW = (int) round($srcH * $targetRatio);
            $cropX = (int) round(($srcW - $cropW) / 2);
            $cropY = 0;
        } else {
            $cropW = $srcW;
            $cropH = (int) round($srcW / $targetRatio);
            $cropX = 0;
            $cropY = (int) round(($srcH - $cropH) / 2);
        }

        $dst = imagecreatetruecolor($targetW, $targetH);
        imagecopyresampled($dst, $src, 0, 0, $cropX, $cropY, $targetW, $targetH, $cropW, $cropH);

        ob_start();
        imagejpeg($dst, null, 82);
        $jpeg = (string) ob_get_clean();

        imagedestroy($src);
        imagedestroy($dst);

        return 'data:image/jpeg;base64,'.base64_encode($jpeg);
    }
}
