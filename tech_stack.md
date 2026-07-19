# Mini-CRM API Technology Stack

This document specifies the technology stack and environmental configuration chosen for the Mini-CRM API.

---

## Backend Framework & Language
- **Language**: PHP 8.2+
- **Framework**: Laravel 11/12
- **Authentication**: Laravel Sanctum (Token-based API authentication)

---

## Database Management System
- **Database**: PostgreSQL (v16+ recommended)
- **Rationale**: Robust support for complex queries, rich indexing, and scales efficiently for large datasets (e.g., ~100k leads).
- **Driver**: `pgsql` (Laravel Eloquent)

---

## Infrastructure & Containerization
- **Development Environment**: Docker & Laravel Sail
  - Local environment is fully containerized.
  - No need for PHP, Composer, or PostgreSQL to be installed on the host OS.
- **Sail Services**:
  - `laravel.test` (Application service running PHP 8.3)
  - `pg_database` (PostgreSQL service container)
  - `mailpit` (Local mail testing server)

---

## Queues & Background Workers
- **Queue Connection**: `database` (utilizing PostgreSQL database tables to queue and process jobs)
- **Usage**: Dispatching `NotifyRepOfAssignment` jobs to process assignments asynchronously without slowing down API responses.
