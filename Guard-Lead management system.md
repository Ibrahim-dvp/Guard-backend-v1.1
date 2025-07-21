# **Backend Development Plan & Conception (Laravel)**

## **1\. Project Overview**

This document outlines the backend architecture, database schema, and API design for the Lead Management System, adapted for the Laravel framework. The primary goal is to build a robust, scalable, and secure backend that fulfills the requirements specified in the cahier-de-charge.md, adhering to modern best practices.

## **2\. Technology Stack**

* **Framework**: **Laravel (PHP)** \- A web application framework with expressive, elegant syntax.  
* **Database**: **PostgreSQL** \- A powerful, open-source object-relational database system.  
* **ORM**: **Eloquent** \- Laravel's built-in ORM for database interaction.  
* **Authentication**: **Laravel Sanctum** \- For lightweight, token-based API authentication.  
* **Authorization**: **spatie/laravel-permission** \- For robust role and permission-based access control.  
* **Real-time Notifications**: **Laravel Echo & Pusher/Soketi** \- For broadcasting real-time events to the frontend.

## **3\. Database Schema & Migrations**

The schema will be implemented using Laravel's migration system. The spatie/laravel-permission package will add its own tables (roles, permissions, model\_has\_roles, model\_has\_permissions, role\_has\_permissions).

### **Table: organizations**

Stores partner companies and their hierarchy.  
| Column | Type | Constraints & Description |  
| :--- | :--- | :--- |  
| id | uuid | Primary Key |  
| name | string | NOT NULL |  
| parent\_id | uuid | Foreign Key to organizations.id. Nullable. |  
| director\_id | uuid | Foreign Key to users.id. |  
| is\_active | boolean | default(true) |  
| timestamps | timestamps | created\_at and updated\_at |

### **Table: users**

Stores all users. The role column is now handled by the Spatie package.  
| Column | Type | Constraints & Description |  
| :--- | :--- | :--- |  
| id | uuid | Primary Key |  
| email | string | NOT NULL, UNIQUE |  
| password | string | NOT NULL (Hashed) |  
| first\_name | string | NOT NULL |  
| last\_name | string | NOT NULL |  
| organization\_id| uuid | Foreign Key to organizations.id. Nullable. |  
| created\_by | uuid | Foreign Key to users.id. |  
| is\_active | boolean | default(true) |  
| timestamps | timestamps | created\_at and updated\_at |

### **Table: leads**

The core table for managing all lead information.  
| Column | Type | Constraints & Description |  
| :--- | :--- | :--- |  
| id | uuid | Primary Key |  
| referral\_id | uuid | Foreign Key to users.id. |  
| organization\_id| uuid | Foreign Key to organizations.id. |  
| client\_info | json | Stores client details. |  
| status | string | NOT NULL (Backed by a PHP Enum). |  
| assigned\_to\_id| uuid | Foreign Key to users.id. Nullable. |  
| source | string | default('wordpress\_form') |  
| revenue | decimal | default(0.00) |  
| timestamps | timestamps | created\_at and updated\_at |  
*(Other tables like lead\_history and appointments remain as previously defined)*

## **4\. API Endpoint Design (RESTful)**

The API routes remain the same, but the authorization logic will be more sophisticated. All protected routes will use the auth:sanctum middleware.

* /api/auth/login, /api/auth/user, /api/auth/logout  
* /api/users, /api/users/{user}  
* /api/organizations, /api/organizations/{organization}  
* /api/leads, /api/leads/{lead}, /api/leads/{lead}/assign, etc.

## **5\. Architecture & Core Logic**

This section outlines the key design patterns and practices for building clean, maintainable, and testable code.

### **Authorization: Spatie Roles & Laravel Policies**

We will use a powerful two-level authorization strategy:

1. **Spatie for Role-Based Access Control (RBAC)**: Defines *what* a user can do based on their role. We will create roles (Admin, Sales Manager, etc.) and assign permissions (edit-leads, create-users, view-reports). This is checked in routes or controllers (e.g., middleware('can:edit-leads')).  
2. **Laravel Policies for Model-Based Access Control (MBAC)**: Defines if a user can perform an action on a *specific instance* of a model. A LeadPolicy will answer questions like, "Can this specific Sales Manager edit *this specific lead*?" The logic here will check for ownership and hierarchy (e.g., return $user-\>id \=== $lead-\>assigned\_to\_id;).

This combination provides maximum flexibility and security.

### **Code Architecture & Design Patterns**

* **Service Layer**: Business logic will be encapsulated in Service classes (e.g., LeadAssignmentService, ReportingService). Controllers will call these services, keeping the controllers thin and focused on handling HTTP requests and responses.  
* **Repository Pattern**: To decouple our business logic from the database layer, we will use repositories (e.g., LeadRepository). Services will interact with repository interfaces, not directly with Eloquent models. This makes the application easier to test (we can mock the repository) and maintain.  
* **Form Request Classes**: All POST and PATCH requests will be validated using dedicated Form Request classes. This centralizes all validation logic and keeps controllers clean.  
* **API Resources**: All JSON responses will be formatted using API Resources. This ensures a consistent API output and prevents accidental leaking of sensitive model attributes.  
* **Eloquent Relationships**: All model relationships (hasMany, belongsTo, etc.) will be explicitly defined to ensure data integrity and enable efficient querying.  
* **Traits**: We will use traits to share common functionality. For example, all models will use the HasUuids trait to handle UUID primary keys automatically.

## **6\. API, Security & Best Practices**

### **Standardized JSON Responses**

All API responses will follow a consistent format for predictability.  
Success Response (200 OK, 201 Created):  
{  
  "success": true,  
  "data": { ... },  
  "message": "Operation successful."  
}

**Error Response (4xx, 5xx)**:

{  
  "success": false,  
  "message": "An error occurred.",  
  "errors": {  
    "field\_name": \["The field\_name is required."\]  
  }  
}

### **Security Best Practices**

* **Rate Limiting**: API routes will be protected by Laravel's built-in rate limiter to prevent brute-force attacks and abuse.  
* **Input Validation**: Handled by Form Request classes to prevent malicious data from entering the system.  
* **Mass Assignment Protection**: All Eloquent models will use the $fillable property to explicitly define which fields can be mass-assigned, preventing vulnerabilities.  
* **CORS Configuration**: The cors.php config will be carefully configured to only allow requests from the specific Next.js frontend domain.  
* **API Key for Public Endpoints**: The /api/leads/ingest endpoint will be protected by a dedicated middleware that checks for a secure, non-guessable API key passed in the headers.  
* **Dependency Scanning**: Regularly scan composer dependencies for known vulnerabilities.