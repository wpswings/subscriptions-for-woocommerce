# Running PHPUnit Tests — Subscriptions for WooCommerce

## Prerequisites

| Tool | Location |
|---|---|
| PHP (system) | `php` |
| PHPUnit 9 PHAR | `/tmp/phpunit.phar` |
| WP test library | `/tmp/wordpress-tests-lib/` |
| WP core (test install) | `/tmp/wordpress/` |
| PHPUnit Polyfills | `/tmp/phpunit-polyfills/` |

> To rebuild the test library from scratch, run:
> ```bash
> bash tests/bin/install-wp-tests.sh local root root \
>     "localhost:/home/danish-1100/.config/Local/run/7KZMXPzv7/mysql/mysqld.sock" latest true
> ```

---

## Base Command

All commands below must be run from the **plugin root** with `WP_TESTS_DIR` set.

```bash
cd "/home/danish-1100/Local Sites/subscription-orgpro-2026-jun/app/public/wp-content/plugins/subscriptions-for-woocommerce"
export WP_TESTS_DIR=/tmp/wordpress-tests-lib
```

Or inline (no export needed):

```bash
WP_TESTS_DIR=/tmp/wordpress-tests-lib php /tmp/phpunit.phar [options]
```

---

## Run All Tests

```bash
WP_TESTS_DIR=/tmp/wordpress-tests-lib php /tmp/phpunit.phar
```

Expected output: **113 tests, 166 assertions**

---

## Run a Single Test File

### CoreFunctionsTest — schema version, product→plan map, slug uniqueness

```bash
WP_TESTS_DIR=/tmp/wordpress-tests-lib php /tmp/phpunit.phar \
    tests/Unit/Membership/CoreFunctionsTest.php
```

17 tests covering:
- `wps_membership_seed_schema_version()`
- `wps_rebuild_product_plan_map()` / `wps_get_product_plan_map()`
- `wps_generate_unique_plan_slug()` / `wps_plan_slug_exists()`

---

### PlanCrudTest — plan create / read / update / delete

```bash
WP_TESTS_DIR=/tmp/wordpress-tests-lib php /tmp/phpunit.phar \
    tests/Unit/Membership/PlanCrudTest.php
```

43 tests covering:
- `wps_create_plan()` / `wps_get_plan()` / `wps_get_plan_by_slug()` / `wps_get_all_plans()`
- `wps_update_plan()` / `wps_delete_plan()`
- `wps_link_product_to_plan()` / `wps_unlink_product_from_plan()`
- `wps_get_plan_products()` / `wps_get_plan_by_product()`
- `wps_sanitize_access_length()`

---

### UserMembershipTest — user membership lifecycle

```bash
WP_TESTS_DIR=/tmp/wordpress-tests-lib php /tmp/phpunit.phar \
    tests/Unit/Membership/UserMembershipTest.php
```

53 tests covering:
- `wps_create_user_membership()` (including merge/dedup logic)
- `wps_get_membership()` / `wps_get_user_memberships()`
- `wps_update_membership_status()` / `wps_extend_membership_expiry()`
- `wps_cancel_all_memberships_for_plan()`
- `wps_membership_row_is_active()`
- `wps_user_has_plan()` / `wps_user_has_plan_any()` / `wps_current_user_has_plan()`
- `wps_user_is_member()`

---

## Run a Single Test Method

```bash
WP_TESTS_DIR=/tmp/wordpress-tests-lib php /tmp/phpunit.phar \
    --filter "test_method_name_here"
```

Examples:

```bash
# Schema version seeding
WP_TESTS_DIR=/tmp/wordpress-tests-lib php /tmp/phpunit.phar \
    --filter "test_seed_schema_version_creates_option_on_first_load"

# Plan creation
WP_TESTS_DIR=/tmp/wordpress-tests-lib php /tmp/phpunit.phar \
    --filter "test_create_plan_returns_integer_post_id_on_success"

# User membership lifecycle
WP_TESTS_DIR=/tmp/wordpress-tests-lib php /tmp/phpunit.phar \
    --filter "test_user_has_plan_returns_true_for_active_member"
```

---

## Run Tests Matching a Pattern

`--filter` accepts a regex, so you can match a group of related tests:

