# DaybydayCRM

DaybydayCRM is a system for running daily customer work in one place.

If you are **new to programming**: think of this as a digital notebook for your business.  
If you are a **senior developer**: this is a Laravel 12 CRM monolith with domain-oriented modules.

---

## What it helps you do

- Keep track of clients and contacts
- Manage leads, projects, and tasks
- Create invoices and record payments
- Store documents and appointments
- Control user access with roles and permissions

---

## Quick demo

Try it here: [demo.daybydaycrm.com](https://demo.daybydaycrm.com/?utm_source=github&utm_medium=daybydaycrmPage&utm_campaign=readme)

---

## For non-technical readers

Imagine your sticky notes, calendar, and invoice folder all moved into one organized app:

1. Add a client
2. Add tasks and deadlines
3. Send invoices
4. Follow progress from one dashboard

That is what DaybydayCRM is built for.

---

## For developers

### Stack

- PHP 8.3+
- Laravel 12
- MySQL
- Blade + legacy Vue 2 assets

### Main directories

- `app/Http` — controllers, requests, middleware
- `app/Services` — workflow/business logic
- `app/Actions` — single-purpose operations
- `app/Models` — Eloquent models
- `resources/views` — Blade templates
- `tests` — feature and unit tests

### Local setup

1. Install dependencies:
   - `composer install`
   - `npm install`
2. Copy env file:
   - `cp .env.example .env`
3. Generate key:
   - `php artisan key:generate`
4. Run migrations:
   - `php artisan migrate`
5. Start app:
   - `php artisan serve`

### Tests and linting

- Run tests: `php artisan test`
- Required PHP lint baseline:  
  `git ls-files '*.php' | xargs -n1 php -l`

---

## Contributing

Pull requests are welcome.  
Please keep changes focused, tested, and consistent with existing project conventions.

---

## License

- Version 2.0.0 and later: GNU GPLv3
- Versions before 2.0.0: MIT

