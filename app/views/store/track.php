<?php /** @var string $l @var ?string $n @var ?string $e @var bool $notFound */ $t = dict($l); ?>
<div class="mx-auto max-w-md px-4 py-20 md:py-32">
  <h1 class="display text-center text-4xl"><?= e($t['trackOrder']) ?></h1>
  <form class="mt-12 space-y-6">
    <label class="block"><span class="eyebrow text-stone"><?= e($t['orderNumber']) ?></span><input name="n" required value="<?= e($n) ?>" class="field uppercase" dir="ltr"></label>
    <label class="block"><span class="eyebrow text-stone"><?= e($t['email']) ?></span><input name="e" type="email" required value="<?= e($e) ?>" class="field" dir="ltr"></label>
    <?php if ($notFound): ?><p role="alert" class="text-sm text-red-700"><?= e($t['orderNotFound']) ?></p><?php endif ?>
    <button class="btn btn-dark w-full"><?= e($t['track']) ?></button>
  </form>
</div>
