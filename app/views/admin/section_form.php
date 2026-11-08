<?php /** @var array $s @var array $def @var array $products @var array $collections */ ?>
<?= page_head('Edit section', '<a href="/admin/homepage" class="' . A_BTN2 . '">← Homepage</a>') ?>
<form method="post" action="/admin/homepage/<?= e($s['id']) ?>" class="space-y-6" data-section-form>
  <?= csrf_field() ?>
  <input type="hidden" name="data" value="">
  <div class="<?= A_CARD ?> sticky top-0 z-10 flex flex-wrap items-center gap-4">
    <span class="font-medium"><?= e($def['label']) ?></span>
    <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="published" class="accent-ink"<?= $s['published'] ? ' checked' : '' ?>>Published</label>
    <button class="<?= A_BTN ?>">Save section</button>
    <?php if (isset($_GET['saved'])): ?><span class="text-sm text-emerald-700" data-saved-msg>Saved ✓</span><?php endif ?>
    <button formaction="/admin/homepage/<?= e($s['id']) ?>/delete" formnovalidate data-confirm="Delete this section?" class="<?= A_DANGER ?> ms-auto">Delete</button>
  </div>
  <div class="<?= A_CARD ?>" data-section-editor="<?= attr_json(['fields' => $def['fields'], 'data' => (object) jdecode($s['data'], []), 'products' => $products, 'collections' => $collections]) ?>"></div>
</form>
