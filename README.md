# shift Scheduler

Welcome, for setting up client and service please follow the step on the respective REAME.md files in **Scheduler-client** and **scheduler-service**.

---

## Design Approach

Taking into consideration the time limit and requirements, my approach was simplicity over complexity. I split my work into three steps:

### 1. Technology Selection

Since the company suggested PHP with React, I decided to use those technologies. For the PHP backend, I wanted a lightweight framework to keep things simple. After considering Laminas (due to my prior experience with Zend Framework), I discovered Lumen, the lightweight version of Laravel. Although new to me, its documentation was clear, and it seemed well-suited for quickly setting up services like logging and an in-memory file-based database.

### 2. User Interaction

I focused on the UI/UX by considering how users would interact with the application, the devices they might use, and the target age group. This process helped shape the domain and define the core requirements. Again, I favoured simplicity: the result is a basic but intuitive and functional design, with some trade-offs that will be discussed later.

### 3. Domain and Architecture

To address the core scheduling concepts, I identified the essential entities: staff, roles (for staff and shifts), and shifts themselves. I structured tables for staff, roles, and shifts, setting up their relationships appropriately.

With Lumen chosen, I followed an MVC architecture, using Eloquent ORM and a simple middleware for logging. Once the domain was set, I designed the models and controllers around these core entities.

---

## Project structure

- scheduler-service/
    - app/ - controllers, models
    - database/ - migrations, seeders, sqlite file
    - routes/ - API routes
    - tests/ - PHPUnit tests

- scheduler-client/
    - src/ - React components and pages
    - public/ - static assets
    - package.json - scripts and dependencies

## Features


- Conflict detection and resolution for overlapping shifts based on existinmg shifts.
- Staff Management
- Schedule management
- Day Overview.

## Technology Stack

- **Backend Language:** PHP (Lumen micro-framework)
- **Frontend:** React (for client interface)
- **ORM:** Eloquent
- **Logging:** Simple middleware-based logging
- **Database:** In-memory file-based (for simplicity and speed)




## Limitations and Trade-offs

While the system aims for simplicity and fast setup, the implementation contains limitations and trade-offs.
The main focus was, on what I assumed, more critical feature which was schedule management and UI/UX.
Main assumptions is that the software was primirily for one restaurant and not offered as a service for several restaurants.
The trade-off and limitations are:

- **Scalability**: Expanding to more complex domains will require work.
- **Authentication and Permissions**: There is no user login or permissions system. The application assumes a manager is using it to create schedules and send them to employees.
    - Since there are no users, changes to tables like staff and shifts are not tracked, and there is no change history table.
- **Database**: The current setup uses a primarily in-memory, file-based database. There is no template for connecting to a actual sql database.
- **Only one restaurant**: System asumption is that its used by only one restaurant. Current implementation aimed to leave space for upgrading it to be able to handle different restaurants.
- **Shift Swapping**: There is no feature for shift swapping or sending/approving swap requests to a manager. Any changes must be made manually by the manager.
- **Bulk Operations**: Employee updates are performed one-by-one; there is no bulk update feature. The focus was on schedule management rather than frequent staff updates. The assumption is that shift change would be more frequent than staff updates, so more time was dedicated to scheduling actions.
- **Role Management**: There is no way to update, add, or remove roles from the UI. While the backend supports it, there was not enough time to implement this on the GUI.
- **Schedule Visualization**: Schedules are displayed only for a specific day. The initial plan was to allow week/month views for easier visualization and to show more information for each staff member, such as total hours worked in a week. But that was a trade off on focusing on schedules and trying to have a better UI.
- **Staff Availability**: There is currently no way to specify or track staff availability. The intended design included a table as follows:

    **| id | staff_id | day_of_the_week | start | end | created_at | expired_at | created_by | updated_by |**

    where `day_of_the_week` ranges from 0 (Sunday) to 6 (Saturday).
    This would enable automatic availability checks for scheduling and warn the manager if a staff member is unavailable. This was a trade-off from trying to set up a cleaner way to show/manage schedules on GUI.
- **Bulk Transaction Handling**: In bulk operations, if one item fails, the entire database transaction rolls back. This prevents partial saves and confusion. If time allowed (or if I was faster) I would consider adding a mechanism to properly report to the user what have failed and what was saved.
- **Shift management Tamplate**: The system doesn't have a template for shifts, that is, a way to set up that every monday it has 2 shifts for manager from x time to y time and 3 shifts for cook, ... ect. This would be a nice to have to allow for better planning an management. The current design is simpler  with every shift having an employee and a role attached to it.
- **TimeZone management**: System is not capable of managing different timezone. The initial idea was to set up a way to store the user preference, including its timezone. Time is normalized in the database but converted to a specific time depending on the users preference.
---

> **In Summary:** Due to the prioritization of schedule management features and UI/UX, some administrative capabilities and advanced usability features were not implemented. Additional work would be needed to support better user management, audit trails, improved schedule visualization and management, and staff availability tracking.

## Database schema

Below are the main tables created by the migrations in `scheduler-service/database/migrations`. This is a concise reference showing columns, types and key constraints.


## roles

| Column | Type | Notes |
|---|---|---|
| id | bigint (auto-increment) | Primary key |
| role_name | string(100) | Unique |
| role_description | text | nullable |
| created_at | timestamp | |
| updated_at | timestamp | |
---
## staff

| Column | Type | Notes |
|---|---|---|
| id | bigint (auto-increment) | Primary key |
| name | string(200) | |
| phone_number | string(20) | nullable |
| email | string(150) | Unique |
| role_id | bigint (foreign) | FK -> roles.id, onDelete: restrict |
| start_date | date | |
| end_date | date | nullable |
| created_at | timestamp | |
| updated_at | timestamp | |

Indexes: role_id, start_date, email

## schedules

| Column | Type | Notes |
|---|---|---|
| id | bigint (auto-increment) | Primary key |
| date | date | Shift date (Y-m-d) |
| start_time | time | |
| end_time | time | |
| employee_id | bigint (foreign) | FK -> staff.id, onDelete: cascade |
| assigned_role | bigint (foreign) | FK -> roles.id, nullable, onDelete: set null |
| created_at | timestamp | |
| updated_at | timestamp | |

Indexes: date, start_time, end_time, employee_id, assigned_role, composite (date, start_time)

Notes:
- Default roles are seeded by the `roles` migration (manager, cook, server, kitchen_staff).
- `schedules.employee_id` is set to cascade on staff deletion; deleting a staff member will remove their schedules.
- `assigned_role` on schedules is nullable and will be set to NULL if the referenced role is deleted.

If you need a diagram or SQL DDL for a different database engine (MySQL/Postgres), tell me which engine and I can produce it.