# NUR-PUSARA System

**"Cemetery Tracking System"** — an integrated Islamic cemetery management system developed for **Jabatan Agama Islam Selangor (JAIS)**.

Replaces manual processes (Excel, paper-based records) with a centralized digital platform to accurately track, locate, and manage burial lots through a structured database and digital mapping (Leaflet.js).

## Client Background
**Jabatan Agama Islam Selangor (JAIS)** — a Selangor state government agency responsible for Islamic affairs, including mosque management, Islamic education, family law, dakwah, halal management, enforcement, and R&D — as well as **cemetery/burial management** (the focus of this project).

Related unit: Bahagian Pengurusan Masjid, Unit Tanah Kubur.

## Problem Statement
- No tracking system for burial locations
- Difficulty locating graves (manual/Excel/paper records)
- Inefficient record management, data redundancy, and errors
- No real-time information or digital map visualization

## Key Features
- **Dashboard** — overview of total burials, lots, deceased records, and pending requests
- **Interactive Map** — plots burial lots on a real map (Leaflet.js) with lot status (available/occupied/maintenance)
- **Burial & Deceased Records** — full CRUD management
- **Lot Management** — add/update lots, bearing/grid calibration
- **Next-of-Kin Records** — links kin to deceased records
- **Reports** — generate burial reports, PDF export
- **Search** — quick record lookup
- **User Management** — role-based access control (admin/staff)

## Team
| Name | Role |
|---|---|
| Nisa Afiqah binti Iskandar | Member |
| Nur Athirah binti Zulkifli | Member |
| Nur Farah Izzati binti Ahmad Shuhaimi | Member |
| Nurul Amira Syafiqah binti Noor Faizul | Member |

*Developed for IMS611 (Systems Analysis and Design in Information Management), Faculty of Information Management, UiTM Puncak Perdana Campus.*

## Tech Stack
- PHP + MySQL (mysqli)
- Bootstrap
- Leaflet.js (interactive map)

## Requirements
- [Laragon](https://laragon.org/) (or any PHP + MySQL local server)
- PHP 8.x
- MySQL / MariaDB

## Setup

1. **Place the project folder**
   Copy the `nur-pusara` folder into `C:\laragon\www\`

2. **Start Laragon**
   Click **Start All** (Apache + MySQL).

3. **Import the database**
   - Open phpMyAdmin (`http://localhost/phpmyadmin`, or right-click Laragon → Database)
   - Create a new database named **`nur_pusara`**
   - Select the database → **Import** tab → choose `database/nur_pusara.sql` → **Go**

4. **Configuration**
   Check `config/db.php` — defaults to user `root` with no password (Laragon default). Update if your MySQL setup differs.

5. **Access**
   - Via Laragon's auto virtual host: `http://nur-pusara.test` (Laragon auto-generates this based on the folder name), or
   - `http://localhost/nur-pusara`

## Login Credentials
| Role | Username | Password |
|---|---|---|
| Admin | `admin` | `admin123` |
| Staff | `alif.shauji` | `jais2026` |

## Folder Structure
```
nur-pusara/
├── assets/          # CSS, JS, images
├── auth/            # Login/logout
├── config/          # Database configuration
├── dashboard/        # Main dashboard
├── database/         # nur_pusara.sql
├── includes/         # Header, footer, sidebar, geo helper
└── modules/           # burial, deceased, faq, kin, lot, reports, search, users
```
