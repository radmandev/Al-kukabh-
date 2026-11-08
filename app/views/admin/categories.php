<?php /** @var array $cats */ ?>
<?= page_head('Categories') ?>
<p class="-mt-4 mb-6 text-sm text-stone">Used for shop filters and menu links (e.g. occasion → /shop?category=occasion).</p>
<?php if (isset($_GET['taken'])): ?><p class="mb-4 text-sm text-red-700">That slug is already used by another category.</p><?php endif ?>
<?= sortable_rows('category', array_map(fn ($c) => [$c['id'], '<div class="flex flex-wrap items-center gap-2">'
    . '<form method="post" action="/admin/categories" class="flex flex-1 flex-wrap gap-2">' . csrf_field()
    . '<input type="hidden" name="id" value="' . e($c['id']) . '">'
    . '<input name="nameAr" value="' . e($c['nameAr']) . '" dir="rtl" class="' . A_INP . ' w-40" aria-label="Arabic name">'
    . '<input name="nameEn" value="' . e($c['nameEn']) . '" class="' . A_INP . ' w-40" aria-label="English name">'
    . '<input name="slug" value="' . e($c['slug']) . '" class="' . A_INP . ' w-36" aria-label="Slug">'
    . '<button class="' . A_BTN2 . '">Save</button></form>'
    . '<span class="text-xs text-stone">' . (int) $c['n'] . ' products</span>'
    . post_button('/admin/categories/' . e($c['id']) . '/delete', 'Delete', A_DANGER, [], 'Delete this category?') . '</div>'], $cats)) ?>
<form method="post" action="/admin/categories" class="<?= A_CARD ?> mt-6 flex flex-wrap gap-2">
  <?= csrf_field() ?>
  <input name="nameAr" placeholder="الاسم بالعربية" dir="rtl" required class="<?= A_INP ?> w-48">
  <input name="nameEn" placeholder="English name" required class="<?= A_INP ?> w-48">
  <input name="slug" placeholder="slug (optional)" class="<?= A_INP ?> w-40">
  <button class="<?= A_BTN ?>">+ Add category</button>
</form>
