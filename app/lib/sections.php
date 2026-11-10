<?php
// One schema per homepage block type. The admin editor renders its form from this,
// the storefront (app/views/sections.php) reads the same keys. Add a type here + a renderer there.

function section_types(): array
{
    $bi = fn (string $key, string $label, string $kind = 'text') => [
        ['key' => $key . 'Ar', 'label' => "$label (AR)", 'kind' => $kind],
        ['key' => $key . 'En', 'label' => "$label (EN)", 'kind' => $kind],
    ];
    $cta = [...$bi('cta', 'Button text'), ['key' => 'link', 'label' => 'Button link', 'kind' => 'link']];

    return [
        'hero' => ['label' => 'Hero slideshow', 'fields' => [[
            'key' => 'slides', 'label' => 'Slides', 'kind' => 'list', 'fields' => [
                ['key' => 'media', 'label' => 'Desktop image / video', 'kind' => 'media'],
                ['key' => 'mobileMedia', 'label' => 'Mobile image / video (optional)', 'kind' => 'media'],
                ...$bi('eyebrow', 'Small label'), ...$bi('heading', 'Headline'), ...$bi('text', 'Text', 'textarea'), ...$cta,
            ],
        ]]],
        'collections' => ['label' => 'Featured collections (editorial)', 'fields' => [
            ...$bi('title', 'Section title'), ['key' => 'collectionIds', 'label' => 'Collections (empty = all published)', 'kind' => 'collections'],
        ]],
        'products' => ['label' => 'Product carousel', 'fields' => [
            ...$bi('title', 'Section title'),
            ['key' => 'source', 'label' => 'Products from', 'kind' => 'select', 'options' => ['new', 'featured', 'limited', 'ready', 'manual']],
            ['key' => 'productIds', 'label' => 'Products (when source = manual)', 'kind' => 'products'],
            ['key' => 'limit', 'label' => 'Max products', 'kind' => 'number'], ...$cta,
        ]],
        'occasions' => ['label' => 'Shop by occasion', 'fields' => [
            ...$bi('title', 'Section title'),
            ['key' => 'items', 'label' => 'Tiles', 'kind' => 'list', 'fields' => [
                ['key' => 'media', 'label' => 'Image / video', 'kind' => 'media'], ...$bi('label', 'Label'), ['key' => 'link', 'label' => 'Link', 'kind' => 'link'],
            ]],
        ]],
        'ready' => ['label' => 'Ready to wear', 'fields' => [
            ...$bi('title', 'Section title'), ...$bi('text', 'Intro', 'textarea'), ['key' => 'limit', 'label' => 'Max products', 'kind' => 'number'], ...$cta,
        ]],
        'story' => ['label' => 'Brand story / Atelier', 'fields' => [
            ...$bi('eyebrow', 'Small label'), ...$bi('title', 'Title'), ...$bi('text', 'Text', 'textarea'),
            ['key' => 'media', 'label' => 'Main image / video', 'kind' => 'media'],
            ['key' => 'gallery', 'label' => 'Detail images', 'kind' => 'list', 'fields' => [['key' => 'media', 'label' => 'Image / video', 'kind' => 'media'], ...$bi('caption', 'Caption')]],
            ...$cta,
        ]],
        'campaign' => ['label' => 'Editorial campaign', 'fields' => [
            ...$bi('eyebrow', 'Small label'), ...$bi('title', 'Title'), ...$bi('text', 'Text', 'textarea'),
            ['key' => 'layout', 'label' => 'Layout', 'kind' => 'select', 'options' => ['full', 'split', 'grid', 'mosaic', 'showcase']],
            ['key' => 'items', 'label' => 'Media', 'kind' => 'list', 'fields' => [['key' => 'media', 'label' => 'Image / video', 'kind' => 'media'], ...$bi('caption', 'Caption')]],
            ...$cta,
        ]],
        'values' => ['label' => 'Brand values', 'fields' => [
            ...$bi('title', 'Section title'),
            ['key' => 'items', 'label' => 'Values', 'kind' => 'list', 'fields' => [...$bi('title', 'Title'), ...$bi('text', 'Text', 'textarea')]],
        ]],
        'shipping' => ['label' => 'Delivery band (where we deliver)', 'fields' => [
            ...$bi('title', 'Title'),
            ['key' => 'items', 'label' => 'Destinations', 'kind' => 'list', 'fields' => [...$bi('title', 'Place'), ...$bi('text', 'Delivery time')]],
        ]],
        'faq' => ['label' => 'FAQ (questions & answers)', 'fields' => [
            ...$bi('eyebrow', 'Small label'), ...$bi('title', 'Title'),
            ['key' => 'items', 'label' => 'Questions', 'kind' => 'list', 'fields' => [...$bi('q', 'Question'), ...$bi('a', 'Answer', 'textarea')]],
        ]],
        'teaser' => ['label' => 'Coming soon teaser (upcoming collection)', 'fields' => [
            ...$bi('eyebrow', 'Small label'), ...$bi('title', 'Title'), ...$bi('year', 'Season / year'), ...$bi('soon', 'Coming soon word'),
            ...$bi('text', 'Text', 'textarea'),
            ['key' => 'media', 'label' => 'Veiled image (shown dark & blurred)', 'kind' => 'media'],
            ['key' => 'launchDate', 'label' => 'Launch date YYYY-MM-DD (optional: shows a countdown)', 'kind' => 'text'],
            ...$bi('notify', 'Sign-up line'), ...$bi('notifyBtn', 'Sign-up button'),
        ]],
        'text' => ['label' => 'Text / statement', 'fields' => [
            ...$bi('eyebrow', 'Small label'), ...$bi('title', 'Title'), ...$bi('text', 'Text', 'textarea'), ...$cta,
        ]],
    ];
}
// media fields store {id, url, kind, poster?, altAr?, altEn?} so rendering needs no lookup
