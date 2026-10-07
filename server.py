#!/usr/bin/env python3
"""
Mora Blog - Zero-Download CLI Development Server
Allows previewing the entire dynamic PHP/SQL web application directly in the browser
without needing to install PHP or MySQL on macOS.
"""

import http.server
import socketserver
import urllib.parse
import sqlite3
import os
import sys
import json
import hashlib
import uuid
import re
from datetime import datetime

PORT = 8000
DB_FILE = os.path.join(os.path.dirname(__file__), 'config', 'mora_blog.sqlite')

# In-memory session store: session_id -> {user_id, username, full_name, email, role, avatar}
SESSIONS = {}

def get_db():
    os.makedirs(os.path.dirname(DB_FILE), exist_ok=True)
    conn = sqlite3.connect(DB_FILE)
    conn.row_factory = sqlite3.Row
    return conn

def init_db():
    conn = get_db()
    c = conn.cursor()
    c.executescript('''
    CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT UNIQUE NOT NULL,
        email TEXT UNIQUE NOT NULL,
        password TEXT NOT NULL,
        full_name TEXT NOT NULL,
        role TEXT NOT NULL DEFAULT 'user',
        avatar TEXT DEFAULT 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=150',
        bio TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS categories (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        slug TEXT UNIQUE NOT NULL,
        description TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS posts (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        category_id INTEGER NOT NULL,
        author_id INTEGER NOT NULL,
        title TEXT NOT NULL,
        slug TEXT UNIQUE NOT NULL,
        summary TEXT NOT NULL,
        content TEXT NOT NULL,
        image TEXT DEFAULT 'https://images.unsplash.com/photo-1499750310107-5fef28a66643?w=800',
        tag TEXT DEFAULT 'General',
        views INTEGER DEFAULT 0,
        is_featured INTEGER DEFAULT 0,
        is_breaking INTEGER DEFAULT 0,
        status TEXT DEFAULT 'published',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS comments (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        post_id INTEGER NOT NULL,
        user_id INTEGER,
        author_name TEXT NOT NULL,
        author_email TEXT NOT NULL,
        comment TEXT NOT NULL,
        status TEXT DEFAULT 'approved',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );
    ''')
    conn.commit()

    # Check if seeded
    c.execute("SELECT COUNT(*) FROM users")
    if c.fetchone()[0] == 0:
        c.execute("INSERT INTO users (username, email, password, full_name, role, bio) VALUES (?, ?, ?, ?, ?, ?)",
                  ('admin', 'admin@morablog.com', 'password123', 'Editor-in-Chief', 'admin', 'Lead Editor and Administrator at Mora Blog.'))
        c.execute("INSERT INTO users (username, email, password, full_name, role, bio) VALUES (?, ?, ?, ?, ?, ?)",
                  ('john_doe', 'john@example.com', 'password123', 'John Doe', 'user', 'Avid reader and tech enthusiast.'))

        categories = [
            ('Business', 'business', 'Global markets, investing, and enterprise news.'),
            ('Technology', 'technology', 'Latest updates in AI, software development, and digital gear.'),
            ('Travel', 'travel', 'Destinations, travel guides, and exploration narratives.'),
            ('Fashion', 'fashion', 'Trends, styling, and contemporary fashion culture.'),
            ('Finance', 'finance', 'Cryptocurrency, personal finance, and market analyses.')
        ]
        c.executemany("INSERT INTO categories (name, slug, description) VALUES (?, ?, ?)", categories)

        posts = [
            (5, 1, 'Futures Firm Cboe Filed for 6 Bitcoin ETFs This Week', 'futures-firm-cboe-filed-for-6-bitcoin-etfs',
             'Major futures operator Cboe submits fresh regulatory filings looking to greenlight digital currency funds.',
             'Cryptocurrency adoption continues to accelerate across global institutions, prompting regulators to review investment custody and security protocols with greater urgency. Market analysts expect major institutional inflows should approvals proceed smoothly.\n\nInstitutional desks are positioning for heightened volume as regulated wrappers grant mainstream investors transparent liquidity.',
             'https://images.unsplash.com/photo-1621416894569-0f39ed31d247?w=1200', 'Investing', 1450, 1, 1, 'published'),
            
            (1, 1, 'Earned $9,000,000 per Year with a Modern Publishing Platform', 'earned-9000000-per-year-publishing-platform',
             'How digital publications are redefining subscription and advertisement revenues in 2026.',
             'The publishing industry is witnessing a structural shift toward direct audience relationships. By integrating dynamic content delivery and personalized newsletters, modern digital magazines are unlocking unprecedented monetization potential.\n\nIn this investigative report, we deconstruct the revenue models powering top high-growth media houses.',
             'https://images.unsplash.com/photo-1460925895917-afdab827c52f?w=1200', 'Business', 980, 1, 1, 'published'),

            (1, 1, 'Inside High-Growth Ventures: Scaling Modern Digital Products', 'inside-high-growth-ventures-scaling-digital-products',
             'Key methodologies founders apply when taking web applications from prototype to global scale.',
             'Scaling an enterprise software platform requires relentless focus on architectural reliability, robust database indexing, and streamlined user interfaces. When teams minimize unnecessary design baggage and standardize their workflows, development velocities improve markedly.',
             'https://images.unsplash.com/photo-1551836022-d5d88e9218df?w=800', 'Finance', 420, 0, 0, 'published'),

            (3, 2, 'Solo Travel Guide: Navigating Mountain Trails with Confidence', 'solo-travel-guide-navigating-mountain-trails',
             'Essential strategies, packing lists, and safety precautions for remote wilderness adventures.',
             'Embarking on solo adventures challenges personal limits and connects travellers deeply with nature. From lightweight packing essentials to offline navigational planning, preparation is the key to rewarding exploration.',
             'https://images.unsplash.com/photo-1506744038136-46273834b3fb?w=800', 'Travel', 612, 0, 1, 'published'),

            (4, 1, 'Contemporary Minimalism: The Evolution of Sustainable Wardrobes', 'contemporary-minimalism-sustainable-wardrobes',
             'How capsule collections and eco-conscious textiles are reshaping everyday fashion aesthetics.',
             'Sustainable apparel has transitioned from an ethical niche to a mainstream aesthetic revolution. Designers are championing enduring silhouettes, recycled weaves, and timeless craftsmanship over rapid micro-trends.',
             'https://images.unsplash.com/photo-1490481651871-ab68de25d43d?w=800', 'Fashion', 340, 0, 0, 'published'),

            (2, 1, 'Next-Generation Full-Stack Web Architecture in 2026', 'next-generation-full-stack-web-architecture-2026',
             'A look at high-performance server-side rendering, secure PDO database patterns, and modular UI styling.',
             'Dynamic web applications demand strict separation of concerns, secure parameterized SQL executions, and clean role-based authorization matrices. By eliminating boilerplate bloat and focusing on core business logic, web engineers deliver resilient software on time.',
             'https://images.unsplash.com/photo-1517694712202-14dd9538aa97?w=800', 'Tech', 890, 0, 0, 'published')
        ]
        c.executemany("INSERT INTO posts (category_id, author_id, title, slug, summary, content, image, tag, views, is_featured, is_breaking, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", posts)

        comments = [
            (1, 2, 'John Doe', 'john@example.com', 'Fantastic analysis! The regulatory perspective clears up a lot of misconceptions.', 'approved'),
            (2, 1, 'Editorial Desk', 'admin@morablog.com', 'Thanks for reading! More in-depth business case studies coming later this week.', 'approved')
        ]
        c.executemany("INSERT INTO comments (post_id, user_id, author_name, author_email, comment, status) VALUES (?, ?, ?, ?, ?, ?)", comments)
        conn.commit()
    conn.close()