```bash
# All slug-related tests
WP_TESTS_DIR=/tmp/wordpress-tests-lib php /tmp/phpunit.phar \
    --filter "slug"

# All merge/dedup tests
WP_TESTS_DIR=/tmp/wordpress-tests-lib php /tmp/phpunit.phar \
    --filter "merge"

# Everything in CoreFunctionsTest
WP_TESTS_DIR=/tmp/wordpress-tests-lib php /tmp/phpunit.phar \
    --filter "CoreFunctionsTest"
```

---

## Useful Flags

| Flag | Purpose |
|---|---|
| `--verbose` | Show each test name as it runs |
| `--testdox` | Human-readable output (one line per test) |
| `--stop-on-failure` | Stop at first failure instead of running all |
| `--colors=always` | Force colour output (useful in CI) |

Example with flags:

```bash
WP_TESTS_DIR=/tmp/wordpress-tests-lib php /tmp/phpunit.phar \
    --testdox --stop-on-failure
```

---

## Available Test Methods by File

### CoreFunctionsTest (17 tests)

| Method |
|---|
| `test_seed_schema_version_creates_option_on_first_load` |
| `test_seed_schema_version_does_not_overwrite_existing_value` |
| `test_rebuild_product_plan_map_returns_empty_array_when_no_plans` |
| `test_rebuild_product_plan_map_maps_products_to_slugs` |
| `test_rebuild_product_plan_map_persists_to_options` |
| `test_rebuild_product_plan_map_last_plan_wins_for_shared_product` |
| `test_get_product_plan_map_falls_back_to_rebuild_when_option_missing` |
| `test_get_product_plan_map_returns_cached_option_without_rebuild` |
| `test_generate_unique_plan_slug_sanitizes_name` |
| `test_generate_unique_plan_slug_appends_suffix_on_collision` |
| `test_generate_unique_plan_slug_increments_suffix_until_unique` |
| `test_generate_unique_plan_slug_excludes_given_plan_from_collision_check` |
| `test_generate_unique_plan_slug_falls_back_to_plan_for_empty_name` |
| `test_plan_slug_exists_returns_false_when_no_plans` |
| `test_plan_slug_exists_returns_true_for_existing_slug` |
| `test_plan_slug_exists_returns_false_when_only_excluded_plan_matches` |
| `test_plan_slug_exists_returns_false_for_empty_slug` |

### PlanCrudTest (43 tests)

| Method |
|---|
| `test_create_plan_returns_wp_error_when_name_is_empty` |
| `test_create_plan_returns_integer_post_id_on_success` |
| `test_create_plan_stores_slug_meta` |
| `test_create_plan_accepts_explicit_slug` |
| `test_create_plan_auto_generates_unique_slug_on_collision` |
| `test_create_plan_defaults_to_active_status` |
| `test_create_plan_stores_inactive_status` |
| `test_create_plan_stores_fixed_access_length` |
| `test_create_plan_stores_product_ids` |
| `test_create_plan_fires_wps_plan_created_hook` |
| `test_create_plan_rebuilds_product_plan_map` |
| `test_get_plan_returns_null_for_missing_id` |
| `test_get_plan_returns_null_for_wrong_post_type` |
| `test_get_plan_returns_plan_array` |
| `test_get_plan_by_slug_returns_null_for_empty_slug` |
| `test_get_plan_by_slug_returns_null_for_unknown_slug` |
| `test_get_plan_by_slug_returns_correct_plan` |
| `test_get_all_plans_returns_only_plans_of_given_status` |
| `test_get_all_plans_returns_all_when_status_is_all` |
| `test_update_plan_returns_wp_error_for_missing_plan` |
| `test_update_plan_changes_name` |
| `test_update_plan_changes_status` |
| `test_update_plan_updates_products_and_rebuilds_map` |
| `test_update_plan_fires_wps_plan_updated_hook` |
| `test_update_plan_omitted_keys_are_unchanged` |
| `test_delete_plan_returns_wp_error_for_missing_plan` |
| `test_delete_plan_removes_post_from_database` |
| `test_delete_plan_fires_wps_plan_deleted_hook` |
| `test_delete_plan_clears_products_from_map` |
| `test_link_product_to_plan_returns_true_on_new_link` |
| `test_link_product_to_plan_is_idempotent` |
| `test_link_product_to_plan_returns_false_for_unknown_slug` |
| `test_unlink_product_from_plan_removes_product` |
| `test_unlink_product_from_plan_returns_false_when_not_linked` |
| `test_get_plan_products_returns_empty_for_unknown_slug` |
| `test_get_plan_products_returns_linked_ids` |
| `test_get_plan_by_product_returns_null_for_unmapped_product` |
| `test_get_plan_by_product_returns_slug_for_mapped_product` |
| `test_sanitize_access_length_returns_defaults_for_non_array` |
| `test_sanitize_access_length_preserves_fixed_type` |
| `test_sanitize_access_length_rejects_invalid_unit` |
| `test_sanitize_access_length_rejects_invalid_type` |
| `test_sanitize_access_length_casts_value_to_positive_int` |

