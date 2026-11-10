<?php
/**
 * One-off data updates that must reach databases we can't touch by hand (e.g. the live server).
 * Each runs once per database and is remembered in settings.migrations, so later edits made in the
 * dashboard are never overwritten. Each update is written to be safe even if it ran twice.
 */
function run_migrations(): void
{
    $done = (array) (settings()['migrations'] ?? []);
    $all = [
        // Nov 2026: new price list (half of the launch prices). Exact values per abaya, old and new slugs.
        '2026-11-prices-halved' => function (): void {
            $prices = [
                ['satin-sequin-panel-abaya', 'najma-abaya', 825, null],
                ['crepe-embroidered-lapel-abaya', 'diya-abaya', 445, null],
                ['crepe-beaded-flower-abaya', 'lulu-abaya', 740, 595],
                ['lace-abaya', 'yasmin-abaya', 625, null],
                ['satin-keffiyeh-abaya', 'sahar-abaya', 945, null],
                ['satin-embroidered-sleeve-abaya', 'misk-abaya', 360, null],
            ];
            foreach ($prices as [$slug, $oldSlug, $price, $sale]) {
                q('UPDATE products SET price = ?, salePrice = ? WHERE slug IN (?, ?)', [$price, $sale, $slug, $oldSlug]);
            }
        },
    ];
    // Nov 2026: add the "Winter 2026 · 2027 — coming soon" teaser to the homepage (if the database has none)
    $all['2026-11-winter-teaser'] = function (): void {
        require_once APP . '/lib/home_content.php';
        if (row("SELECT id FROM sections WHERE type = 'teaser' LIMIT 1")) return;
        $sort = (int) (row('SELECT COALESCE(MAX(sort), 0) AS m FROM sections')['m'] ?? 0) + 1; // position is fixed at render time
        insert('sections', ['id' => new_id(), 'type' => 'teaser', 'sort' => $sort, 'published' => 1, 'data' => jencode(winter_teaser_data())]);
    };
    // Nov 2026: prices a little lower. Only where the price is still the previous list price (dashboard edits are kept).
    $all['2026-11-prices-lowered'] = function (): void {
        foreach ([
                ['satin-sequin-panel-abaya', 'najma-abaya', 825, 699, null],
                ['crepe-embroidered-lapel-abaya', 'diya-abaya', 445, 379, null],
                ['crepe-beaded-flower-abaya', 'lulu-abaya', 740, 629, 499],
                ['lace-abaya', 'yasmin-abaya', 625, 529, null],
                ['satin-keffiyeh-abaya', 'sahar-abaya', 945, 799, null],
                ['satin-embroidered-sleeve-abaya', 'misk-abaya', 360, 299, null],
        ] as [$slug, $oldSlug, $was, $price, $sale]) {
            q('UPDATE products SET price = ?, salePrice = ? WHERE slug IN (?, ?) AND price = ?', [$price, $sale, $slug, $oldSlug, $was]);
        }
    };
    // Nov 2026: delivery band under the slideshow + FAQ at the end of the homepage; wording "تواصل معنا" / "تابعنا"
    $all['2026-11-home-delivery-faq'] = function (): void {
        require_once APP . '/lib/home_content.php';
        if (!row("SELECT id FROM sections WHERE type = 'shipping' LIMIT 1")) {
            $hero = (int) (row("SELECT sort FROM sections WHERE type = 'hero' ORDER BY sort LIMIT 1")['sort'] ?? -1);
            q('UPDATE sections SET sort = sort + 1 WHERE sort > ?', [$hero]);
            insert('sections', ['id' => new_id(), 'type' => 'shipping', 'sort' => $hero + 1, 'published' => 1, 'data' => jencode(home_shipping_data())]);
        }
        if (!row("SELECT id FROM sections WHERE type = 'faq' LIMIT 1")) {
            $sort = (int) (row('SELECT COALESCE(MAX(sort), 0) AS m FROM sections')['m'] ?? 0) + 1;
            insert('sections', ['id' => new_id(), 'type' => 'faq', 'sort' => $sort, 'published' => 1, 'data' => jencode(home_faq_data())]);
        }
        foreach ([['settings', 'value'], ['sections', 'data']] as [$t, $c]) {
            q("UPDATE `$t` SET `$c` = REPLACE(REPLACE(`$c`, 'تواصلي معنا', 'تواصل معنا'), 'تابعينا', 'تابعنا')");
        }
    };
    // Nov 2026: prices between 250 and 450. Only where the price is still the previous list price (dashboard edits are kept).
    $all['2026-11-prices-250-450'] = function (): void {
        foreach ([
                ['satin-embroidered-sleeve-abaya', 'misk-abaya', 299, 250, null],
                ['crepe-embroidered-lapel-abaya', 'diya-abaya', 379, 290, null],
                ['lace-abaya', 'yasmin-abaya', 529, 340, null],
                ['crepe-beaded-flower-abaya', 'lulu-abaya', 629, 380, 320],
                ['satin-sequin-panel-abaya', 'najma-abaya', 699, 410, null],
                ['satin-keffiyeh-abaya', 'sahar-abaya', 799, 450, null],
        ] as [$slug, $oldSlug, $was, $price, $sale]) {
            q('UPDATE products SET price = ?, salePrice = ? WHERE slug IN (?, ?) AND price = ?', [$price, $sale, $slug, $oldSlug, $was]);
        }
    };
    $pending = array_diff(array_keys($all), $done);
    if (!$pending) return;
    foreach ($pending as $key) {
        try {
            $all[$key]();
            $done[] = $key;
        } catch (Throwable $e) {
            error_log("migration $key failed: " . $e->getMessage());
            return; // try again on the next request
        }
    }
    save_setting('migrations', array_values($done));
}