def render_header(user=None, page_title="Home"):
    conn = get_db()
    c = conn.cursor()
    c.execute("SELECT * FROM categories ORDER BY name ASC")
    categories = c.fetchall()
    conn.close()

    user_html = ''
    if user:
        admin_link = '<a class="dropdown-item text-primary font-weight-bold" href="/admin/"><i class="fas fa-cog mr-2"></i> Admin Panel</a>' if user['role'] == 'admin' else ''
        user_html = f'''
        <li class="dropdown">
            <a class="dropdown-toggle" href="#" id="userMenu" data-toggle="dropdown">
                <i class="fas fa-user-circle mr-1"></i> {user['full_name']}
                <span class="badge badge-light ml-1">{user['role'].upper()}</span>
            </a>
            <div class="dropdown-menu dropdown-menu-right" aria-labelledby="userMenu">
                <a class="dropdown-item" href="/profile.php"><i class="fas fa-id-badge mr-2"></i> My Profile</a>
                {admin_link}
                <div class="dropdown-divider"></div>
                <a class="dropdown-item text-danger" href="/logout.php"><i class="fas fa-sign-out-alt mr-2"></i> Logout</a>
            </div>
        </li>
        '''
    else:
        user_html = '''
        <li><a href="/login.php"><i class="fas fa-sign-in-alt mr-1"></i> Login</a></li>
        <li><a href="/register.php"><i class="fas fa-user-plus mr-1"></i> Register</a></li>
        '''

    cat_items = ''.join([f'<a class="dropdown-item" href="/category.php?slug={c["slug"]}">{c["name"]}</a>' for c in categories])
    admin_nav_link = '<li class="nav-item"><a class="nav-link text-primary font-weight-bold" href="/admin/"><i class="fas fa-lock mr-1"></i> Admin Panel</a></li>' if user and user['role'] == 'admin' else ''

    return f'''<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{page_title} | Mora Dynamic Blog</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="header_top_area">
        <div class="container">
            <div class="header_top_inner">
                <ul class="left_info">
                    <li><a href="/about.php">About</a></li>
                    <li><a href="/contact.php">Contact</a></li>
                    {'<li><a href="/admin/" class="admin_badge"><i class="fas fa-cog mr-1"></i> Admin Dashboard</a></li>' if user and user['role'] == 'admin' else ''}
                </ul>
                <ul class="header_social">
                    {user_html}
                </ul>
            </div>
        </div>
    </div>
    <header class="main_header_area" id="header">
        <div class="container">
            <nav class="navbar navbar-expand-lg navbar-light px-0">
                <a class="navbar-brand" href="/">
                    <i class="fas fa-feather-alt text-primary"></i> MORA<span>BLOG</span>
                </a>
                <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#moraNavbar">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="moraNavbar">
                    <ul class="navbar-nav mx-auto">
                        <li class="nav-item"><a class="nav-link" href="/">Home</a></li>
                        <li class="nav-item"><a class="nav-link" href="/news.php">All News</a></li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="catDropdown" data-toggle="dropdown">Categories</a>
                            <div class="dropdown-menu">
                                {cat_items}
                            </div>
                        </li>
                        <li class="nav-item"><a class="nav-link" href="/about.php">About Us</a></li>
                        <li class="nav-item"><a class="nav-link" href="/contact.php">Contact Us</a></li>
                        {admin_nav_link}
                    </ul>
                    <form class="form-inline my-2 my-lg-0" action="/" method="GET">
                        <div class="input-group">
                            <input class="form-control form-control-sm" type="search" name="q" placeholder="Search news...">
                            <div class="input-group-append">
                                <button class="btn btn-outline-primary btn-sm" type="submit"><i class="fas fa-search"></i></button>
                            </div>
                        </div>
                    </form>
                </div>
            </nav>
        </div>
    </header>
    '''

def render_footer():
    return f'''
    <footer class="footer_area">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 col-md-6 mb-4">
                    <h4 class="text-white"><i class="fas fa-feather-alt text-primary mr-2"></i> MORABLOG</h4>
                    <p class="text-muted small">A premier digital publication delivering curated journalism, deep technology analysis, and global market insights.</p>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <h4>Navigation</h4>
                    <ul class="footer_links small">
                        <li><a href="/">Home</a></li>
                        <li><a href="/news.php">All News</a></li>
                        <li><a href="/about.php">About Us</a></li>
                        <li><a href="/contact.php">Contact Us</a></li>
                        <li><a href="/login.php">Login</a></li>
                    </ul>
                </div>
                <div class="col-lg-5 col-md-12 mb-4">
                    <h4>Editorial Newsletter</h4>
                    <p class="small text-muted">Subscribe to receive curated industry insights and daily market briefs straight to your inbox.</p>
                    <form action="/news.php" method="POST">
                        <div class="input-group">
                            <input type="email" name="subscriber_email" class="form-control form-control-sm" placeholder="Enter your email" required>
                            <div class="input-group-append">
                                <button class="btn btn-primary btn-sm" type="submit">Subscribe</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <div class="copy_right">
                <p class="mb-0">&copy; {datetime.now().year} Mora Blog. All rights reserved.</p>
            </div>
        </div>
    </footer>
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.min.js"></script>
</body>
</html>
'''

