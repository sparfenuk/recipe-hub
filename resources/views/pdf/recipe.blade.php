<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $recipe->title }}</title>
    <style>
        @page {
            margin-top: 18mm;
            margin-right: 18mm;
            margin-bottom: 18mm;
            margin-left: 28mm;
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 9.5pt;
            line-height: 1.4;
            color: #1e293b;
        }
        ul {
            padding-left: 6px;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 2px solid #059669;
            padding-bottom: 0;
            margin-bottom: 14px;
        }
        .header-table td {
            vertical-align: top;
            padding-bottom: 10px;
        }
        .hero-cell {
            width: 38%;
            padding-right: 14px;
        }
        .hero {
            width: 100%;
            height: auto;
            display: block;
        }
        h1 {
            font-size: 18pt;
            color: #0f172a;
            margin-bottom: 4px;
            line-height: 1.15;
        }
        .summary {
            font-size: 9.5pt;
            color: #475569;
            margin-top: 3px;
        }
        .meta {
            margin-top: 8px;
            font-size: 8.5pt;
            color: #475569;
            line-height: 1.6;
        }
        .meta-sep {
            color: #cbd5e1;
            margin: 0 5px;
        }
        .meta-label {
            color: #94a3b8;
        }
        .tags {
            margin-top: 8px;
        }
        .tag {
            display: inline-block;
            font-size: 7.5pt;
            color: #047857;
            background: #ecfdf5;
            padding: 1px 7px;
            border-radius: 8px;
            margin-right: 3px;
        }
        h2 {
            font-size: 11pt;
            color: #0f172a;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 4px;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .body-grid {
            width: 100%;
            border-collapse: collapse;
        }
        .body-grid > tbody > tr > td {
            vertical-align: top;
        }
        .col-ingredients {
            width: 38%;
            padding-left: 10px;
            padding-right: 14px;
        }
        .col-instructions {
            width: 62%;
            padding-left: 14px;
            border-left: 1px solid #e2e8f0;
        }
        .group-label {
            font-size: 8.5pt;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #64748b;
            font-weight: 600;
            margin-top: 8px;
            margin-bottom: 3px;
        }
        .ingredient-list {
            list-style: none;
            width: 100%;
        }
        .ingredient-list li {
            padding: 3px 0;
            border-bottom: 1px solid #f1f5f9;
            font-size: 9.5pt;
        }
        .ingredient-list li:last-child {
            border-bottom: none;
        }
        .ingredient-amount {
            color: #0f172a;
            font-weight: 600;
        }
        .note {
            font-size: 8.5pt;
            color: #64748b;
        }
        .steps {
            width: 100%;
            border-collapse: collapse;
        }
        .steps td {
            vertical-align: top;
            padding-bottom: 8px;
        }
        .steps tr {
            page-break-inside: avoid;
        }
        .step-number-cell {
            width: 26px;
        }
        .step-number {
            display: block;
            width: 20px;
            height: 20px;
            line-height: 20px;
            text-align: center;
            background: #ecfdf5;
            color: #047857;
            font-weight: 700;
            font-size: 9pt;
            border-radius: 10px;
        }
        .step-body {
            font-size: 9.5pt;
            color: #1e293b;
        }
        .nutrition-strip {
            margin-top: 14px;
            padding: 8px 12px;
            background: #f8fafc;
            border-radius: 4px;
            font-size: 9pt;
            color: #475569;
        }
        .nutrition-strip strong {
            color: #0f172a;
        }
        .nutrition-sep {
            color: #cbd5e1;
            margin: 0 7px;
        }
        .footer {
            margin-top: 12px;
            padding-top: 8px;
            border-top: 1px solid #e2e8f0;
            font-size: 7.5pt;
            color: #94a3b8;
            text-align: center;
        }
    </style>
