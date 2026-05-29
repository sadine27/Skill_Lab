#!/usr/bin/env python3
"""
Waste System — Setup Wizard
Run this once after cloning the repo to initialize the database,
then open http://localhost/waste_system in your browser.
"""

import tkinter as tk
from tkinter import font as tkfont
import subprocess
import webbrowser
import os
import socket
import threading
import sys

# ── Embedded SQL schema ───────────────────────────────────────────────────────
SQL_SCHEMA = """\
DROP DATABASE IF EXISTS waste_system;
CREATE DATABASE waste_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE waste_system;

CREATE TABLE users (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  name          VARCHAR(120)  NOT NULL,
  email         VARCHAR(190)  NOT NULL UNIQUE,
  password_hash VARCHAR(255)  NOT NULL,
  role          ENUM('citizen','collector','admin') NOT NULL DEFAULT 'citizen',
  is_active     TINYINT(1)    NOT NULL DEFAULT 1,
  created_at    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE waste_categories (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(80) NOT NULL UNIQUE,
  description VARCHAR(255) DEFAULT NULL
) ENGINE=InnoDB;

CREATE TABLE waste_reports (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  citizen_id    INT NOT NULL,
  category_id   INT NOT NULL,
  description   TEXT NOT NULL,
  location_text VARCHAR(255) NOT NULL,
  photo_path    VARCHAR(255) DEFAULT NULL,
  status        ENUM('pending','in_progress','collected','rejected') NOT NULL DEFAULT 'pending',
  assigned_to   INT DEFAULT NULL,
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_report_citizen  FOREIGN KEY (citizen_id)  REFERENCES users(id)            ON DELETE CASCADE,
  CONSTRAINT fk_report_category FOREIGN KEY (category_id) REFERENCES waste_categories(id),
  CONSTRAINT fk_report_assignee FOREIGN KEY (assigned_to) REFERENCES users(id)            ON DELETE SET NULL,
  INDEX idx_reports_status   (status),
  INDEX idx_reports_citizen  (citizen_id),
  INDEX idx_reports_assignee (assigned_to)
) ENGINE=InnoDB;

CREATE TABLE collection_logs (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  report_id    INT NOT NULL,
  collector_id INT NOT NULL,
  action       VARCHAR(60) NOT NULL,
  notes        VARCHAR(255) DEFAULT NULL,
  logged_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_log_report    FOREIGN KEY (report_id)    REFERENCES waste_reports(id) ON DELETE CASCADE,
  CONSTRAINT fk_log_collector FOREIGN KEY (collector_id) REFERENCES users(id)         ON DELETE CASCADE,
  INDEX idx_logs_report (report_id)
) ENGINE=InnoDB;

INSERT INTO waste_categories (name, description) VALUES
  ('Household',    'General domestic / kitchen waste'),
  ('Recyclable',   'Paper, plastic, glass, and metal'),
  ('Organic',      'Garden, food, and compostable waste'),
  ('Electronic',   'E-waste: batteries, devices, cables'),
  ('Construction', 'Debris, rubble, and demolition waste'),
  ('Hazardous',    'Chemicals, medical, or toxic materials');

INSERT INTO users (name, email, password_hash, role) VALUES
  ('System Admin', 'admin@waste.local',
   '$2y$12$JdBUHJpHSJ4TxvDECrne9u03.gI3ak9jAW8J8pGYm/Q/i2J3RHPD.', 'admin');
"""

APP_URL   = "http://localhost/waste_system"
APP_LABEL = "http://localhost/waste_system"

# ── Colours ───────────────────────────────────────────────────────────────────
BG        = "#1e1e2e"
SURFACE   = "#2a2a3e"
ACCENT    = "#4ade80"   # green
ACCENT2   = "#60a5fa"   # blue
ERR       = "#f87171"   # red
TEXT      = "#e2e8f0"
TEXT_DIM  = "#94a3b8"

# ── Helpers ───────────────────────────────────────────────────────────────────

def find_mysql_exe():
    candidates = [
        r"C:\xampp\mysql\bin\mysql.exe",
        r"C:\XAMPP\mysql\bin\mysql.exe",
        r"D:\xampp\mysql\bin\mysql.exe",
        r"D:\XAMPP\mysql\bin\mysql.exe",
        r"C:\xampp64\mysql\bin\mysql.exe",
    ]
    for p in candidates:
        if os.path.isfile(p):
            return p
    return None

