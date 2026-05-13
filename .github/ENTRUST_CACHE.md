# Entrust Cache Management

## Overview

**Entrust** caches user roles and permissions for performance. However, this can lead to "Permission Denied" errors when permissions are attached to roles but the cache hasn't been invalidated.

## The Problem

```
Timeline of the issue:
1. User logs in → Roles & permissions cached
2. Admin attaches new permission to user's role → Cache still has old data
3. User tries to use feature → Cache returns old permissions → Permission denied!
```

## Solution: Cache Management Commands

### Clear Cache Command

**Clear all Entrust caches immediately:**

```bash
php artisan entrust:cache-clear
```

With verbose output:
```bash
php artisan entrust:cache-clear --verbose
```

### Upgrade Command

Automatically clears cache as part of the upgrade:

```bash
php artisan daybyday:upgrade
```

## Programmatic Usage

Use the `EntrustCacheService` in your application code:

```php
use App\Services\Entrust\EntrustCacheService;

// Clear all Entrust caches
EntrustCacheService::clear();

// Clear only permission caches
EntrustCacheService::clearPermissions();

// Clear only role caches
EntrustCacheService::clearRoles();
```

## What Gets Cleared

The command flushes:

1. **Permission-Role Cache** - Maps roles to their permissions
   - Tag: `entrust.permission_role_table`
   - Controls: Role permission lookups

2. **Role-User Cache** - Maps users to their roles
   - Tag: `entrust.role_user_table`
   - Controls: User role lookups

3. **General Cache** - Fallback flush
   - Clears all cache entries as a safety measure

## When to Use

Use `entrust:cache-clear` when:

- ✅ You attach/detach permissions to roles in production
- ✅ Users report "Permission denied" after permission changes
- ✅ After running `daybyday:upgrade`
- ✅ After modifying `Entrust` configuration
- ✅ Troubleshooting permission issues

## Implementation Details

### Cache Tags

Entrust uses Laravel's **taggable cache** for fine-grained invalidation:

```php
$tag = 'entrust.permission_role_table';
Cache::tags($tag)->remember('key', 60, function () {
    // Expensive query
});
```

### Automatic Cache Invalidation

The following operations **automatically flush cache**:

- `$role->attachPermission($permission)` → Flushes permission cache
- `$role->detachPermission($permission)` → Flushes permission cache
- `$user->attachRole($role)` → Flushes role cache
- `daybyday:upgrade` → Flushes all caches

### Manual Cache Flush

Sometimes you need to clear cache manually:

```php
// In a controller or service
EntrustCacheService::clear();

// Or via command
Artisan::call('entrust:cache-clear');
```

## Testing

Tests verify:

- ✅ Cache is actually cleared
- ✅ Command returns success exit code
- ✅ Multiple cache entries are cleared
- ✅ Command is idempotent (safe to run multiple times)
- ✅ Verbose output shows details
- ✅ Works with taggable and non-taggable cache drivers

Run tests:

```bash
php artisan test tests/Feature/Commands/ClearEntrustCacheCommandTest.php --no-coverage
```

## Troubleshooting

### "Still no permissions after cache clear"

Check:

1. Is the permission attached to the role?
   ```bash
   php artisan tinker
   >>> $role = App\Models\Role::where('name', 'administrator')->first()
   >>> $role->perms()->count()  // Should have permissions
   ```

2. Is the user assigned to the role?
   ```bash
   >>> $user->roles()->count()  // Should have roles
   ```

3. Re-login after cache clear

### Permissions attached but middleware says "denied"

```php
// This middleware checks $user->can('permission-name')
// Which uses cached roles. Clear cache and re-login.
```

## Best Practices

1. **After bulk permission changes:**
   ```php
   $role->perms()->attach($permissionIds);
   EntrustCacheService::clear();
   ```

2. **In tests after permission modifications:**
   ```php
   $user->fresh();  // Reload user
   $this->actingAs($user);  // Re-authenticate
   ```

3. **In deployment scripts:**
   ```bash
   php artisan daybyday:upgrade  # Includes cache clear
   ```

## Related Files

- Command: `app/Console/Commands/ClearEntrustCacheCommand.php`
- Service: `app/Services/Entrust/EntrustCacheService.php`
- Tests: `tests/Feature/Commands/ClearEntrustCacheCommandTest.php`
- Upgrade Command: `app/Console/Commands/UpgradeCommand.php`
- Entrust Traits: `app/Zizaco/Entrust/Traits/EntrustRoleTrait.php`
- Entrust Traits: `app/Zizaco/Entrust/Traits/EntrustUserTrait.php`

