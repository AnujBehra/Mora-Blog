#!/usr/bin/env python3
"""
Mora Blog - Live Database Inspector
Prints all live data across every SQL table in formatted tables.
Usage: python3 show_db.py
"""

import sqlite3
import os

DB_PATH = os.path.join(os.path.dirname(__file__), 'config', 'mora_blog.sqlite')

def show_database():
    if not os.path.exists(DB_PATH):
        print(f"Database not found at {DB_PATH}")
        return

    conn = sqlite3.connect(DB_PATH)
    conn.row_factory = sqlite3.Row
    c = conn.cursor()

    # Get all user tables
    c.execute("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%';")
    tables = [row['name'] for row in c.fetchall()]

    print("\n" + "=" * 70)
    print(f"📊 LIVE SQL DATABASE SNAPSHOT ({DB_PATH})")
    print("=" * 70)

    for table in tables:
        c.execute(f"SELECT COUNT(*) FROM {table}")
        count = c.fetchone()[0]

        print(f"\n📂 TABLE: {table.upper()} ({count} records)")
        print("-" * 70)

        c.execute(f"SELECT * FROM {table}")
        rows = c.fetchall()

        if not rows:
            print("  (Empty table)")
            continue

        # Print column headers
        col_names = [col[0] for col in c.description]
        
        # Display selected clean columns for readability
        for idx, row in enumerate(rows, 1):
            print(f"[{idx}] ", end="")
            details = []
            for col in col_names:
                val = row[col]
                if col in ['content', 'summary'] and val:
                    val = str(val)[:40] + "..."
                if col == 'password':
                    val = "•••••••• (hashed)"
                details.append(f"{col}: {val}")
            print(" | ".join(details))

    print("\n" + "=" * 70 + "\n")
    conn.close()

if __name__ == '__main__':
    show_database()
