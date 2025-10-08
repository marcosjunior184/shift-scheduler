# Scheduler Service (backend)

This repository contains the backend API for the Shift Scheduler application (the "scheduler-service"). It is built with Lumen and provides endpoints to manage staff, roles and schedules (single and bulk operations) with transactional safety.

## Tech stack

- PHP 8+ (Lumen micro-framework)
- Eloquent ORM (models: Staff, Role, Schedule)
- SQLite (default for local development)
- PHPUnit + Mockery for tests

## Quick start (local development)

Prerequisites:
- PHP 8.x
- Composer
- Node.js & npm (for the frontend client in `scheduler-client`)
- php-sqllite

1. **Install PHP dependencies**

	 composer install

     if not present, install php-sqlite3

2. **Copy or create environment - important**

	 cp .env.example .env

	 .env is used on set up.

3. **Run migrations and seeders**
     
     When starting the server, it will check if database file exists (database/database.sqlite), if not it will create and seed it with some sample roles.
     If resetting database is needed, just delete the file and rerun the server.

4. **Start the dev server**

	 php -S localhost:8000 -t public

	 or if you have the Lumen `artisan` command available:

	 ./artisan serve --host=127.0.0.1 --port=8000

5. **Frontend**

	 The frontend client lives in `../scheduler-client`. From the project root:

	 cd scheduler-client
	 npm install
	 npm run dev -- --host

	 The client expects the API base URL to be localhost:8000

## Running tests

Run the PHPUnit test suite from the `scheduler-service` folder:

	./vendor/bin/phpunit

Notes:
- Without installing php-sqlite testing will fail. 
- Tests use an in-memory or sqlite database via the project's testing configuration.
- Some tests use Mockery and may require running in a separate process (`@runInSeparateProcess`) to avoid aliasing conflicts. Although annotations has been added to the unit test, so it shoudn't be a problem.

## API (overview)

All endpoints are prefixed with `/api`.

- GET /api/schedules
	- Returns list of schedules. Supports filters: `start_date`, `end_date`, `date`, `employee_id`, `role_id`, `active_only`.

- POST /api/schedules
	- Create schedules in bulk. Request body: { "shifts": [ { date, start_time, end_time, employee_id, assigned_role }, ... ] }
	- Validates per-shift rules (no overlapping shifts, max duration, date not in past). Uses DB transactions to roll back on conflicts.

- PUT /api/schedules
	- Update multiple schedules: same `shifts` array but each item requires an `id`.

- DELETE /api/schedules
	- Bulk delete: body { "shifts": [ { id }, ... ] }

- GET /api/staff, POST /api/staff, etc. (see routes/web.php)

For all other endpoints please check `routes/web`

For detailed request/response shapes consult the controllers in `app/Http/Controllers` and the tests in `tests/` which include example payloads.

## Database

- Development uses the SQLite file at `database/database.sqlite`.
- Migrations are in `database/migrations` and factories/seeders are in `database/factories` and `database/seeders`.
- **Migrations are run on server up.**


## Troubleshooting & tips

- If you hit errors about Mockery aliasing ("class already exists"), try running the affected PHPUnit tests individually or ensure tests that rely on aliasing are marked with `@runInSeparateProcess` and `@preserveGlobalState disabled`.
- If schedules are not being created due to validation, check the per-shift validation rules: date format, time format `H:i`, employee and role existence, and maximum shift duration (defined in the `Schedule` model constant).
- For SQLite permission issues, ensure the `database` directory and `database/database.sqlite` are writable by your user.

## Example curl requests

These examples assume the API is available at http://localhost:8000 and that the endpoints are prefixed with `/api`.

1) Create a Role (optional — roles may be seeded)

```bash
curl -X POST http://localhost:8000/api/roles \
	-H "Content-Type: application/json" \
	-d '{"role_name": "server", "role_description": "Front of house server"}'
```

2) Create a Staff user

```bash
curl -X POST http://localhost:8000/api/staff \
	-H "Content-Type: application/json" \
	-d '{
		"name": "Alice Example",
		"email": "alice@example.test",
		"phone_number": "+15551234567",
		"role_id": 1,
		"start_date": "2024-01-01"
	}'
```

3) Create a single shift (wrap in `shifts` array)

```bash
curl -X POST http://localhost:8000/api/schedules \
	-H "Content-Type: application/json" \
	-d '{
		"shifts": [
			{
				"date": "2025-10-08",
				"start_time": "09:00",
				"end_time": "13:00",
				"employee_id": 1,
				"assigned_role": 1
			}
		]
	}'
```

4) Create multiple shifts in one request

```bash
curl -X POST http://localhost:8000/api/schedules \
	-H "Content-Type: application/json" \
	-d '{
		"shifts": [
			{ "date": "2025-10-09", "start_time": "09:00", "end_time": "13:00", "employee_id": 1, "assigned_role": 1 },
			{ "date": "2025-10-10", "start_time": "12:00", "end_time": "16:00", "employee_id": 1, "assigned_role": 1 }
		]
	}'
```

5) Update multiple shifts (each item needs an `id`)

```bash
curl -X PUT http://localhost:8000/api/schedules \
	-H "Content-Type: application/json" \
	-d '{
		"shifts": [
			{ "id": 1, "date": "2025-10-09", "start_time": "08:00", "end_time": "12:00", "employee_id": 1, "assigned_role": 1 },
			{ "id": 2, "date": "2025-10-10", "start_time": "13:00", "end_time": "17:00", "employee_id": 1, "assigned_role": 1 }
		]
	}'
```

6) Delete multiple shifts

```bash
curl -X DELETE http://localhost:8000/api/schedules \
	-H "Content-Type: application/json" \
	-d '{ "shifts": [ { "id": 1 }, { "id": 2 } ] }'
```

Notes:
- If you use `php -S` built-in server, be sure to start it from the `scheduler-service` folder so the `public` folder is served.
- If requests return validation errors (HTTP 422) check the JSON response for `errors` which will include field-level issues.

