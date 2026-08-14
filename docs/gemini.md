# M.A. Logistics ERP (MARL EXPRESS ERP) — Master Project Knowledge Base Entry Point (`gemini.md`)

Welcome to the **M.A. Logistics ERP (MARL EXPRESS ERP)** persistent project knowledge base. This file is the primary entry point for every AI session.

---

## MANDATORY AI STARTUP INSTRUCTIONS

> [!IMPORTANT]
> **READING ORDER DIRECTIVE**: Before performing any task, analysis, code modification, debugging, refactoring, or testing, **YOU MUST READ AND REVIEW THE PROJECT DOCUMENTATION IN THE EXACT FOLLOWING ORDER**:
>
> 1. [README.md](file:///d:/Company%20Work/Company%20projects/MAlogistic/docs/README.md) — Project Documentation Entry Point
> 2. [project_summary.md](file:///d:/Company%20Work/Company%20projects/MAlogistic/docs/project_summary.md) — Full Project Summary & Inter-File Linkage Map
> 3. [rules.md](file:///d:/Company%20Work/Company%20projects/MAlogistic/docs/rules.md) — Permanent Rules & Development Constraints
> 4. [architecture.md](file:///d:/Company%20Work/Company%20projects/MAlogistic/docs/architecture.md) — System Architecture & Layered Design
> 5. [functionality.md](file:///d:/Company%20Work/Company%20projects/MAlogistic/docs/functionality.md) — Complete Module Workflows & Business Logic
> 6. [changes.md](file:///d:/Company%20Work/Company%20projects/MAlogistic/docs/changes.md) — Living Implementation & Change Log
> 7. [database.md](file:///d:/Company%20Work/Company%20projects/MAlogistic/docs/database.md) — Database Schemas, Tables & Model Mappings
> 8. [api.md](file:///d:/Company%20Work/Company%20projects/MAlogistic/docs/api.md) — Public & Internal REST/JSON API Specifications
> 9. [known-issues.md](file:///d:/Company%20Work/Company%20projects/MAlogistic/docs/known-issues.md) — Known Bugs, Limitations & Workarounds
> 10. [testing.md](file:///d:/Company%20Work/Company%20projects/MAlogistic/docs/testing.md) — Testing Framework, Performance & Load Test Guides
>
> **NO IMPLEMENTATION OR CODE MODIFICATION SHOULD BEGIN UNTIL THESE FILES HAVE BEEN REVIEWED.**

---

## DOCUMENTATION MAINTENANCE RULE

> [!CAUTION]
> **SYNCHRONIZATION RULE**: The project documentation is an integral part of the project codebase.
> Whenever code is modified, added, removed, refactored, or system behavior changes, **YOU MUST REVIEW AND UPDATE THE AFFECTED DOCUMENTATION IMMEDIATELY**.
>
> At minimum, update whichever of the following documentation files are affected by the changes:
> - `rules.md`
> - `changes.md`
> - `functionality.md`
> - `architecture.md`
> - `database.md`
> - `api.md`
> - `known-issues.md`
> - `testing.md`
>
> **The documentation must always reflect the current state of the codebase. Code and documentation must remain strictly synchronized.**

---

## AI SUBAGENTS & SKILLS DIRECTIVE

Use relevant skills before implementation whenever applicable.

### Specialized AI Subagents
If specialized subagent perspectives or workflows are requested (or required by task scope), adopt the specialized domain knowledge and reusable role guidelines for:
- **QA Engineer** (Automated test suites, regression coverage, edge-case validation)
- **Senior Developer** (Clean code, SOLID principles, optimal CI4 patterns)
- **Code Reviewer** (Static analysis, vulnerability audits, performance checks)
- **Security Specialist** (CSRF validation, XSS prevention, SQL injection safety, multi-tenant isolation)
- **Performance Engineer** (Database query profiling, DataTables latency, Redis session tuning, FPM sizing)
- **Software Architect** (Layered architecture, service extraction, PDF layout stability design)
- **Tester** (Smoke testing, API contract verification, UI/UX interaction traps)
- **Database Engineer** (Schema normalization, composite indexes, migration safety, transaction bounds)
- **UI/UX Reviewer** (Bootstrap responsive compliance, SweetAlert UX flows, ultrawide display layout)
- **DevOps Engineer** (Production deployment, server tuning, OPcache, FPM/MySQL sizing)

Review available tools, MCP servers, and skill definitions located in:
- `C:\Users\bhupe\.gemini\antigravity-ide\mcp\`

Use the appropriate subagent knowledge before performing requested work.

---

## Project Documentation Structure & Responsibilities

| File | Purpose & Responsibilities |
| :--- | :--- |
| **`README.md`** | Directs every AI to startup reading order and indexes documentation. |
| **`rules.md`** | Permanent project rules, architectural constraints, business math rules, and UI conventions. |
| **`architecture.md`** | System architecture, MVC flow, Service Layer, TCPDF PDF generator design, DataTables optimization. |
| **`functionality.md`** | Detailed module workflows, formulas, form behaviors, smart field copy-forward, GST toggle mechanics. |
| **`changes.md`** | Living changelog tracking every implementation with change IDs, files modified, and risks. |
| **`database.md`** | Database table definitions, primary/foreign keys, indexes (`idx_bookings_company_id`), model mappings. |
| **`api.md`** | Documentation of public tracking API (`/api/track/*`) and internal master data APIs. |
| **`known-issues.md`** | Known technical bugs (e.g., TCPDF table cell height blowout) and temporary workarounds. |
| **`testing.md`** | Manual 10-minute smoke test checklist, performance load testing CLI commands, regression matrix. |

---

## Project Overview Summary

**MARL EXPRESS ERP (M.A. Logistics ERP)** is an enterprise-grade logistics management system built on **CodeIgniter 4 (PHP 7.4+/8.x)**, **MySQL**, **Bootstrap 5**, and **TCPDF**. It manages the end-to-end consignment lifecycle, air/surface freight manifests, dynamic master data registries, multi-surcharge billing, live courier tracking, POD image uploads, and pixel-perfect horizontal A4 PDF invoice generation.
