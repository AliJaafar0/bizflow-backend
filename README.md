# BizFlow

BizFlow is a CRM and business management project I built to practice working on something closer to a real business system.

The idea is to give small teams one place where they can manage customers, leads, tasks, appointments, and team members.

I did not want this project to be just another simple CRUD application, so while building it I focused a lot on authentication, user roles, security, company data separation, API structure, and testing.

## What BizFlow Can Do

At the moment, BizFlow includes:

- User registration and login
- Laravel Sanctum authentication
- Email verification
- Forgot and reset password
- Profile settings
- Company settings
- Customer management
- Lead management
- Task management
- Appointment management
- Team member management
- Owner, admin, and member roles
- Dashboard statistics
- Search, filters, and pagination
- Multi-tenant company isolation
- Inactive account protection
- Rate limiting
- API error handling
- Automated security tests
- Health check endpoint

## Tech Stack

### Backend

- Laravel 12
- PHP 8.2+
- MySQL
- Laravel Sanctum
- PHPUnit
- SQLite in-memory database for testing

### Frontend

The frontend is planned to use:

- Next.js
- TypeScript
- Tailwind CSS

## How BizFlow Works

The Laravel backend handles the main logic of the project, including authentication, database operations, validation, security, and the API routes.

Every user belongs to a company.

Customers, leads, tasks, and appointments also belong to a company, so users from one company should not be able to access data from another company.

There are currently three roles in the system:

- Owner
- Admin
- Member

Owners and admins can manage team members, while normal members have more limited access.

## Customers

Users can:

- Create customers
- Edit customers
- Delete customers
- Search customers
- Filter by status
- Filter by source
- Filter by assigned user
- Use pagination

## Leads

Leads can be connected to customers and assigned to team members.

The lead pipeline currently supports these stages:

- New
- Qualified
- Proposal
- Negotiation
- Won
- Lost

Users can also search and filter leads by stage, source, customer, and assigned user.

## Tasks

Tasks can be connected to customers or leads.

A task can include:

- Title
- Description
- Due date
- Priority
- Status
- Assigned user
- Customer
- Lead

Tasks also support search, filters, and pagination.

## Appointments

Appointments include:

- Title
- Description
- Customer
- Assigned user
- Start time
- End time
- Status
- Location

They can also be filtered by date, customer, status, and assigned user.

## Team Management

Owners and admins can manage team members.

They can:

- Add members
- Change roles
- Activate or deactivate users
- Remove users

Normal members cannot access team management routes.

## Security

Security was one of the parts I spent the most time on while building BizFlow.

The backend currently includes:

- Sanctum token authentication
- Email verification
- Role-based permissions
- Company-level data isolation
- Scoped foreign-key validation
- Inactive account blocking
- Rate limiting
- Protected API routes
- API error handling
- Automated security tests

## Automated Tests

I added automated tests to make sure important parts of the backend still work when changes are made.

The tests currently cover:

- Registration
- Login
- Wrong password handling
- Unauthenticated access
- Verified and unverified users
- Role permissions
- Multi-tenant isolation
- Cross-company access prevention
- Cross-company foreign-key protection

Run all tests with:

```bash
php artisan test
```

## Local Setup

Clone the repository:

```bash
git clone YOUR_REPOSITORY_URL
```

Go inside the project:

```bash
cd bizflow-backend
```

Install the dependencies:

```bash
composer install
```

Create the environment file:

```bash
copy .env.example .env
```

Generate the application key:

```bash
php artisan key:generate
```

Create a MySQL database called:

```text
bizflow
```

Then update the database settings inside `.env`.

Run the migrations:

```bash
php artisan migrate
```

Start the Laravel development server:

```bash
php artisan serve
```

The backend will normally run on:

```text
http://127.0.0.1:8000
```

## Testing Environment

The project uses a separate testing environment with SQLite in memory.

The testing configuration is stored in:

```text
.env.testing
```

Run:

```bash
php artisan test
```

This keeps the automated tests separate from the main MySQL database.

## Health Check

The backend has a simple health check endpoint:

```text
GET /api/health
```

Example response:

```json
{
  "status": "ok",
  "app": "BizFlow",
  "environment": "local"
}
```