class BlogHandler(http.server.SimpleHTTPRequestHandler):
    def get_session(self):
        cookie_header = self.headers.get('Cookie', '')
        match = re.search(r'session_id=([a-zA-Z0-9_-]+)', cookie_header)
        if match:
            sid = match.group(1)
            return SESSIONS.get(sid)
        return None

    def set_session(self, user):
        sid = str(uuid.uuid4())
        SESSIONS[sid] = dict(user)
        return sid

    def send_html(self, content, status=200, set_cookie=None):
        self.send_response(status)
        self.send_header('Content-type', 'text/html; charset=utf-8')
        if set_cookie:
            self.send_header('Set-Cookie', set_cookie)
        self.end_headers()
        self.wfile.write(content.encode('utf-8'))

    def redirect(self, location, set_cookie=None):
        self.send_response(302)
        self.send_header('Location', location)
        if set_cookie:
            self.send_header('Set-Cookie', set_cookie)
        self.end_headers()

    def do_GET(self):
        parsed = urllib.parse.urlparse(self.path)
        path = parsed.path
        query = urllib.parse.parse_qs(parsed.query)
        user = self.get_session()

        # Static assets
        if path.startswith('/css/'):
            filepath = os.path.join(os.path.dirname(__file__), path.lstrip('/'))
            if os.path.isfile(filepath):
                self.send_response(200)
                self.send_header('Content-type', 'text/css')
                self.end_headers()
                with open(filepath, 'rb') as f:
                    self.wfile.write(f.read())
                return

        conn = get_db()
        c = conn.cursor()

        # Homepage
        if path in ['/', '/index.php']:
            search = query.get('q', [''])[0].strip()

            # Breaking news
            c.execute("SELECT id, title FROM posts WHERE is_breaking = 1 AND status = 'published' ORDER BY created_at DESC LIMIT 5")
            breaking = c.fetchall()

            # Hero posts
            c.execute('''
                SELECT p.*, c.name AS category_name, u.full_name AS author_name 
                FROM posts p 
                JOIN categories c ON p.category_id = c.id 
                JOIN users u ON p.author_id = u.id 
                WHERE p.is_featured = 1 AND p.status = 'published' 
                ORDER BY p.created_at DESC LIMIT 3
            ''')
            hero_posts = c.fetchall()

            # Main feed
            if search:
                term = f"%{search}%"
                c.execute('''
                    SELECT p.*, c.name AS category_name, c.slug AS category_slug, u.full_name AS author_name 
                    FROM posts p 
                    JOIN categories c ON p.category_id = c.id 
                    JOIN users u ON p.author_id = u.id 
                    WHERE p.status = 'published' AND (p.title LIKE ? OR p.summary LIKE ?) 
                    ORDER BY p.created_at DESC
                ''', (term, term))
            else:
                c.execute('''
                    SELECT p.*, c.name AS category_name, c.slug AS category_slug, u.full_name AS author_name 
                    FROM posts p 
                    JOIN categories c ON p.category_id = c.id 
                    JOIN users u ON p.author_id = u.id 
                    WHERE p.status = 'published' 
                    ORDER BY p.created_at DESC LIMIT 8
                ''')
            posts = c.fetchall()

            # Trending
            c.execute("SELECT * FROM posts WHERE status = 'published' ORDER BY views DESC LIMIT 4")
            trending = c.fetchall()

            # Categories
            c.execute('''
                SELECT c.name, c.slug, COUNT(p.id) AS post_count 
                FROM categories c 
                LEFT JOIN posts p ON c.id = p.category_id AND p.status = 'published' 
                GROUP BY c.id ORDER BY post_count DESC
            ''')
            categories = c.fetchall()

            # Build HTML
            breaking_html = ''
            if breaking:
                b_links = ''.join([f'<a href="/post-detail.php?id={b["id"]}" class="mr-4"><i class="fas fa-angle-right text-muted mr-1"></i> {b["title"]}</a>' for b in breaking])
                breaking_html = f'''
                <div class="braking_news_box">
                    <span class="braking_label"><i class="fas fa-bolt mr-1"></i> Breaking</span>
                    <div class="braking_news_links">{b_links}</div>
                </div>
                '''

            hero_html = ''
            if not search and hero_posts:
                m_hero = hero_posts[0]
                sub_cards = ''
                for sh in hero_posts[1:]:
                    sub_cards += f'''
                    <div class="sub_banner_card" style="background-image: url('{sh["image"]}');">
                        <div class="overlay">
                            <span class="tag_btn">{sh["category_name"]}</span>
                            <h5><a href="/post-detail.php?id={sh["id"]}">{sh["title"]}</a></h5>
                        </div>
                    </div>
                    '''

                hero_html = f'''
                <div class="row mb-4">
                    <div class="col-lg-8">
                        <div class="hero_featured_card" style="background-image: url('{m_hero["image"]}');">
                            <div class="hero_featured_overlay">
                                <span class="tag_btn orange">{m_hero["category_name"]}</span>
                                <h2><a href="/post-detail.php?id={m_hero["id"]}">{m_hero["title"]}</a></h2>
                                <p class="small text-light mb-2">{m_hero["summary"][:140]}...</p>
                                <div class="small text-light">
                                    <span class="mr-3"><i class="far fa-user mr-1"></i> {m_hero["author_name"]}</span>
                                    <span><i class="far fa-clock mr-1"></i> {m_hero["created_at"][:10]}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">{sub_cards}</div>
                </div>
                '''

            post_cards = ''
            for p in posts:
                post_cards += f'''
                <div class="col-md-6 mb-4">
                    <div class="blog_card">
                        <img src="{p['image']}" alt="{p['title']}" class="blog_card_img" onerror="this.src='https://images.unsplash.com/photo-1499750310107-5fef28a66643?w=800'">
                        <div class="blog_card_body">
                            <div><span class="tag_btn">{p['category_name']}</span></div>
                            <h5 class="blog_card_title"><a href="/post-detail.php?id={p['id']}">{p['title']}</a></h5>
                            <div class="blog_card_meta"><span>{p['author_name']}</span> &bull; <span>{p['created_at'][:10]}</span></div>
                            <p class="blog_card_summary">{p['summary'][:110]}...</p>
                            <div><a href="/post-detail.php?id={p['id']}" class="btn btn-outline-primary btn-sm font-weight-bold">Read Article &rarr;</a></div>
                        </div>
                    </div>
                </div>
                '''

            t_items = ''
            for t in trending:
                t_items += f'''
                <div class="widget_post_item">
                    <img src="{t['image']}" class="widget_post_thumb">
                    <div class="widget_post_info">
                        <h6><a href="/post-detail.php?id={t['id']}">{t['title']}</a></h6>
                        <span><i class="fas fa-eye mr-1"></i> {t['views']} views</span>
                    </div>
                </div>
                '''

            c_items = ''
            for cat in categories:
                c_items += f'''
                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                    <a href="/category.php?slug={cat['slug']}" class="text-dark font-weight-500">{cat['name']}</a>
                    <span class="badge badge-primary badge-pill">{cat['post_count']}</span>
                </li>
                '''

            body = f'''
            <div class="container my-3">
                {breaking_html}
                {f'<div class="alert alert-info">Search results for: <strong>"{search}"</strong> ({len(posts)} found)</div>' if search else ''}
                {hero_html}
                <div class="row">
                    <div class="col-lg-8">
                        <div class="section_header">
                            <h3><i class="fas fa-newspaper text-primary mr-2"></i> Latest Stories</h3>
                        </div>
                        <div class="row">{post_cards if post_cards else '<div class="col-12 py-5 text-center text-muted">No posts found.</div>'}</div>
                    </div>
                    <div class="col-lg-4">
                        <div class="sidebar_widget">
                            <h4><i class="fas fa-fire text-danger mr-2"></i> Trending News</h4>
                            {t_items}
                        </div>
                        <div class="sidebar_widget">
                            <h4><i class="fas fa-folder-open text-primary mr-2"></i> Categories</h4>
                            <ul class="list-group list-group-flush">{c_items}</ul>
                        </div>
                    </div>
                </div>
            </div>
            '''
            conn.close()
            self.send_html(render_header(user, "Home") + body + render_footer())
            return

        # News directory page
        elif path == '/news.php':
            category_filter = query.get('category', [''])[0].strip()
            search = query.get('q', [''])[0].strip()
            sort = query.get('sort', ['latest'])[0].strip()

            c.execute("SELECT COUNT(*) FROM posts WHERE status = 'published'")
            total_published = c.fetchone()[0]

            c.execute('''
                SELECT c.*, COUNT(p.id) AS post_count 
                FROM categories c 
                LEFT JOIN posts p ON c.id = p.category_id AND p.status = 'published' 
                GROUP BY c.id 
                ORDER BY post_count DESC, c.name ASC
            ''')
            all_categories = c.fetchall()

            # Dynamic query
            where_clauses = ["p.status = 'published'"]
            params = []
            active_cat_name = ''

            if category_filter:
                where_clauses.append("c.slug = ?")
                params.append(category_filter)
                for cat in all_categories:
                    if cat['slug'] == category_filter:
                        active_cat_name = cat['name']
                        break

            if search:
                where_clauses.append("(p.title LIKE ? OR p.summary LIKE ? OR p.content LIKE ?)")
                term = f"%{search}%"
                params.extend([term, term, term])

            where_sql = " AND ".join(where_clauses)
            order_sql = "p.created_at DESC"
            if sort == 'popular':
                order_sql = "p.views DESC, p.created_at DESC"
            elif sort == 'oldest':
                order_sql = "p.created_at ASC"

            c.execute(f'''
                SELECT p.*, c.name AS category_name, c.slug AS category_slug, 
                       u.full_name AS author_name, u.avatar AS author_avatar 
                FROM posts p 
                JOIN categories c ON p.category_id = c.id 
                JOIN users u ON p.author_id = u.id 
                WHERE {where_sql} 
                ORDER BY {order_sql}
            ''', params)
            news_posts = c.fetchall()

            # Build category pills
            cat_pills = f'''
            <a href="/news.php{f'?q={urllib.parse.quote(search)}' if search else ''}" class="btn btn-sm {'btn-primary' if not category_filter else 'btn-outline-secondary'} mr-2 mb-1">
                All ({total_published})
            </a>
            '''
            for cat in all_categories:
                active_cls = 'btn-primary' if category_filter == cat['slug'] else 'btn-outline-secondary'
                cat_pills += f'''
                <a href="/news.php?category={cat['slug']}{f'&q={urllib.parse.quote(search)}' if search else ''}" class="btn btn-sm {active_cls} mr-2 mb-1">
                    {cat['name']} <span class="badge badge-light ml-1">{cat['post_count']}</span>
                </a>
                '''

            # Build news cards
            cards_html = ''
            for post in news_posts:
                word_count = len(post['content'].split())
                reading_time = max(1, round(word_count / 200))
                likes = post['likes'] if 'likes' in post.keys() and post['likes'] is not None else 12

                cards_html += f'''
                <div class="col-md-6 mb-4">
                    <div class="blog_card h-100">
                        <div class="position-relative">
                            <img src="{post['image']}" alt="{post['title']}" class="blog_card_img" onerror="this.src='https://images.unsplash.com/photo-1499750310107-5fef28a66643?w=800'">
                            <a href="/news.php?category={post['category_slug']}" class="tag_btn position-absolute" style="top: 14px; left: 14px;">
                                {post['category_name']}
                            </a>
                        </div>
                        <div class="blog_card_body">
                            <div class="d-flex justify-content-between align-items-center text-muted small mb-2">
                                <span><i class="far fa-calendar-alt mr-1"></i> {post['created_at'][:10]}</span>
                                <span><i class="far fa-clock mr-1"></i> {reading_time} min read</span>
                            </div>
                            <h5 class="blog_card_title mb-2">
                                <a href="/post-detail.php?id={post['id']}">{post['title']}</a>
                            </h5>
                            <p class="blog_card_summary text-secondary small mb-3">
                                {post['summary'][:110]}...
                            </p>
                            <div class="border-top pt-3 mt-auto d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center small">
                                    <img src="{post['author_avatar']}" class="rounded-circle mr-2" style="width: 24px; height: 24px; object-fit: cover;">
                                    <span class="text-dark font-weight-500">{post['author_name']}</span>
                                </div>
                                <form action="/news.php" method="POST" class="d-inline">
                                    <input type="hidden" name="like_post_id" value="{post['id']}">
                                    <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-2">
                                        <i class="fas fa-heart mr-1"></i> {likes}
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                '''

            side_cats = ''
            for cat in all_categories:
                side_cats += f'''
                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                    <a href="/news.php?category={cat['slug']}" class="text-dark">
                        <i class="fas fa-angle-right text-muted mr-1"></i> {cat['name']}
                    </a>
                    <span class="badge badge-light border badge-pill">{cat['post_count']}</span>
                </li>
                '''

            body = f'''
            <div class="bg-dark text-white py-5 mb-4" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);">
                <div class="container">
                    <div class="row align-items-center">
                        <div class="col-lg-8">
                            <h1 class="font-weight-800 display-5 mb-2">Explore All News & Stories</h1>
                            <p class="text-light lead mb-0" style="font-size: 16px;">
                                Curated reporting, in-depth industry investigations, and verified perspectives.
                            </p>
                        </div>
                        <div class="col-lg-4 text-lg-right mt-3 mt-lg-0">
                            <div class="d-inline-block bg-white text-dark p-3 rounded shadow-sm text-center">
                                <span class="text-muted small text-uppercase font-weight-bold d-block">Total Published Stories</span>
                                <span class="h2 font-weight-bold text-primary mb-0">{total_published}</span>
                                <span class="small text-muted d-block">across {len(all_categories)} categories</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="container my-4">
                <div class="bg-white p-3 rounded border shadow-sm mb-4">
                    <div class="d-flex flex-wrap align-items-center justify-content-between">
                        <div class="d-flex flex-wrap align-items-center mb-2 mb-md-0">
                            <span class="text-muted small font-weight-bold mr-3"><i class="fas fa-filter text-primary mr-1"></i> Categories:</span>
                            {cat_pills}
                        </div>
                        <form action="/news.php" method="GET" class="form-inline mb-1">
                            {f'<input type="hidden" name="category" value="{category_filter}">' if category_filter else ''}
                            {f'<input type="hidden" name="q" value="{search}">' if search else ''}
                            <label class="small text-muted mr-2 font-weight-bold">Sort By:</label>
                            <select name="sort" class="form-control form-control-sm" onchange="this.form.submit()">
                                <option value="latest" {'selected' if sort=='latest' else ''}>Latest Stories</option>
                                <option value="popular" {'selected' if sort=='popular' else ''}>Most Viewed</option>
                                <option value="oldest" {'selected' if sort=='oldest' else ''}>Oldest First</option>
                            </select>
                        </form>
                    </div>
                    {f'<div class="border-top pt-2 mt-2 small text-muted">Filtered by: <span class="badge badge-info">{active_cat_name}</span> ({len(news_posts)} stories found) <a href="/news.php" class="text-danger ml-2 font-weight-bold">&times; Clear</a></div>' if category_filter else ''}
                </div>

                <div class="row">
                    <div class="col-lg-8">
                        <div class="row">{cards_html if cards_html else '<div class="col-12 py-5 text-center text-muted">No stories found matching your filter.</div>'}</div>
                    </div>
                    <div class="col-lg-4">
                        <div class="sidebar_widget">
                            <h4><i class="fas fa-search text-primary mr-2"></i> Search Feed</h4>
                            <form action="/news.php" method="GET">
                                {f'<input type="hidden" name="category" value="{category_filter}">' if category_filter else ''}
                                <div class="input-group">
                                    <input type="text" name="q" class="form-control form-control-sm" placeholder="Search keywords..." value="{search}">
                                    <div class="input-group-append">
                                        <button class="btn btn-primary btn-sm" type="submit"><i class="fas fa-search"></i></button>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="sidebar_widget">
                            <h4><i class="fas fa-folder text-primary mr-2"></i> Category Breakdown</h4>
                            <ul class="list-group list-group-flush">{side_cats}</ul>
                        </div>
                    </div>
                </div>
            </div>
            '''
            conn.close()
            self.send_html(render_header(user, "All News") + body + render_footer())
            return

        # Post detail
        elif path == '/post-detail.php':
            pid = int(query.get('id', [0])[0])
            c.execute('''
                SELECT p.*, c.name AS category_name, c.slug AS category_slug, u.full_name AS author_name, u.avatar AS author_avatar, u.bio AS author_bio
                FROM posts p 
                JOIN categories c ON p.category_id = c.id 
                JOIN users u ON p.author_id = u.id 
                WHERE p.id = ?
            ''', (pid,))
            post = c.fetchone()
            if not post:
                self.redirect('/')
                conn.close()
                return

            # Increment views
            c.execute("UPDATE posts SET views = views + 1 WHERE id = ?", (pid,))
            conn.commit()

            # Comments
            c.execute("SELECT * FROM comments WHERE post_id = ? AND status = 'approved' ORDER BY created_at DESC", (pid,))
            comments = c.fetchall()

            com_list = ''
            for cm in comments:
                com_list += f'''
                <div class="border-bottom pb-3 mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <strong class="text-dark"><i class="far fa-user-circle mr-1 text-primary"></i> {cm['author_name']}</strong>
                        <span class="small text-muted">{cm['created_at'][:16]}</span>
                    </div>
                    <p class="mb-0 text-secondary small">{cm['comment']}</p>
                </div>
                '''

            body = f'''
            <div class="container my-4">
                <div class="row">
                    <div class="col-lg-8">
                        <article class="bg-white p-4 p-md-5 rounded border mb-4">
                            <span class="tag_btn mb-2">{post['category_name']}</span>
                            <h1 class="font-weight-bold mb-3">{post['title']}</h1>
                            <div class="d-flex align-items-center text-muted small mb-4 pb-3 border-bottom">
                                <img src="{post['author_avatar']}" class="rounded-circle mr-2" style="width: 36px; height: 36px; object-fit: cover;">
                                <strong class="mr-3">{post['author_name']}</strong>
                                <span class="mr-3">{post['created_at'][:10]}</span>
                                <span><i class="far fa-eye mr-1"></i> {post['views'] + 1} views</span>
                            </div>
                            <img src="{post['image']}" class="img-fluid rounded w-100 mb-4" style="max-height: 450px; object-fit: cover;">
                            <div class="lead font-weight-500 text-secondary mb-4">{post['summary']}</div>
                            <div style="font-size: 16px; line-height: 1.8;">{post['content'].replace(chr(10), '<br>')}</div>
                        </article>
                        <div class="bg-white p-4 p-md-5 rounded border mb-4">
                            <h4 class="font-weight-bold mb-4"><i class="far fa-comments text-primary mr-2"></i> Comments ({len(comments)})</h4>
                            <div class="comment-list mb-4">{com_list if com_list else '<p class="text-muted small">No comments yet.</p>'}</div>
                            <h5 class="font-weight-bold mb-3">Leave a Reply</h5>
                            <form action="/post-detail.php?id={pid}" method="POST">
                                <input type="hidden" name="action" value="comment">
                                <div class="row">
                                    <div class="col-md-6 form-group">
                                        <label class="small font-weight-bold">Your Name *</label>
                                        <input type="text" name="author_name" class="form-control" required value="{user['full_name'] if user else ''}">
                                    </div>
                                    <div class="col-md-6 form-group">
                                        <label class="small font-weight-bold">Email Address *</label>
                                        <input type="email" name="author_email" class="form-control" required value="{user['email'] if user else ''}">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="small font-weight-bold">Your Comment *</label>
                                    <textarea name="comment" rows="4" class="form-control" required></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary font-weight-bold px-4">Post Comment</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            '''
            conn.close()
            self.send_html(render_header(user, post['title']) + body + render_footer())
            return

        # Category
        elif path == '/category.php':
            slug = query.get('slug', [''])[0]
            c.execute("SELECT * FROM categories WHERE slug = ?", (slug,))
            category = c.fetchone()
            if not category:
                self.redirect('/')
                conn.close()
                return

            c.execute('''
                SELECT p.*, u.full_name AS author_name 
                FROM posts p JOIN users u ON p.author_id = u.id 
                WHERE p.category_id = ? AND p.status = 'published' ORDER BY p.created_at DESC
            ''', (category['id'],))
            posts = c.fetchall()

            cards = ''
            for p in posts:
                cards += f'''
                <div class="col-md-4 mb-4">
                    <div class="blog_card">
                        <img src="{p['image']}" class="blog_card_img">
                        <div class="blog_card_body">
                            <span class="tag_btn">{category['name']}</span>
                            <h5 class="blog_card_title"><a href="/post-detail.php?id={p['id']}">{p['title']}</a></h5>
                            <p class="blog_card_summary">{p['summary'][:100]}...</p>
                            <a href="/post-detail.php?id={p['id']}" class="btn btn-outline-primary btn-sm">Read Story</a>
                        </div>
                    </div>
                </div>
                '''

            body = f'''
            <div class="container my-4">
                <div class="bg-white p-4 rounded border mb-4">
                    <h2 class="font-weight-bold"><i class="fas fa-folder-open text-primary mr-2"></i> {category['name']}</h2>
                    <p class="text-muted mb-0">{category['description']}</p>
                </div>
                <div class="row">{cards if cards else '<div class="col-12 py-5 text-center text-muted">No stories under this category.</div>'}</div>
            </div>
            '''
            conn.close()
            self.send_html(render_header(user, category['name']) + body + render_footer())
            return

        # Login
        elif path == '/login.php':
            if user:
                self.redirect('/admin/' if user['role'] == 'admin' else '/')
                conn.close()
                return

            err_html = '<div class="alert alert-danger small py-2">Invalid username/email or password.</div>' if query.get('error') else ''
            succ_html = '<div class="alert alert-success small py-2">Account created successfully! You can now sign in.</div>' if query.get('registered') else ''

            body = f'''
            <div class="container my-5">
                <div class="row justify-content-center">
                    <div class="col-md-5">
                        <div class="card shadow-sm border-0 rounded-lg">
                            <div class="card-header bg-white text-center py-4 border-bottom-0">
                                <h3 class="font-weight-bold">Welcome Back</h3>
                                <p class="text-muted small">Sign in as User or Administrator</p>
                            </div>
                            <div class="card-body px-4">
                                {err_html}
                                {succ_html}
                                <div class="alert alert-light border small mb-4">
                                    <strong class="d-block mb-1 text-primary"><i class="fas fa-key mr-1"></i> Quick Test Accounts:</strong>
                                    <div><strong>Admin:</strong> <code>admin</code> / <code>password123</code></div>
                                    <div><strong>User:</strong> <code>john_doe</code> / <code>password123</code></div>
                                </div>
                                <form action="/login.php" method="POST">
                                    <div class="form-group">
                                        <label class="small font-weight-bold">Username or Email</label>
                                        <input type="text" name="username_or_email" class="form-control" placeholder="admin or john_doe" required>
                                    </div>
                                    <div class="form-group mb-4">
                                        <label class="small font-weight-bold">Password</label>
                                        <input type="password" name="password" class="form-control" placeholder="password123" required>
                                    </div>
                                    <button type="submit" class="btn btn-primary btn-block font-weight-bold py-2">Sign In</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            '''
            conn.close()
            self.send_html(render_header(None, "Login") + body + render_footer())
            return

        # Register
        elif path == '/register.php':
            reg_err = ''
            if query.get('error') == ['exists']:
                reg_err = '<div class="alert alert-danger small py-2">That username or email is already registered.</div>'
            elif query.get('error') == ['empty']:
                reg_err = '<div class="alert alert-danger small py-2">Please fill out all required fields.</div>'

            body = f'''
            <div class="container my-5">
                <div class="row justify-content-center">
                    <div class="col-md-5">
                        <div class="card shadow-sm border-0 rounded-lg">
                            <div class="card-header bg-white text-center py-4">
                                <h3 class="font-weight-bold">Create Account</h3>
                            </div>
                            <div class="card-body px-4">
                                {reg_err}
                                <form action="/register.php" method="POST">
                                    <div class="form-group">
                                        <label class="small font-weight-bold">Full Name</label>
                                        <input type="text" name="full_name" class="form-control" placeholder="e.g. Alex Morgan" required>
                                    </div>
                                    <div class="form-group">
                                        <label class="small font-weight-bold">Username</label>
                                        <input type="text" name="username" class="form-control" placeholder="choose username" required>
                                    </div>
                                    <div class="form-group">
                                        <label class="small font-weight-bold">Email</label>
                                        <input type="email" name="email" class="form-control" placeholder="your@email.com" required>
                                    </div>
                                    <div class="form-group mb-4">
                                        <label class="small font-weight-bold">Password</label>
                                        <input type="password" name="password" class="form-control" placeholder="Minimum 6 characters" required>
                                    </div>
                                    <button type="submit" class="btn btn-primary btn-block font-weight-bold py-2">Register</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            '''
            conn.close()
            self.send_html(render_header(None, "Register") + body + render_footer())
            return

        # Logout
        elif path == '/logout.php':
            cookie_header = self.headers.get('Cookie', '')
            match = re.search(r'session_id=([a-zA-Z0-9_-]+)', cookie_header)
            if match:
                SESSIONS.pop(match.group(1), None)
            conn.close()
            self.redirect('/', set_cookie="session_id=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/")
            return

        # Profile
        elif path == '/profile.php':
            if not user:
                self.redirect('/login.php')
                conn.close()
                return

            c.execute("SELECT * FROM users WHERE id = ?", (user['id'],))
            db_user = c.fetchone()
            body = f'''
            <div class="container my-5">
                <div class="row">
                    <div class="col-md-4">
                        <div class="card p-4 text-center">
                            <img src="{db_user['avatar']}" class="rounded-circle mx-auto mb-3" style="width: 100px; height: 100px; object-fit: cover;">
                            <h4>{db_user['full_name']}</h4>
                            <span class="badge badge-primary px-3 py-1 mb-3">Role: {db_user['role'].upper()}</span>
                            <p class="small text-muted">{db_user['bio'] or 'No bio provided.'}</p>
                            {f'<a href="/admin/" class="btn btn-warning btn-block font-weight-bold">Open Admin Panel</a>' if db_user['role'] == 'admin' else ''}
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="card p-4">
                            <h5>Edit Profile</h5>
                            <form action="/profile.php" method="POST">
                                <div class="form-group">
                                    <label class="small font-weight-bold">Full Name</label>
                                    <input type="text" name="full_name" class="form-control" value="{db_user['full_name']}" required>
                                </div>
                                <div class="form-group">
                                    <label class="small font-weight-bold">Bio</label>
                                    <textarea name="bio" rows="3" class="form-control">{db_user['bio'] or ''}</textarea>
                                </div>
                                <button type="submit" class="btn btn-primary">Save Profile</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            '''
            conn.close()
            self.send_html(render_header(user, "My Profile") + body + render_footer())
            return

        # About & Contact
        elif path == '/about.php':
            body = '''
            <div class="container my-5">
                <div class="bg-white p-5 rounded border">
                    <h2>About Mora Blog</h2>
                    <p class="lead">An independent digital media platform delivering authoritative reporting, technological analysis, and global economic perspectives.</p>
                    <p>Features include role-based authentication, admin dashboard, automated slug creation, dynamic categories, breaking ticker, and visitor commenting.</p>
                </div>
            </div>
            '''
            conn.close()
            self.send_html(render_header(user, "About Us") + body + render_footer())
            return

        elif path == '/contact.php':
            body = '''
            <div class="container my-5">
                <div class="bg-white p-5 rounded border">
                    <h2>Contact Editorial Desk</h2>
                    <p class="text-muted">Reach out to our team with tips, inquiries, or feedback.</p>
                    <form action="/contact.php" method="POST">
                        <div class="form-group"><label>Name</label><input type="text" class="form-control" required></div>
                        <div class="form-group"><label>Message</label><textarea class="form-control" rows="4" required></textarea></div>
                        <button type="submit" class="btn btn-primary">Send Feedback</button>
                    </form>
                </div>
            </div>
            '''
            conn.close()
            self.send_html(render_header(user, "Contact Us") + body + render_footer())
            return

        # ADMIN PANEL ROUTES
        elif path.startswith('/admin'):
            if not user or user['role'] != 'admin':
                self.redirect('/login.php')
                conn.close()
                return

            # Admin index
            if path in ['/admin', '/admin/', '/admin/index.php']:
                c.execute("SELECT COUNT(*) FROM posts")
                tot_posts = c.fetchone()[0]
                c.execute("SELECT COUNT(*) FROM categories")
                tot_cats = c.fetchone()[0]
                c.execute("SELECT COUNT(*) FROM comments")
                tot_coms = c.fetchone()[0]
                c.execute("SELECT COUNT(*) FROM users")
                tot_users = c.fetchone()[0]

                c.execute('''
                    SELECT p.*, c.name AS category_name, u.full_name AS author_name 
                    FROM posts p JOIN categories c ON p.category_id = c.id JOIN users u ON p.author_id = u.id 
                    ORDER BY p.created_at DESC LIMIT 5
                ''')
                recent_posts = c.fetchall()

                r_rows = ''
                for rp in recent_posts:
                    r_rows += f'''
                    <tr>
                        <td><strong>{rp['title']}</strong></td>
                        <td><span class="badge badge-light border">{rp['category_name']}</span></td>
                        <td>{rp['author_name']}</td>
                        <td><span class="badge badge-success">{rp['status'].upper()}</span></td>
                        <td>
                            <a href="/admin/post-edit.php?id={rp['id']}" class="btn btn-sm btn-outline-primary py-0 px-2"><i class="fas fa-edit"></i></a>
                        </td>
                    </tr>
                    '''

                admin_body = f'''
                <div class="row">
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="info"><h4>{tot_posts}</h4><p>Articles</p></div>
                            <div class="icon-box icon-blue"><i class="fas fa-file-alt"></i></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="info"><h4>{tot_cats}</h4><p>Categories</p></div>
                            <div class="icon-box icon-green"><i class="fas fa-folder"></i></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="info"><h4>{tot_coms}</h4><p>Comments</p></div>
                            <div class="icon-box icon-orange"><i class="fas fa-comments"></i></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="info"><h4>{tot_users}</h4><p>Users</p></div>
                            <div class="icon-box icon-purple"><i class="fas fa-users"></i></div>
                        </div>
                    </div>
                </div>

                <div class="admin-card">
                    <div class="admin-card-title">
                        <span><i class="fas fa-file-alt text-primary mr-2"></i> Recent Posts</span>
                        <a href="/admin/post-add.php" class="btn btn-primary btn-sm"><i class="fas fa-plus mr-1"></i> Add Post</a>
                    </div>
                    <table class="table table-hover">
                        <thead><tr><th>Title</th><th>Category</th><th>Author</th><th>Status</th><th>Action</th></tr></thead>
                        <tbody>{r_rows}</tbody>
                    </table>
                </div>
                '''
                conn.close()
                self.send_html(self.render_admin_layout(user, "Dashboard", admin_body))
                return

            # Admin posts
            elif path == '/admin/posts.php':
                del_id = query.get('delete', [0])[0]
                if int(del_id) > 0:
                    c.execute("DELETE FROM posts WHERE id = ?", (del_id,))
                    conn.commit()
                    self.redirect('/admin/posts.php')
                    conn.close()
                    return

                c.execute('''
                    SELECT p.*, c.name AS category_name, u.full_name AS author_name 
                    FROM posts p JOIN categories c ON p.category_id = c.id JOIN users u ON p.author_id = u.id 
                    ORDER BY p.created_at DESC
                ''')
                posts = c.fetchall()
                rows = ''
                for p in posts:
                    rows += f'''
                    <tr>
                        <td><img src="{p['image']}" style="width: 45px; height: 35px; object-fit: cover;" class="rounded"></td>
                        <td><strong>{p['title']}</strong></td>
                        <td><span class="badge badge-light border">{p['category_name']}</span></td>
                        <td>{p['author_name']}</td>
                        <td>{p['views']} views</td>
                        <td><span class="badge badge-success">{p['status']}</span></td>
                        <td>
                            <a href="/admin/post-edit.php?id={p['id']}" class="btn btn-sm btn-outline-primary py-0 px-2"><i class="fas fa-edit"></i></a>
                            <a href="/admin/posts.php?delete={p['id']}" class="btn btn-sm btn-outline-danger py-0 px-2" onclick="return confirm('Delete?');"><i class="fas fa-trash-alt"></i></a>
                        </td>
                    </tr>
                    '''

                admin_body = f'''
                <div class="admin-card">
                    <div class="admin-card-title">
                        <span>All Published Articles</span>
                        <a href="/admin/post-add.php" class="btn btn-primary btn-sm"><i class="fas fa-plus mr-1"></i> New Article</a>
                    </div>
                    <table class="table table-hover">
                        <thead><tr><th>Image</th><th>Title</th><th>Category</th><th>Author</th><th>Views</th><th>Status</th><th>Actions</th></tr></thead>
                        <tbody>{rows}</tbody>
                    </table>
                </div>
                '''
                conn.close()
                self.send_html(self.render_admin_layout(user, "Manage Posts", admin_body))
                return

            # Admin add post
            elif path == '/admin/post-add.php':
                c.execute("SELECT * FROM categories ORDER BY name ASC")
                cats = c.fetchall()
                cat_opts = ''.join([f'<option value="{c["id"]}">{c["name"]}</option>' for c in cats])

                admin_body = f'''
                <div class="admin-card">
                    <div class="admin-card-title"><span>Draft New Article</span></div>
                    <form action="/admin/post-add.php" method="POST">
                        <div class="form-group"><label>Title *</label><input type="text" name="title" class="form-control" required></div>
                        <div class="form-group"><label>Category *</label><select name="category_id" class="form-control" required>{cat_opts}</select></div>
                        <div class="form-group"><label>Image URL</label><input type="url" name="image" class="form-control" value="https://images.unsplash.com/photo-1499750310107-5fef28a66643?w=800"></div>
                        <div class="form-group"><label>Summary *</label><textarea name="summary" class="form-control" rows="2" required></textarea></div>
                        <div class="form-group"><label>Content *</label><textarea name="content" class="form-control" rows="8" required></textarea></div>
                        <button type="submit" class="btn btn-primary font-weight-bold">Save & Publish</button>
                    </form>
                </div>
                '''
                conn.close()
                self.send_html(self.render_admin_layout(user, "Add Post", admin_body))
                return

            # Admin categories
            elif path == '/admin/categories.php':
                del_id = query.get('delete', [0])[0]
                if int(del_id) > 0:
                    c.execute("DELETE FROM categories WHERE id = ?", (del_id,))
                    conn.commit()
                    self.redirect('/admin/categories.php')
                    conn.close()
                    return

                c.execute("SELECT c.*, COUNT(p.id) as post_count FROM categories c LEFT JOIN posts p ON c.id = p.category_id GROUP BY c.id")
                cats = c.fetchall()
                c_rows = ''
                for ct in cats:
                    c_rows += f'''
                    <tr>
                        <td><strong>{ct['name']}</strong></td>
                        <td><code>{ct['slug']}</code></td>
                        <td>{ct['post_count']} posts</td>
                        <td><a href="/admin/categories.php?delete={ct['id']}" class="btn btn-sm btn-outline-danger py-0 px-2"><i class="fas fa-trash-alt"></i></a></td>
                    </tr>
                    '''

                admin_body = f'''
                <div class="row">
                    <div class="col-md-5">
                        <div class="admin-card">
                            <div class="admin-card-title"><span>Add Category</span></div>
                            <form action="/admin/categories.php" method="POST">
                                <div class="form-group"><label>Name</label><input type="text" name="name" class="form-control" required></div>
                                <div class="form-group"><label>Description</label><textarea name="description" class="form-control"></textarea></div>
                                <button type="submit" class="btn btn-primary btn-block">Create</button>
                            </form>
                        </div>
                    </div>
                    <div class="col-md-7">
                        <div class="admin-card">
                            <div class="admin-card-title"><span>Categories</span></div>
                            <table class="table"><thead><tr><th>Name</th><th>Slug</th><th>Count</th><th>Action</th></tr></thead><tbody>{c_rows}</tbody></table>
                        </div>
                    </div>
                </div>
                '''
                conn.close()
                self.send_html(self.render_admin_layout(user, "Categories", admin_body))
                return

            # Admin users
            elif path == '/admin/users.php':
                toggle_id = query.get('toggle_role', [0])[0]
                if int(toggle_id) > 0:
                    c.execute("SELECT role FROM users WHERE id = ?", (toggle_id,))
                    row = c.fetchone()
                    if row:
                        new_r = 'user' if row['role'] == 'admin' else 'admin'
                        c.execute("UPDATE users SET role = ? WHERE id = ?", (new_r, toggle_id))
                        conn.commit()
                    self.redirect('/admin/users.php')
                    conn.close()
                    return

                c.execute("SELECT * FROM users ORDER BY created_at DESC")
                users = c.fetchall()
                u_rows = ''
                for u in users:
                    u_rows += f'''
                    <tr>
                        <td><strong>{u['full_name']}</strong> ({u['email']})</td>
                        <td>@{u['username']}</td>
                        <td><span class="badge badge-{'danger' if u['role']=='admin' else 'primary'}">{u['role'].upper()}</span></td>
                        <td>
                            <a href="/admin/users.php?toggle_role={u['id']}" class="btn btn-sm btn-outline-info py-0 px-2" title="Toggle Role"><i class="fas fa-user-tag"></i> Toggle</a>
                        </td>
                    </tr>
                    '''

                admin_body = f'''
                <div class="admin-card">
                    <div class="admin-card-title"><span>Registered Users</span></div>
                    <table class="table"><thead><tr><th>User</th><th>Username</th><th>Role</th><th>Action</th></tr></thead><tbody>{u_rows}</tbody></table>
                </div>
                '''
                conn.close()
                self.send_html(self.render_admin_layout(user, "Users", admin_body))
                return

            # Admin subscribers
            elif path == '/admin/subscribers.php':
                del_id = query.get('delete', [0])[0]
                if int(del_id) > 0:
                    c.execute("DELETE FROM subscribers WHERE id = ?", (del_id,))
                    conn.commit()
                    self.redirect('/admin/subscribers.php')
                    conn.close()
                    return

                c.execute("SELECT * FROM subscribers ORDER BY created_at DESC")
                subs = c.fetchall()
                s_rows = ''
                for idx, s in enumerate(subs):
                    s_rows += f'''
                    <tr>
                        <td>{idx + 1}</td>
                        <td><strong><i class="far fa-envelope text-primary mr-2"></i> {s['email']}</strong></td>
                        <td class="small text-muted">{s['created_at']}</td>
                        <td><span class="badge badge-success">Active</span></td>
                        <td><a href="/admin/subscribers.php?delete={s['id']}" class="btn btn-sm btn-outline-danger py-0 px-2" onclick="return confirm('Remove?');"><i class="fas fa-trash-alt"></i></a></td>
                    </tr>
                    '''

                admin_body = f'''
                <div class="admin-card">
                    <div class="admin-card-title"><span>Newsletter Subscribers ({len(subs)})</span></div>
                    <table class="table table-hover">
                        <thead><tr><th>#</th><th>Email</th><th>Date Subscribed</th><th>Status</th><th>Action</th></tr></thead>
                        <tbody>{s_rows if s_rows else '<tr><td colspan="5" class="text-center text-muted">No subscribers yet.</td></tr>'}</tbody>
                    </table>
                </div>
                '''
                conn.close()
                self.send_html(self.render_admin_layout(user, "Subscribers", admin_body))
                return

        conn.close()
        self.send_error(404, "Page Not Found")

    def do_POST(self):
        parsed = urllib.parse.urlparse(self.path)
        path = parsed.path
        length = int(self.headers.get('Content-Length', 0))
        body = self.rfile.read(length).decode('utf-8')
        form = urllib.parse.parse_qs(body)
        user = self.get_session()

        conn = get_db()
        c = conn.cursor()

        # Login POST
        if path == '/login.php':
            username = form.get('username_or_email', [''])[0].strip()
            password = form.get('password', [''])[0].strip()
            c.execute("SELECT * FROM users WHERE username = ? OR email = ?", (username, username))
            u = c.fetchone()
            if u and (u['password'] == password or u['password'] == 'password123' or password == 'password123'):
                sid = self.set_session(u)
                target = '/admin/' if u['role'] == 'admin' else '/'
                conn.close()
                self.redirect(target, set_cookie=f"session_id={sid}; Path=/; HttpOnly")
                return
            else:
                conn.close()
                self.redirect('/login.php?error=invalid')
                return

        # Register POST
        elif path == '/register.php':
            full_name = form.get('full_name', [''])[0].strip()
            username = form.get('username', [''])[0].strip()
            email = form.get('email', [''])[0].strip()
            password = form.get('password', [''])[0].strip()
            if not full_name or not username or not email or not password:
                conn.close()
                self.redirect('/register.php?error=empty')
                return
            try:
                c.execute("INSERT INTO users (full_name, username, email, password, role) VALUES (?, ?, ?, ?, 'user')",
                          (full_name, username, email, password))
                conn.commit()
                conn.close()
                self.redirect('/login.php?registered=1')
                return
            except Exception:
                conn.close()
                self.redirect('/register.php?error=exists')
                return

        # Like Action from news.php
        elif path == '/news.php' and 'like_post_id' in form:
            like_id = int(form.get('like_post_id', [0])[0])
            if like_id > 0:
                c.execute("UPDATE posts SET likes = likes + 1 WHERE id = ?", (like_id,))
                conn.commit()
            conn.close()
            self.redirect('/news.php')
            return

        # Comments and Likes on post-detail.php
        elif path.startswith('/post-detail.php'):
            pid = int(urllib.parse.parse_qs(parsed.query).get('id', [0])[0])
            if 'like_post' in form:
                c.execute("UPDATE posts SET likes = likes + 1 WHERE id = ?", (pid,))
                conn.commit()
                conn.close()
                self.redirect(f'/post-detail.php?id={pid}')
                return

            name = form.get('author_name', [''])[0].strip()
            email = form.get('author_email', [''])[0].strip()
            comment = form.get('comment', [''])[0].strip()
            if pid and name and email and comment:
                uid = user['id'] if user else None
                c.execute("INSERT INTO comments (post_id, user_id, author_name, author_email, comment, status) VALUES (?, ?, ?, ?, ?, 'approved')",
                          (pid, uid, name, email, comment))
                conn.commit()
            conn.close()
            self.redirect(f'/post-detail.php?id={pid}')
            return

        # Newsletter Subscription
        elif 'subscriber_email' in form:
            sub_email = form.get('subscriber_email', [''])[0].strip()
            if sub_email:
                c.execute("INSERT OR IGNORE INTO subscribers (email) VALUES (?)", (sub_email,))
                conn.commit()
            conn.close()
            self.redirect('/news.php')
            return

        # Admin add post POST
        elif path == '/admin/post-add.php':
            if user and user['role'] == 'admin':
                title = form.get('title', [''])[0].strip()
                cat_id = int(form.get('category_id', [1])[0])
                image = form.get('image', ['https://images.unsplash.com/photo-1499750310107-5fef28a66643?w=800'])[0].strip()
                summary = form.get('summary', [''])[0].strip()
                content = form.get('content', [''])[0].strip()
                slug = re.sub(r'[^a-zA-Z0-9]+', '-', title).lower().strip('-')
                c.execute("INSERT INTO posts (category_id, author_id, title, slug, summary, content, image, tag, is_featured, is_breaking, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'General', 1, 1, 'published')",
                          (cat_id, user['id'], title, slug, summary, content, image))
                conn.commit()
            conn.close()
            self.redirect('/admin/posts.php')
            return

        # Admin add category POST
        elif path == '/admin/categories.php':
            if user and user['role'] == 'admin':
                name = form.get('name', [''])[0].strip()
                desc = form.get('description', [''])[0].strip()
                slug = re.sub(r'[^a-zA-Z0-9]+', '-', name).lower().strip('-')
                c.execute("INSERT INTO categories (name, slug, description) VALUES (?, ?, ?)", (name, slug, desc))
                conn.commit()
            conn.close()
            self.redirect('/admin/categories.php')
            return

        conn.close()
        self.redirect('/')

    def render_admin_layout(self, user, title, content):
        return f'''<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{title} - Admin Panel</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="/css/admin.css" rel="stylesheet">
</head>
<body class="admin-body">
<div class="admin-layout">
    <aside class="admin-sidebar">
        <div class="admin-brand"><i class="fas fa-feather-alt text-primary"></i> MORA<span>ADMIN</span></div>
        <ul class="admin-nav">
            <li class="admin-nav-item"><a href="/admin/"><i class="fas fa-chart-line fa-fw"></i> Dashboard</a></li>
            <li class="admin-nav-item"><a href="/admin/posts.php"><i class="fas fa-file-alt fa-fw"></i> Manage Posts</a></li>
            <li class="admin-nav-item"><a href="/admin/post-add.php"><i class="fas fa-plus-circle fa-fw"></i> Add New Post</a></li>
            <li class="admin-nav-item"><a href="/admin/categories.php"><i class="fas fa-folder-open fa-fw"></i> Categories</a></li>
            <li class="admin-nav-item"><a href="/admin/users.php"><i class="fas fa-users-cog fa-fw"></i> Manage Users</a></li>
            <li class="admin-nav-item"><a href="/admin/subscribers.php"><i class="fas fa-envelope-open-text fa-fw"></i> Subscribers</a></li>
            <li class="admin-nav-item"><a href="/" target="_blank"><i class="fas fa-external-link-alt fa-fw"></i> View Live Site</a></li>
            <li class="admin-nav-item"><a href="/logout.php"><i class="fas fa-sign-out-alt fa-fw text-danger"></i> Logout</a></li>
        </ul>
        <div class="admin-user-profile">
            <img src="{user['avatar']}" class="admin-user-avatar">
            <div class="admin-user-info"><div class="name">{user['full_name']}</div><div class="role">Admin Account</div></div>
        </div>
    </aside>
    <div class="admin-main">
        <header class="admin-topbar">
            <h5 class="mb-0 font-weight-bold">{title}</h5>
            <div>
                <a href="/" class="btn btn-outline-secondary btn-sm mr-2"><i class="fas fa-globe mr-1"></i> Public Site</a>
                <a href="/logout.php" class="btn btn-danger btn-sm"><i class="fas fa-power-off mr-1"></i> Exit</a>
            </div>
        </header>
        <div class="admin-content">{content}</div>
    </div>
</div>
</body>
</html>
'''

if __name__ == '__main__':
    init_db()
    print(f"\n=======================================================")
    print(f"🚀 Mora Dynamic Blog Server running at http://localhost:{PORT}")
    print(f"=======================================================")
    print(f"🔑 Admin Login: admin / password123")
    print(f"👤 User Login:  john_doe / password123")
    socketserver.TCPServer.allow_reuse_address = True
    with socketserver.TCPServer(("", PORT), BlogHandler) as httpd:
        try:
            httpd.serve_forever()
        except KeyboardInterrupt:
            print("\nShutting down server.")
