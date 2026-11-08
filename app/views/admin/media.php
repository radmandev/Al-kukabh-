<?php /** @var array $items */
$kb = fn (int $n) => $n > 1e6 ? number_format($n / 1e6, 1) . ' MB' : round($n / 1e3) . ' KB';
?>
<?= page_head('Media library') ?>
<div class="space-y-6" data-media-library>
  <div data-dropzone data-reload></div>
  <div class="flex gap-2 text-sm">
    <?php foreach (['' => 'All', 'image' => 'Images', 'video' => 'Videos'] as $k => $label): ?>
      <button type="button" data-kind-filter="<?= $k ?>" class="rounded px-3 py-1 <?= $k === '' ? 'bg-ink text-ivory' : 'bg-white' ?>"><?= $label ?></button>
    <?php endforeach ?>
  </div>
  <ul class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
    <?php foreach ($items as $m): ?>
      <li data-media-item="<?= e($m['id']) ?>" data-kind="<?= e($m['kind']) ?>" class="rounded border border-black/10 bg-white p-2 text-xs">
        <a href="<?= e($m['url']) ?>" target="_blank"><?= thumb($m, 'aspect-[3/4] rounded') ?></a>
        <p class="mt-2 text-stone"><?= $m['width'] ? (int) $m['width'] . '×' . (int) $m['height'] . ' · ' : '' ?><?= $kb((int) $m['size']) ?><?= $m['used'] ? ' · in ' . (int) $m['used'] . ' product(s)' : '' ?></p>
        <input class="<?= A_INP ?> mt-1 py-1 text-xs" dir="rtl" placeholder="Alt (AR)" value="<?= e($m['altAr']) ?>" data-alt="altAr">
        <input class="<?= A_INP ?> mt-1 py-1 text-xs" placeholder="Alt (EN)" value="<?= e($m['altEn']) ?>" data-alt="altEn">
        <div class="mt-2 flex justify-between">
          <button type="button" class="underline" data-copy="<?= e($m['url']) ?>">Copy URL</button>
          <button type="button" class="text-red-700" data-media-delete="<?= $m['used'] ? 'This file is used by products. Delete anyway?' : 'Delete this file?' ?>">Delete</button>
        </div>
      </li>
    <?php endforeach ?>
  </ul>
</div>