### UserMembershipTest (53 tests)

| Method |
|---|
| `test_create_user_membership_returns_wp_error_for_invalid_user` |
| `test_create_user_membership_returns_wp_error_for_missing_slug` |
| `test_create_user_membership_returns_true_on_success` |
| `test_create_user_membership_writes_wps_memberships_meta` |
| `test_create_user_membership_writes_flat_queryable_key` |
| `test_create_user_membership_stores_subscription_pointer` |
| `test_create_user_membership_fires_wps_membership_created_hook` |
| `test_create_user_membership_stores_lifetime_when_expiry_null` |
| `test_create_user_membership_stores_expiry_timestamp` |
| `test_duplicate_create_merges_rather_than_duplicates` |
| `test_merge_keeps_subscription_source_over_order` |
| `test_merge_keeps_subscription_source_over_manual` |
| `test_merge_lifetime_expiry_wins_over_timestamp` |
| `test_merge_keeps_later_expiry_when_both_are_timestamps` |
| `test_get_membership_returns_null_when_not_a_member` |
| `test_get_membership_returns_row_after_create` |
| `test_get_user_memberships_returns_empty_when_no_memberships` |
| `test_get_user_memberships_returns_all_plans` |
| `test_get_user_memberships_filters_by_status` |
| `test_update_membership_status_returns_wp_error_for_invalid_status` |
| `test_update_membership_status_returns_wp_error_when_not_a_member` |
| `test_update_membership_status_persists_new_status` |
| `test_update_membership_status_updates_flat_key` |
| `test_update_membership_status_fires_hook` |
| `test_update_membership_status_passes_old_and_new_status_to_hook` |
| `test_update_membership_status_fires_hook_even_when_status_unchanged` |
| `test_extend_membership_expiry_returns_wp_error_when_not_a_member` |
| `test_extend_membership_expiry_updates_to_new_timestamp` |
| `test_extend_membership_expiry_null_grants_lifetime` |
| `test_extend_membership_expiry_fires_hook` |
| `test_cancel_all_memberships_for_plan_cancels_every_member` |
| `test_cancel_all_memberships_for_plan_ignores_empty_slug` |
| `test_membership_row_is_active_returns_true_for_active_with_null_expiry` |
| `test_membership_row_is_active_returns_true_for_active_future_expiry` |
| `test_membership_row_is_active_returns_false_when_expired` |
| `test_membership_row_is_active_returns_false_for_non_active_status` |
| `test_user_has_plan_returns_false_when_no_memberships` |
| `test_user_has_plan_returns_true_for_active_member` |
| `test_user_has_plan_returns_false_for_cancelled_member` |
| `test_user_has_plan_returns_false_for_expired_membership` |
| `test_user_has_plan_accepts_array_of_slugs` |
| `test_user_has_plan_returns_false_when_no_slug_in_array_matches` |
| `test_user_has_plan_any_returns_true_when_one_active_plan_exists` |
| `test_user_has_plan_any_returns_false_when_only_cancelled_plans_exist` |
| `test_current_user_has_plan_returns_false_when_not_logged_in` |
| `test_current_user_has_plan_returns_true_for_logged_in_active_member` |
| `test_user_is_member_returns_false_when_no_memberships` |
| `test_user_is_member_returns_true_when_active_in_any_plan` |
| `test_user_is_member_returns_false_when_all_memberships_cancelled` |
| `test_object_cache_is_busted_after_status_update` |
| `test_merge_rows_incoming_order_beats_existing_manual` |
| `test_merge_rows_existing_subscription_beats_incoming_order` |
| `test_merge_rows_start_date_comes_from_existing` |
