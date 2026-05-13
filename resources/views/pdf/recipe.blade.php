<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $recipe->title }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11pt;
            line-height: 1.5;
            color: #1e293b;
        }
        .header {
            border-bottom: 2px solid #059669;
            padding-bottom: 12px;
            margin-bottom: 20px;
        }
        h1 {
            font-size: 22pt;
            color: #0f172a;
            margin-bottom: 6px;
        }
        .summary {
            font-size: 11pt;
            color: #475569;
        }
        .meta {
            margin-top: 10px;
            font-size: 9pt;
            color: #64748b;
        }
        .meta span {
            margin-right: 14px;
        }
        .tags {
            margin-top: 8px;
        }
        .tag {
            display: inline-block;
            font-size: 8pt;
            color: #059669;
            border: 1px solid #059669;
            padding: 1px 8px;
            border-radius: 10px;
            margin-right: 4px;
        }
        h2 {
            font-size: 14pt;
            color: #0f172a;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 6px;
            margin-top: 24px;
            margin-bottom: 12px;
        }
        .group-label {
            font-size: 9pt;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #64748b;
            font-weight: 600;
            margin-top: 10px;
            margin-bottom: 4px;
        }
        .ingredient-list {
            list-style: none;
        }
        .ingredient-list li {
            padding: 3px 0;
            border-bottom: 1px solid #f1f5f9;
        }
        .ingredient-list li:last-child {
            border-bottom: none;
        }
        .optional {
            opacity: 0.6;
        }
        .optional-label {
            font-size: 8pt;
            color: #94a3b8;
        }
        .note {
            font-size: 9pt;
            color: #64748b;
        }
        .step {
            margin-bottom: 14px;
            page-break-inside: avoid;
        }
        .step-number {
            display: inline-block;
            width: 24px;
            height: 24px;
            line-height: 24px;
            text-align: center;
            background: #ecfdf5;
            color: #059669;
            font-weight: 700;
            font-size: 10pt;
            border-radius: 12px;
            margin-right: 8px;
        }
        .step-body {
            display: inline;
            font-size: 10.5pt;
        }
        .nutrition-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
            font-size: 10pt;
        }
        .nutrition-table th,
        .nutrition-table td {
            padding: 5px 10px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }
        .nutrition-table th {
            font-weight: 600;
            color: #475569;
            background: #f8fafc;
        }
        .nutrition-table td:last-child,
        .nutrition-table th:last-child {
            text-align: right;
        }
        .footer {
            margin-top: 30px;
            padding-top: 10px;
            border-top: 1px solid #e2e8f0;
            font-size: 8pt;
            color: #94a3b8;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $recipe->title }}</h1>

        @if ($recipe->summary)
            <p class="summary">{{ $recipe->summary }}</p>
        @endif

        <div class="meta">
            @if ($recipe->prep_time_min)
                <span>{{ __('recipes.prep') }}: {{ $recipe->prep_time_min }} {{ __('recipes.min') }}</span>
            @endif
            @if ($recipe->cook_time_min)
                <span>{{ __('recipes.cook') }}: {{ $recipe->cook_time_min }} {{ __('recipes.min') }}</span>
            @endif
            @if ($recipe->total_time_min)
                <span>{{ __('recipes.total_time') }}: {{ $recipe->total_time_min }} {{ __('recipes.min') }}</span>
            @endif
            @if ($recipe->servings)
                <span>{{ $recipe->servings }} {{ __('recipes.servings') }}</span>
            @endif
            @if ($recipe->difficulty)
                <span>{{ __('recipes.difficulty_' . $recipe->difficulty) }}</span>
            @endif
            @if ($recipe->cuisine)
                <span>{{ $recipe->cuisine->name }}</span>
            @endif
            @if ($recipe->category)
                <span>{{ $recipe->category->name }}</span>
            @endif
        </div>

        @if ($recipe->tags->isNotEmpty())
            <div class="tags">
                @foreach ($recipe->tags as $tag)
                    <span class="tag">{{ $tag->name }}</span>
                @endforeach
            </div>
        @endif
    </div>

    @if ($recipe->description)
        <div style="margin-bottom: 16px; font-size: 10.5pt; color: #334155;">
            {!! $recipe->description !!}
        </div>
    @endif

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
                <li class="{{ $ri->is_optional ? 'optional' : '' }}">
                    @if ($ri->amount)
                        <strong>{{ rtrim(rtrim(number_format((float) $ri->amount, 3), '0'), '.') }}</strong>
                    @endif
                    @if ($ri->unit)
                        {{ $ri->unit->code }}
                    @endif
                    {{ $ri->ingredient?->name }}
                    @if ($ri->note)
                        <span class="note"> &mdash; {{ $ri->note }}</span>
                    @endif
                    @if ($ri->is_optional)
                        <span class="optional-label">({{ __('recipes.optional') }})</span>
                    @endif
                </li>
            @endforeach
        </ul>
    @endforeach

    <h2>{{ __('recipes.instructions') }}</h2>

    @foreach ($recipe->steps as $step)
        <div class="step">
            <span class="step-number">{{ $step->position }}</span>
            <span class="step-body">{!! nl2br(e($step->body)) !!}</span>
        </div>
    @endforeach

    @if ($recipe->nutrition_cached_at)
        <h2>{{ __('recipes.nutrition_per_serving') }}</h2>

        <table class="nutrition-table">
            <thead>
                <tr>
                    <th></th>
                    <th>{{ __('recipes.nutrition_per_serving') }}</th>
                    <th>{{ __('recipes.nutrition_total') }}</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ __('recipes.calories') }}</td>
                    <td>{{ number_format((float) $recipe->kcal_per_serving, 0) }} {{ __('recipes.kcal') }}</td>
                    <td>{{ number_format((float) $recipe->total_kcal, 0) }} {{ __('recipes.kcal') }}</td>
                </tr>
                <tr>
                    <td>{{ __('recipes.protein') }}</td>
                    <td>{{ number_format((float) $recipe->protein_per_serving_g, 1) }}g</td>
                    <td>{{ number_format((float) $recipe->total_protein_g, 1) }}g</td>
                </tr>
                <tr>
                    <td>{{ __('recipes.fat') }}</td>
                    <td>{{ number_format((float) $recipe->fat_per_serving_g, 1) }}g</td>
                    <td>{{ number_format((float) $recipe->total_fat_g, 1) }}g</td>
                </tr>
                <tr>
                    <td>{{ __('recipes.carbs') }}</td>
                    <td>{{ number_format((float) $recipe->carbs_per_serving_g, 1) }}g</td>
                    <td>{{ number_format((float) $recipe->total_carbs_g, 1) }}g</td>
                </tr>
                <tr>
                    <td>{{ __('recipes.fiber') }}</td>
                    <td>{{ number_format((float) $recipe->fiber_per_serving_g, 1) }}g</td>
                    <td>{{ number_format((float) $recipe->total_fiber_g, 1) }}g</td>
                </tr>
            </tbody>
        </table>
    @endif

    <div class="footer">
        {{ config('app.name') }} &mdash; {{ $recipe->title }}
        @if ($recipe->author)
            &mdash; {{ __('recipes.author') }}: {{ $recipe->author->name }}
        @endif
    </div>
</body>
</html>
