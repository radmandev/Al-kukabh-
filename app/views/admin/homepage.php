<?php /** @var array $secs @var array $types */ ?>
<?= page_head('Homepage', '<a href="/" target="_blank" class="' . A_BTN2 . '">Preview store ↗</a>') ?>
<p class="-mt-4 mb-6 text-sm text-stone">Sections appear on the homepage in this order. Hidden sections are kept as drafts. Story, campaign &amp; values blocks also appear on the “Our Story” page.</p>
<?= sortable_rows('section', array_map(function ($s) use ($types) {
    $d = (array) jdecode($s['data'], []);
    $title = ($d['titleEn'] ?? '') ?: (($d['titleAr'] ?? '') ?: ($s['type'] === 'hero' ? count((array) ($d['slides'] ?? [])) . ' slide(s)' : ''));
    $base = '/admin/homepage/' . e($s['id']);
    return [$s['id'], '<div class="flex flex-wrap items-center gap-3">'
        . '<a href="' . $base . '" class="min-w-0 flex-1"><p class="font-medium">' . e($types[$s['type']]['label'] ?? $s['type']) . '</p><p class="truncate text-xs text-stone">' . e(is_string($title) ? $title : '') . '</p></a>'
        . ($s['published'] ? badge('Live', 'good') : badge('Hidden'))
        . post_button("$base/toggle", $s['published'] ? 'Hide' : 'Publish', A_BTN2)
        . '<a href="' . $base . '" class="' . A_BTN2 . '">Edit</a>'
        . post_button("$base/duplicate", 'Duplicate', A_BTN2)
        . post_button("$base/delete", 'Delete', 'text-sm text-red-700', [], 'Delete this section?')
        . '</div>'];
}, $secs)) ?>
<form method="post" action="/admin/homepage" class="<?= A_CARD ?> mt-6 flex flex-wrap items-center gap-3">
  <?= csrf_field() ?>
  <span class="text-sm font-medium">Add a section</span>
  <select name="type" class="<?= A_INP ?> w-auto"><?php foreach ($types as $k => $v): ?><option value="<?= e($k) ?>"><?= e($v['label']) ?></option><?php endforeach ?></select>
  <button class="<?= A_BTN ?>">+ Add</button>
</form>