## Main API Endpoints

### Authentication

```text
POST /api/register
POST /api/login
POST /api/logout
GET  /api/me
```

### Password

```text
POST /api/forgot-password
POST /api/reset-password
PUT  /api/settings/password
```

### Email Verification

```text
GET  /api/email/verification-status
POST /api/email/verification-notification
GET  /api/email/verify/{id}/{hash}
```

### Dashboard

```text
GET /api/dashboard
```

### Customers

```text
GET    /api/customers
POST   /api/customers
GET    /api/customers/{customer}
PUT    /api/customers/{customer}
PATCH  /api/customers/{customer}
DELETE /api/customers/{customer}
```

Customer filters include:

```text
?search=Sara
?status=active
?source=Referral
?assigned_to=2
?page=1
?per_page=10
```

### Leads

```text
GET    /api/leads
POST   /api/leads
GET    /api/leads/{lead}
PUT    /api/leads/{lead}
PATCH  /api/leads/{lead}
DELETE /api/leads/{lead}
```

Lead filters include:

```text
?search=Website
?stage=new
?source=Instagram
?assigned_to=2
?customer_id=1
?page=1
?per_page=10
```

### Tasks

```text
GET    /api/tasks
POST   /api/tasks
GET    /api/tasks/{task}
PUT    /api/tasks/{task}
PATCH  /api/tasks/{task}
DELETE /api/tasks/{task}
```

Task filters include:

```text
?search=proposal
?status=pending
?priority=high
?assigned_to=2
?lead_id=1
?customer_id=1
?page=1
?per_page=10
```

### Appointments

```text
GET    /api/appointments
POST   /api/appointments
GET    /api/appointments/{appointment}
PUT    /api/appointments/{appointment}
PATCH  /api/appointments/{appointment}
DELETE /api/appointments/{appointment}
```

Appointment filters include:

```text
?search=call
?status=scheduled
?assigned_to=2
?customer_id=1
?date_from=2026-10-01
?date_to=2026-10-31
?page=1
?per_page=10
```

### Team

Owner and admin only:

```text
GET    /api/team
POST   /api/team
PUT    /api/team/{user}
DELETE /api/team/{user}
```

### Settings

```text
GET /api/settings/profile
PUT /api/settings/profile

GET /api/settings/company
PUT /api/settings/company
```

## Roles

BizFlow currently has three roles:

```text
owner
admin
member
```

The owner has the highest level of access.

Admins can manage team members and business data.

Members can use the CRM, but they cannot access protected team-management actions.

## Multi-Tenant Protection

One of the main goals of BizFlow was to make sure each company only works with its own data.

The backend checks the company before allowing access to:

- Customers
- Leads
- Tasks
- Appointments
- Team members

Foreign keys such as `customer_id`, `lead_id`, and `assigned_to` are also checked against the current user's company.

This helps prevent one company from using or connecting records that belong to another company.

## Authentication Flow

A normal user flow looks like this:

```text
Register
↓
Login
↓
Verify email
↓
Access dashboard
↓
Use CRM features
```

If a user account is deactivated, their current tokens are removed and they can no longer use protected routes.

## Password Recovery

Users can request a password reset with:

```text
POST /api/forgot-password
```

Then reset the password with:

```text
POST /api/reset-password
```

After the password is changed, existing authentication tokens are removed and the user has to log in again.

## Rate Limiting

Some sensitive routes have rate limiting to reduce repeated or abusive requests.

This currently includes:

- Login
- Registration
- Forgot password
- Reset password
- Email verification notifications

## Current Status

The backend is currently working as a complete MVP.

The main API features, authentication, security checks, roles, multi-tenant protection, and automated tests are working.

The next big step is building the frontend with Next.js and connecting it to the Laravel API.

## Why I Built This Project

I started BizFlow because I wanted to work on something more realistic than the smaller projects I had built before.

While working on it, I got more practice with:

- Laravel backend development
- REST APIs
- Authentication
- Database relationships
- Multi-tenant systems
- Role-based access
- Validation and security
- Automated testing
- Debugging
- API design
- Organizing a larger project

There are still things I want to improve later, especially once the frontend is added, but the backend is now at a point where it can work as a real MVP.