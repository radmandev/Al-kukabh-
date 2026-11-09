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
        require_once APP . '/lib/teaser_content.php';
        if (row("SELECT id FROM sections WHERE type = 'teaser' LIMIT 1")) return;
        $sort = (int) (row('SELECT COALESCE(MAX(sort), 0) AS m FROM sections')['m'] ?? 0) + 1; // position is fixed at render time
        insert('sections', ['id' => new_id(), 'type' => 'teaser', 'sort' => $sort, 'published' => 1, 'data' => jencode(winter_teaser_data())]);
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
