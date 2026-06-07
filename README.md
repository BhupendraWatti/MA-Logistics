# MA Logistics ERP (Phase 2)

An Enterprise Resource Planning (ERP) application tailored for logistics operations, multi-tenant billing, automated volumetric weight calculations, real-time shipment tracking, and TCPDF-based digital signature invoice generation.

Built on the **CodeIgniter 4 full-stack PHP framework**, utilizing a clean MVC pattern coupled with transactional services.

---

## 🚀 Quick Navigation & Developer Documentation

*   **[Core Developer Documentation & Session Logs](CONTEXT.md)**: A complete reference guide outlining:
    *   System Architecture (MVC & Service layers)
    *   Volumetric and Chargeable Weight calculation logic
    *   Multi-Tenant company isolation mechanisms
    *   Database schema layout, key relationships, and migration history
    *   Interactive client-side controls (SweetAlert2 navigation traps, responsive UI)
    *   API endpoints, formulas, and production scaling guidelines
    *   Detailed logs of changes, frontend bug fixes, database schema modifications, and layout improvements implemented during recent development cycles.

---

## 🛠️ Tech Stack & Requirements

*   **Backend**: PHP 8.1+ / 8.2+
*   **Framework**: CodeIgniter 4
*   **Database**: MySQL / MariaDB (5.7+ or 8.0+)
*   **Frontend**: Bootstrap 5, jQuery 3.x, SweetAlert2
*   **PDF Compiler**: TCPDF
*   **Required PHP Extensions**:
    *   `intl`
    *   `mbstring`
    *   `json`
    *   `mysqlnd`
    *   `curl`

---

## 📦 Local Installation & Setup

1.  **Clone the Repository** and make sure it is configured under your local PHP/Apache environment pointing to the `public/` directory.
2.  **Configure Environment Variables**:
    *   Copy `env` to `.env` in the root directory.
    *   Configure your MySQL database credentials:
        ```env
        database.default.hostname = localhost
        database.default.database = malogistics
        database.default.username = root
        database.default.password = 
        database.default.DBDriver = MySQLi
        ```
3.  **Run Migrations**:
    Apply the database tables, fields, and tenant contexts by running the CodeIgniter spark command:
    ```bash
    php spark migrate
    ```
4.  **Launch the Application**:
    Run CodeIgniter's internal server for development:
    ```bash
    php spark serve
    ```
    Or configure a virtual host (e.g. `http://malogistic.local`) targeting the `public/` directory.
