# Registration, Login, and Profile Management Portal

A full-stack, responsive user portal built as an assignment matching all required guidelines.

## Technologies Used
- **Frontend:** HTML5, CSS3, JavaScript (ES6+), jQuery, Bootstrap 5.
- **Backend:** PHP 8.x.
- **Databases:**
  - **MySQL:** Stores user registration and credentials (via secure Prepared Statements).
  - **MongoDB:** Stores user profile details (Age, DOB, Contact, Biography).
  - **Redis:** Manages active user session tokens (with 1-hour expiration).
- **Session Management:** LocalStorage (browser side) and Redis (backend side). Strictly no PHP native sessions.

---

## Directory Structure
```
├── index.html
├── login.html
├── register.html
├── profile.html
├── css/
│   └── style.css
├── js/
│   ├── login.js
│   ├── register.js
│   └── profile.js
└── php/
    ├── db.php (Database connection driver manager)
    ├── login.php
    ├── register.php
    └── profile.php
```

---

## How to Run Locally

### Prerequisites
1. Ensure your local **MySQL Server** is running and configured with the database credentials (default is host: `127.0.0.1`, user: `root`, password: `1234`).
2. Ensure you have **Redis** and **MongoDB** servers running on your machine.
3. Ensure PHP is configured with the `pdo_mysql` and `mongodb` extensions enabled in your `php.ini`.

### Starting the Services
Open a terminal in the root directory:

1. **Start Redis Server:**
   ```bash
   redis-server
   ```
2. **Start MongoDB Server:**
   ```bash
   mongod --dbpath <path-to-db-data-folder>
   ```
3. **Start the PHP Web Server:**
   ```bash
   php -S 127.0.0.1:8000
   ```

### Access the Application
Go to [http://127.0.0.1:8000/index.html](http://127.0.0.1:8000/index.html) in your browser.