</head>
<body>
    @php
        $metaItems = [];
        if ($recipe->total_time_min) {
            $metaItems[] = ['label' => __('recipes.total_time'), 'value' => $recipe->total_time_min.' '.__('recipes.min')];
        }
        if ($recipe->prep_time_min) {
            $metaItems[] = ['label' => __('recipes.prep'), 'value' => $recipe->prep_time_min.' '.__('recipes.min')];
        }
        if ($recipe->cook_time_min) {
            $metaItems[] = ['label' => __('recipes.cook'), 'value' => $recipe->cook_time_min.' '.__('recipes.min')];
        }
        if ($recipe->servings) {
            $metaItems[] = ['label' => null, 'value' => $recipe->servings.' '.__('recipes.servings')];
        }
        if ($recipe->difficulty) {
            $metaItems[] = ['label' => null, 'value' => __('recipes.difficulty_'.$recipe->difficulty)];
        }
        if ($recipe->cuisine) {
            $metaItems[] = ['label' => null, 'value' => $recipe->cuisine->name];
        }
        if ($recipe->category) {
            $metaItems[] = ['label' => null, 'value' => $recipe->category->name];
        }
    @endphp

    <table class="header-table">
        <tr>
            @if ($heroDataUri)
                <td class="hero-cell">
                    <img src="{{ $heroDataUri }}" alt="" class="hero">
                </td>
            @endif
            <td>
                <h1>{{ $recipe->title }}</h1>

                @if ($recipe->summary)
                    <p class="summary">{{ $recipe->summary }}</p>
                @endif

                @if (! empty($metaItems))
                    <div class="meta">
                        @foreach ($metaItems as $i => $item)
                            @if ($i > 0)<span class="meta-sep">·</span>@endif
                            @if ($item['label'])<span class="meta-label">{{ $item['label'] }}:</span> @endif{{ $item['value'] }}
                        @endforeach
                    </div>
                @endif

                @if ($recipe->tags->isNotEmpty())
                    <div class="tags">
                        @foreach ($recipe->tags as $tag)
                            <span class="tag">{{ $tag->name }}</span>
                        @endforeach
                    </div>
                @endif
            </td>
        </tr>
    </table>

    <table class="body-grid">
        <tr>
            <td class="col-ingredients">
                <h2>{{ __('recipes.ingredients') }}</h2>

                @php
                    $grouped = $recipe->recipeIngredients->groupBy('group_label');
                @endphp

                @foreach ($grouped as $label => $ingredients)
                    @if ($label)
                        <p class="group-label">{{ $label }}</p>
                    @endif

                    <ul class="ingredient-list">
                        @foreach ($ingredients as $ri)
                            <li>
                                @if ((float) $ri->amount > 0)
                                    <span class="ingredient-amount">{{ rtrim(rtrim(number_format((float) $ri->amount, 3), '0'), '.') }}</span>
                                @endif
                                @if ($ri->unit)
                                    {{ $ri->unit->name }}
                                @endif
                                {{ $ri->ingredient?->name }}
                                @if ($ri->note)
                                    <span class="note"> &mdash; {{ $ri->note }}</span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endforeach
            </td>
            <td class="col-instructions">
                <h2>{{ __('recipes.instructions') }}</h2>

                <table class="steps">
                    @foreach ($recipe->steps as $step)
                        <tr>
                            <td class="step-number-cell"><span class="step-number">{{ $step->position }}</span></td>
                            <td class="step-body">{!! nl2br(e($step->body)) !!}</td>
                        </tr>
                    @endforeach
                </table>
            </td>
        </tr>
    </table>

    @if ($recipe->nutrition_cached_at)
        <div class="nutrition-strip">
            <strong>{{ __('recipes.nutrition_per_serving') }}:</strong>
            <strong>{{ number_format((float) $recipe->display_kcal_per_serving, 0) }}</strong> {{ __('recipes.kcal') }}
            <span class="nutrition-sep">·</span>
            {{ __('recipes.protein') }} <strong>{{ number_format((float) $recipe->display_protein_per_serving_g, 1) }} g</strong>
            <span class="nutrition-sep">·</span>
            {{ __('recipes.fat') }} <strong>{{ number_format((float) $recipe->display_fat_per_serving_g, 1) }} g</strong>
            <span class="nutrition-sep">·</span>
            {{ __('recipes.carbs') }} <strong>{{ number_format((float) $recipe->display_carbs_per_serving_g, 1) }} g</strong>
            <span class="nutrition-sep">·</span>
            {{ __('recipes.fiber') }} <strong>{{ number_format((float) $recipe->fiber_per_serving_g, 1) }} g</strong>
        </div>
    @endif

    <div class="footer">
        {{ config('app.name') }} &mdash; {{ $recipe->title }}
        @if ($recipe->author)
            &mdash; {{ __('recipes.author') }}: {{ $recipe->author->name }}
        @endif
    </div>
</body>
</html>
