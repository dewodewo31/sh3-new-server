# Testing & Auto-Fix Context

## Objective

Your objective is to validate the entire codebase by executing all available tests, identifying failures, determining the root cause, and fixing implementation issues while preserving existing business logic.

Do not stop after reporting errors. Continue until all tests pass or a blocker is encountered.

---

# Execution Rules

Follow this order strictly.

1. Discover the project structure.
2. Detect the framework and testing tools.
3. Install missing dependencies if required.
4. Execute all tests.
5. Analyze failures.
6. Fix the implementation.
7. Execute tests again.
8. Repeat until all tests pass.
9. Generate a testing report.

---

# Framework Detection

Automatically detect the project.

Possible frameworks include:

- Laravel
- Symfony
- NestJS
- Express
- Next.js
- React
- Vue
- Nuxt
- Flutter
- Django

Automatically detect the testing framework, such as:

- PHPUnit
- Pest
- Jest
- Vitest
- Mocha
- Cypress
- Playwright

---

# Laravel Testing Order

If this is a Laravel project, execute in this order.

```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan optimize:clear

composer install

php artisan migrate --force

php artisan test
```

If Pest exists, use:

```bash
./vendor/bin/pest
```

---

# Frontend Testing

If package.json exists:

```bash
npm install

npm run test
```

If Playwright exists:

```bash
npx playwright test
```

If Cypress exists:

```bash
npx cypress run
```

---

# Auto Fix Rules

When a test fails:

1. Read the complete stack trace.
2. Find the root cause.
3. Fix only the implementation causing the failure.
4. Do not suppress exceptions.
5. Do not remove assertions.
6. Do not modify tests unless they are objectively incorrect.
7. Keep the public API backward compatible whenever possible.

---

# Validation Rules

Never bypass validation.

Never disable middleware simply to make tests pass.

Never remove authorization checks.

Never remove policies.

Never skip failing tests.

---

# Database Rules

If migration issues occur:

- inspect migration order
- inspect foreign keys
- inspect indexes
- inspect factories
- inspect seeders

Fix the implementation instead of commenting migrations out.

---

# Code Quality Rules

After fixing:

- remove dead code
- remove duplicated code
- remove unused imports
- ensure formatting is consistent
- ensure naming conventions remain consistent

---

# Regression Testing

After every fix:

Run the full test suite again.

Do not run only the previously failing test unless debugging.

The final state must pass the complete test suite.

---

# Report

Generate a report containing:

## Summary

- Total Tests
- Passed
- Failed
- Skipped

## Root Cause Analysis

Explain every issue found.

## Files Modified

List every modified file.

## Reason For Modification

Explain why each file was changed.

## Remaining Issues

List blockers, if any.

---

# Success Criteria

The task is complete only when:

- all tests pass
- no regressions are introduced
- no validation is bypassed
- no authorization is removed
- no test is skipped
- implementation remains maintainable