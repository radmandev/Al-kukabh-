<?php
// URL → handler. Patterns are matched in order against the decoded path (no trailing slash).
// Capture groups are passed to the handler as string arguments.

$L = '(ar|en)';
$ID = '([A-Za-z0-9_-]+)';
$ID_OR_NEW = '(new|[A-Za-z0-9_-]+)'; // one capture group, so handlers get a single id argument

return [
    // media & resized images
    ['GET', '#^/media/([^/]+)$#', 'serve_media'],
    ['GET', '#^/img/(\d+)/(media|brand)/(.+)$#', fn ($w, $root, $path) => serve_resized((int) $w, $root, $path)],

    // admin
    ['GET', '#^/admin/login$#', 'admin_login_page'],
    ['POST', '#^/admin/login$#', 'admin_login'],
    ['POST', '#^/admin/logout$#', 'admin_logout'],
    ['GET', '#^/admin$#', 'admin_overview'],
    ['GET', '#^/admin/products$#', 'admin_products'],
    ['GET', "#^/admin/products/$ID_OR_NEW$#", 'admin_product_edit'],
    ['POST', "#^/admin/products/$ID_OR_NEW$#", 'admin_product_save'],
    ['POST', "#^/admin/products/$ID/delete$#", 'admin_product_delete'],
    ['GET', '#^/admin/collections$#', 'admin_collections'],
    ['GET', "#^/admin/collections/$ID_OR_NEW$#", 'admin_collection_edit'],
    ['POST', "#^/admin/collections/$ID_OR_NEW$#", 'admin_collection_save'],
    ['POST', "#^/admin/collections/$ID/delete$#", 'admin_collection_delete'],
    ['GET', '#^/admin/categories$#', 'admin_categories'],
    ['POST', '#^/admin/categories$#', 'admin_category_save'],
    ['POST', "#^/admin/categories/$ID/delete$#", 'admin_category_delete'],
    ['POST', '#^/admin/reorder$#', 'admin_reorder'],
    ['GET', '#^/admin/homepage$#', 'admin_homepage'],
    ['POST', '#^/admin/homepage$#', 'admin_section_add'],
    ['GET', "#^/admin/homepage/$ID$#", 'admin_section_edit'],
    ['POST', "#^/admin/homepage/$ID$#", 'admin_section_save'],
    ['POST', "#^/admin/homepage/$ID/(toggle|duplicate|delete)$#", 'admin_section_action'],
    ['GET', '#^/admin/media$#', 'admin_media'],
    ['GET', '#^/admin/api/media$#', 'admin_api_media_list'],
    ['POST', '#^/admin/api/media$#', 'admin_api_media_upload'],
    ['POST', "#^/admin/api/media/$ID$#", 'admin_api_media_update'],
    ['POST', "#^/admin/api/media/$ID/delete$#", 'admin_api_media_delete'],
    ['GET', '#^/admin/orders$#', 'admin_orders'],
    ['GET', "#^/admin/orders/$ID$#", 'admin_order'],
    ['POST', "#^/admin/orders/$ID$#", 'admin_order_update'],
    ['GET', '#^/admin/customers$#', 'admin_customers'],
    ['GET', "#^/admin/customers/$ID$#", 'admin_customer'],
    ['GET', '#^/admin/discounts$#', 'admin_discounts'],
    ['POST', '#^/admin/discounts$#', 'admin_discount_save'],
    ['POST', "#^/admin/discounts/$ID/(toggle|delete)$#", 'admin_discount_action'],
    ['GET', '#^/admin/settings$#', 'admin_settings'],
    ['POST', '#^/admin/settings$#', 'admin_settings_save'],
    ['POST', '#^/admin/settings/restore$#', 'admin_restore_store'],
    ['GET', '#^/admin/staff$#', 'admin_staff'],
    ['POST', '#^/admin/staff$#', 'admin_staff_save'],
    ['POST', "#^/admin/staff/$ID/role$#", 'admin_staff_role'],

    // storefront
    ['GET', "#^/$L$#", 'store_home'],
    ['GET', "#^/$L/shop$#", 'store_shop'],
    ['GET', "#^/$L/product/([^/]+)$#", 'store_product'],
    ['GET', "#^/$L/collections$#", 'store_collections'],
    ['GET', "#^/$L/collections/([^/]+)$#", 'store_collection'],
    ['GET', "#^/$L/story$#", 'store_story'],
    ['GET', "#^/$L/policies/([^/]+)$#", 'store_policy'],
    ['GET', "#^/$L/track$#", 'store_track'],
    ['GET', "#^/$L/wishlist$#", 'store_wishlist'],
    ['GET', "#^/$L/cards$#", 'store_cards'],
    ['GET', "#^/$L/quickview/$ID$#", 'store_quickview'],
    ['GET', "#^/$L/checkout$#", 'store_checkout'],
    ['POST', "#^/$L/checkout$#", 'store_place_order'],
    ['POST', "#^/$L/discount$#", 'store_check_discount'],
    ['GET', "#^/$L/order/([A-Za-z0-9]+)$#", 'store_order'],
    ['GET', "#^/$L/account$#", 'store_account'],
    ['POST', "#^/$L/login$#", 'store_login'],
    ['POST', "#^/$L/register$#", 'store_register'],
    ['POST', "#^/$L/logout$#", 'store_logout'],
    ['POST', "#^/$L/subscribe$#", 'store_subscribe'],
];