def mysql_is_running():
    try:
        with socket.create_connection(("127.0.0.1", 3306), timeout=2):
            return True
    except OSError:
        return False


# ── Main window ───────────────────────────────────────────────────────────────

class App(tk.Tk):
    def __init__(self):
        super().__init__()
        self.title("Waste System — Setup Wizard")
        self.resizable(False, False)
        self.configure(bg=BG)
        self._center(580, 460)

        self._build_ui()

    # ── layout ────────────────────────────────────────────────────────────────

    def _center(self, w, h):
        sw, sh = self.winfo_screenwidth(), self.winfo_screenheight()
        self.geometry(f"{w}x{h}+{(sw-w)//2}+{(sh-h)//2}")

    def _build_ui(self):
        # ── header ──
        hdr = tk.Frame(self, bg=SURFACE, height=72)
        hdr.pack(fill="x")
        hdr.pack_propagate(False)

        title_font = tkfont.Font(family="Segoe UI", size=15, weight="bold")
        sub_font   = tkfont.Font(family="Segoe UI", size=9)

        tk.Label(hdr, text="Smart Waste Aggregation System",
                 font=title_font, fg=ACCENT, bg=SURFACE).place(x=20, y=14)
        tk.Label(hdr, text="One-click setup wizard — initialises the database and opens the app",
                 font=sub_font, fg=TEXT_DIM, bg=SURFACE).place(x=20, y=42)

        # ── info grid ──
        grid = tk.Frame(self, bg=BG)
        grid.pack(fill="x", padx=20, pady=(14, 0))

        self._row(grid, 0, "XAMPP MySQL",  self._detect_mysql_label())
        self._row(grid, 1, "App URL",      APP_LABEL)
        self._row(grid, 2, "Admin login",  "admin@waste.local  /  admin123")

        # ── log box ──
        log_frame = tk.Frame(self, bg=SURFACE, bd=0)
        log_frame.pack(fill="both", expand=True, padx=20, pady=12)

        log_font = tkfont.Font(family="Consolas", size=9)
        self.log = tk.Text(log_frame, bg=SURFACE, fg=TEXT_DIM, font=log_font,
                           bd=0, relief="flat", wrap="word",
                           state="disabled", height=10)
        self.log.pack(fill="both", expand=True, padx=10, pady=8)
        self.log.tag_config("ok",   foreground=ACCENT)
        self.log.tag_config("err",  foreground=ERR)
        self.log.tag_config("info", foreground=TEXT)
        self.log.tag_config("dim",  foreground=TEXT_DIM)

        # ── status bar ──
        self.status_var = tk.StringVar(value="Ready — click Setup to begin")
        tk.Label(self, textvariable=self.status_var,
                 font=tkfont.Font(family="Segoe UI", size=9),
                 fg=TEXT_DIM, bg=BG, anchor="w").pack(
                     fill="x", padx=24, pady=(0, 6))

        # ── buttons ──
        btn_row = tk.Frame(self, bg=BG)
        btn_row.pack(pady=(0, 16))

        self.btn_setup = tk.Button(
            btn_row, text="  ▶  Run Setup  ", command=self._start_setup,
            bg=ACCENT, fg="#0f0f1a", font=tkfont.Font(family="Segoe UI", size=10, weight="bold"),
            relief="flat", cursor="hand2", padx=10, pady=6, bd=0,
            activebackground="#22c55e", activeforeground="#0f0f1a")
        self.btn_setup.pack(side="left", padx=8)

        self.btn_open = tk.Button(
            btn_row, text="  Open App  ", command=self._open_browser,
            bg=SURFACE, fg=TEXT_DIM, font=tkfont.Font(family="Segoe UI", size=10),
            relief="flat", cursor="hand2", padx=10, pady=6, bd=0,
            activebackground="#3a3a5a", activeforeground=TEXT, state="disabled")
        self.btn_open.pack(side="left", padx=8)

    def _detect_mysql_label(self):
        path = find_mysql_exe()
        if path:
            return path
        return "mysql.exe not found — install XAMPP"

    def _row(self, parent, row, label, value):
        lbl_font = tkfont.Font(family="Segoe UI", size=9)
        val_font = tkfont.Font(family="Consolas", size=9)
        tk.Label(parent, text=label, font=lbl_font, fg=TEXT_DIM, bg=BG,
                 width=14, anchor="w").grid(row=row, column=0, sticky="w", pady=2)
        tk.Label(parent, text=value, font=val_font, fg=TEXT, bg=BG,
                 anchor="w").grid(row=row, column=1, sticky="w", padx=(4, 0))

    # ── logging ───────────────────────────────────────────────────────────────

    def _log(self, text, tag="info"):
        self.log.configure(state="normal")
        self.log.insert("end", text + "\n", tag)
        self.log.see("end")
        self.log.configure(state="disabled")

    def _status(self, text):
        self.status_var.set(text)
        self.update_idletasks()

    # ── setup flow ────────────────────────────────────────────────────────────

    def _start_setup(self):
        self.btn_setup.configure(state="disabled")
        t = threading.Thread(target=self._run_setup, daemon=True)
        t.start()

    def _run_setup(self):
        self._log("=== Waste System Setup Wizard ===", "dim")

        # 1. Find mysql.exe
        self._status("Locating mysql.exe …")
        mysql_exe = find_mysql_exe()
        if not mysql_exe:
            self._log("✗  mysql.exe not found. Is XAMPP installed?", "err")
            self._log("   Checked: C:\\xampp, C:\\XAMPP, D:\\xampp, D:\\XAMPP", "err")
            self._status("Error — mysql.exe not found")
            self._re_enable_btn()
            return
        self._log(f"✓  Found mysql.exe at: {mysql_exe}", "ok")

        # 2. Check MySQL is running
        self._status("Checking MySQL connection on port 3306 …")
        if not mysql_is_running():
            self._log("✗  MySQL is not running on port 3306.", "err")
            self._log("   Please start MySQL in the XAMPP Control Panel, then try again.", "err")
            self._status("Error — MySQL not running")
            self._re_enable_btn()
            return
        self._log("✓  MySQL is running on port 3306", "ok")

        # 3. Import schema
        self._status("Importing database schema …")
        self._log("   Dropping and recreating 'waste_system' database …", "dim")
        try:
            result = subprocess.run(
                [mysql_exe, "-u", "root", "--password=", "-e", SQL_SCHEMA],
                capture_output=True, text=True, timeout=30
            )
            # mysql exits 0 even on warnings; check stderr for real errors
            if result.returncode != 0:
                raise RuntimeError(result.stderr.strip())
        except Exception as exc:
            self._log(f"✗  Database import failed:\n   {exc}", "err")
            self._status("Error — database import failed")
            self._re_enable_btn()
            return
        self._log("✓  Database 'waste_system' created & seeded", "ok")
        self._log("   Tables: users, waste_categories, waste_reports, collection_logs", "dim")
        self._log("   Admin account: admin@waste.local / admin123", "dim")

        # 4. Verify htdocs location
        self._status("Checking htdocs placement …")
        mysql_dir = os.path.dirname(os.path.dirname(os.path.dirname(mysql_exe)))  # xampp root
        htdocs_target = os.path.join(mysql_dir, "htdocs", "waste_system")
        exe_dir = os.path.dirname(os.path.abspath(
            sys.executable if getattr(sys, "frozen", False) else __file__
        ))
        if os.path.normcase(exe_dir) == os.path.normcase(htdocs_target):
            self._log(f"✓  App is already in htdocs at: {htdocs_target}", "ok")
        elif os.path.isdir(htdocs_target):
            self._log(f"✓  App folder exists at: {htdocs_target}", "ok")
        else:
            self._log(f"⚠  App not found in htdocs ({htdocs_target})", "err")
            self._log("   Clone/copy the repo to that path so Apache can serve it.", "err")
            self._status("Warning — repo not in htdocs")
            # Don't abort; DB is set up. User just needs to move the files.

        # 5. Done
        self._log("", "dim")
        self._log("✓  Setup complete!", "ok")
        self._log(f"   Open your browser at: {APP_URL}", "ok")
        self._status("Setup complete — click 'Open App' to launch")
        self.after(0, lambda: self.btn_open.configure(state="normal",
                                                       bg=ACCENT2, fg="#0f0f1a",
                                                       activebackground="#3b82f6"))
        self.after(800, self._open_browser)

    def _open_browser(self):
        webbrowser.open(APP_URL)

    def _re_enable_btn(self):
        self.after(0, lambda: self.btn_setup.configure(state="normal"))


# ── entry point ───────────────────────────────────────────────────────────────

if __name__ == "__main__":
    App().mainloop()
