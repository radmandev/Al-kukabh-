<?php /** @var array $cols @var array $covers */ ?>
<?= page_head('Collections', '<a href="/admin/collections/new" class="' . A_BTN . '">+ New collection</a>') ?>
<?= sortable_rows('collection', array_map(function ($c) use ($covers) {
    $cv = $covers[$c['coverId'] ?? ''] ?? null;
    return [$c['id'], '<a href="/admin/collections/' . e($c['id']) . '" class="flex items-center gap-4">'
        . ($cv ? thumb($cv, 'aspect-[4/5] w-12 rounded') : '<div class="aspect-[4/5] w-12 rounded bg-black/5"></div>')
        . '<div class="flex-1"><p class="font-medium">' . e($c['nameEn']) . ' <span class="text-stone" dir="rtl">· ' . e($c['nameAr']) . '</span></p>'
        . '<p class="text-xs text-stone">' . (int) $c['n'] . ' products · /' . e($c['slug']) . '</p></div>'
        . ($c['published'] ? badge('Published', 'good') : badge('Hidden')) . '</a>'];
}, $cols)) ?>
