# Testing Guide

The library uses a modern testing stack designed for WordPress environments.

## 1. Testing Stack
- **Pest**: The primary test runner (built on PHPUnit).
- **Brain Monkey**: Provides WordPress hook and function mocking.
- **Mockery**: For mocking objects and services.
- **WP-Shims**: Located in `tests/helpers/wp-shims.php`, these provide basic WordPress function definitions for a clean testing environment.

## 2. Running Tests
```bash
composer test
```
To run a specific test file:
```bash
vendor/bin/pest tests/Unit/Services/MemberServiceTest.php
```

## 3. Writing Unit Tests

### 3.1 Mocking WordPress Functions
Brain Monkey allows you to mock WP functions easily:
```php
use Brain\Monkey\Functions;

it('checks permissions', function() {
    Functions\expect('is_user_logged_in')->andReturn(true);
    // ... test logic
});
```

### 3.2 Mocking Private/Protected Members
Since many services use lazy loading or protected properties, use closure binding to inject stubs:
```php
it('uses a stubbed service', function() {
    $service = new MemberService(new ConfigService());
    $stub = mock(PermissionService::class);
    
    // Injecting the stub into a private property
    (function($stub) { $this->permissionService = $stub; })->call($service, $stub);
    
    // ... perform test
});
```

### 3.3 Testing Datastar SSE
When testing controllers that return SSE, check the output buffer for Datastar-specific headers and fragment structures (e.g., `event: datastar-patch-elements`).

## 4. Best Practices
- **Isolation**: Each test should be independent. Use `setUp` and `tearDown` (managed by Brain Monkey automatically in Pest) to clear mocks.
- **Naming**: Use descriptive test names starting with `it` or `test`.
- **Coverage**: Aim for 100% coverage on new Service methods.

## 5. Current High-Value Strategy Tests
- `tests/Unit/Services/StrategiesWiringTest.php`: verifies strategy registration includes `membership_cycle`.
- `tests/Unit/Services/DirectAssignmentStrategyTest.php`: verifies explicit membership UUID resolution and scope validation behavior.
- `tests/Unit/Services/MembershipCycleStrategyTest.php`: verifies required-context guards and organization/membership scope validation.
