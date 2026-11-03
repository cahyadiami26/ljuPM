# Project / Task Management System PT. Lampung Jasa Utama

Aplikasi web Project/Task Management berbasis PHP + MySQL dengan konsep Kanban board, integrasi session antar aplikasi domain `.lju.co.id`, dan integrasi tabel user existing pada database berbeda.

## 1. Fitur Utama

- Dashboard overview:
  - Total project yang terlibat
  - Total ongoing project
  - Total completed project
  - Total task
  - Total completed task
  - List ongoing project
  - List ongoing task
  - List upcoming project
  - Persentase penyelesaian project
  - Waktu rata-rata penyelesaian task
- Project management:
  - Create, edit, delete project
  - Project leader
  - Team member per user, bidang, dan divisi
  - Privacy project:
    - Personal
    - Anggota tim
    - Anggota bidang
    - Anggota divisi
    - Semua user
  - Status history project
- Task management:
  - Kanban board
  - Drag & drop task
  - Kolom default: To Do, In Progress, Done
  - Pilihan kolom:
    - Backlog
    - New Request
    - To Do
    - Trouble/Blocking
    - In Progress
    - Testing
    - Done
  - Task assignment
  - Task status history
  - Card color
  - Notes
  - Multiple attachment
  - View/download attachment
- Role/access:
  - User divisi Direksi dapat melihat seluruh project/task
  - Kepala Bidang dapat melihat project pada bidangnya, kecuali personal project milik user lain
  - Kepala Divisi dapat melihat project pada divisinya, kecuali personal project milik user lain
  - Project leader dapat membuat task untuk user lain yang menjadi anggota tim
- Export PDF:
  - Export dokumentasi project dengan Dompdf
  - Gaya resmi/elegan
  - Memuat signature project leader jika tersedia

## 2. Struktur Folder

```text
project-management/
├── assets/
│   ├── css/
│   │   └── style.css
│   ├── img/
│   │   └── default-avatar.png
│   ├── js/
│   │   ├── app.js
│   │   └── kanban.js
│   └── uploads/
│       └── attachments/
├── config/
│   ├── app.php
│   ├── database.php
│   └── session.php
├── includes/
│   ├── footer.php
│   ├── functions.php
│   ├── header.php
│   └── sidebar.php
├── libs/
│   └── dompdf/
├── logs/
├── pages/
│   ├── api/
│   ├── projects/
│   ├── tasks/
│   ├── 404.php
│   ├── dashboard.php
│   └── profile.php
├── sql/
│   └── schema.sql
├── .htaccess
├── index.php
└── README.md