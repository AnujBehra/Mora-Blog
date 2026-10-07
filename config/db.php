<?php
/**
 * Database Connection Configuration
 * Project: Mora Blog
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database Credentials
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'mora_blog');
define('DB_USER', 'root');
define('DB_PASS', '');

// Base URL helper
define('SITE_NAME', 'Mora Blog');
define('SITE_URL', '/');

function getDBConnection() {
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    // Try MySQL first
    try {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $pdo;
    } catch (PDOException $e) {
        // MySQL connection failed; try SQLite file fallback so the app works seamlessly offline / out of the box
        $sqliteFile = __DIR__ . '/mora_blog.sqlite';
        try {
            $pdo = new PDO("sqlite:" . $sqliteFile);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            
            // Check if tables exist, if not initialize them from schema
            $check = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='users'");
            if (!$check->fetch()) {
                initializeSqliteFallback($pdo);
            }
            return $pdo;
        } catch (Exception $sqliteEx) {
            die("<div style='font-family:sans-serif;padding:30px;background:#fef2f2;border-left:5px solid #ef4444;color:#991b1b;margin:20px;border-radius:4px;'>
                <h3>Database Connection Notice</h3>
                <p>Could not connect to MySQL (<code>" . htmlspecialchars($e->getMessage()) . "</code>).</p>
                <p>Make sure MySQL is running in XAMPP/WAMP/MAMP or import <code>database.sql</code> into your MySQL server.</p>
            </div>");
        }
    }
}

function initializeSqliteFallback($pdo) {
    $sql = "
    CREATE TABLE users (
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

    CREATE TABLE categories (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        slug TEXT UNIQUE NOT NULL,
        description TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE posts (
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
        likes INTEGER DEFAULT 0,
        is_featured INTEGER DEFAULT 0,
        is_breaking INTEGER DEFAULT 0,
        status TEXT DEFAULT 'published',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE comments (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        post_id INTEGER NOT NULL,
        user_id INTEGER,
        author_name TEXT NOT NULL,
        author_email TEXT NOT NULL,
        comment TEXT NOT NULL,
        status TEXT DEFAULT 'approved',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE subscribers (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        email TEXT UNIQUE NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );
    ";
    $pdo->exec($sql);

    // Seed default admin and user (password: password123)
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password, full_name, role, bio) VALUES (?, ?, ?, ?, ?, ?)");
    $hash = password_hash('password123', PASSWORD_BCRYPT);
    $stmt->execute(['admin', 'admin@morablog.com', $hash, 'Editor-in-Chief', 'admin', 'Lead Editor and Administrator at Mora Blog.']);
    $stmt->execute(['john_doe', 'john@example.com', $hash, 'John Doe', 'user', 'Avid reader and tech enthusiast.']);

    // Seed Categories
    $pdo->exec("
        INSERT INTO categories (name, slug, description) VALUES
        ('Business', 'business', 'Global markets, investing, and enterprise news.'),
        ('Technology', 'technology', 'Latest updates in AI, software development, and digital gear.'),
        ('Travel', 'travel', 'Destinations, travel guides, and exploration narratives.'),
        ('Fashion', 'fashion', 'Trends, styling, and contemporary fashion culture.'),
        ('Finance', 'finance', 'Cryptocurrency, personal finance, and market analyses.');
    ");

    // Seed Posts
    $pdo->exec("
        INSERT INTO posts (category_id, author_id, title, slug, summary, content, image, tag, views, is_featured, is_breaking, status) VALUES
        (5, 1, 'Futures Firm Cboe Filed for 6 Bitcoin ETFs This Week', 'futures-firm-cboe-filed-for-6-bitcoin-etfs', 'Major futures operator Cboe submits fresh regulatory filings looking to greenlight digital currency funds.', 'Cryptocurrency adoption continues to accelerate across global institutions, prompting regulators to review investment custody and security protocols with greater urgency. Market analysts expect major institutional inflows should approvals proceed smoothly.', 'https://images.unsplash.com/photo-1621416894569-0f39ed31d247?w=1200', 'Investing', 1450, 1, 1, 'published'),
        (1, 1, 'Earned $9,000,000 per Year with a Modern Publishing Platform', 'earned-9000000-per-year-publishing-platform', 'How digital publications are redefining subscription and advertisement revenues in 2026.', 'The publishing industry is witnessing a structural shift toward direct audience relationships. By integrating dynamic content delivery and personalized newsletters, modern digital magazines are unlocking unprecedented monetization potential.', 'https://images.unsplash.com/photo-1460925895917-afdab827c52f?w=1200', 'Business', 980, 1, 1, 'published'),
        (1, 1, 'Inside High-Growth Ventures: Scaling Modern Digital Products', 'inside-high-growth-ventures-scaling-digital-products', 'Key methodologies founders apply when taking web applications from prototype to global scale.', 'Scaling an enterprise software platform requires relentless focus on architectural reliability, robust database indexing, and streamlined user interfaces.', 'https://images.unsplash.com/photo-1551836022-d5d88e9218df?w=800', 'Finance', 420, 0, 0, 'published'),
        (3, 2, 'Solo Travel Guide: Navigating Mountain Trails with Confidence', 'solo-travel-guide-navigating-mountain-trails', 'Essential strategies, packing lists, and safety precautions for remote wilderness adventures.', 'Embarking on solo adventures challenges personal limits and connects travellers deeply with nature. From lightweight packing essentials to offline navigational planning, preparation is the key to rewarding exploration.', 'https://images.unsplash.com/photo-1506744038136-46273834b3fb?w=800', 'Travel', 612, 0, 1, 'published'),
        (4, 1, 'Contemporary Minimalism: The Evolution of Sustainable Wardrobes', 'contemporary-minimalism-sustainable-wardrobes', 'How capsule collections and eco-conscious textiles are reshaping everyday fashion aesthetics.', 'Sustainable apparel has transitioned from an ethical niche to a mainstream aesthetic revolution. Designers are championing enduring silhouettes, recycled weaves, and timeless craftsmanship over rapid micro-trends.', 'https://images.unsplash.com/photo-1490481651871-ab68de25d43d?w=800', 'Fashion', 340, 0, 0, 'published'),
        (2, 1, 'Next-Generation Full-Stack Web Architecture in 2026', 'next-generation-full-stack-web-architecture-2026', 'A look at high-performance server-side rendering, secure PDO database patterns, and modular UI styling.', 'Dynamic web applications demand strict separation of concerns, secure parameterized SQL executions, and clean role-based authorization matrices.', 'https://images.unsplash.com/photo-1517694712202-14dd9538aa97?w=800', 'Tech', 890, 0, 0, 'published');
    ");

    // Seed Comments
    $pdo->exec("
        INSERT INTO comments (post_id, user_id, author_name, author_email, comment, status) VALUES
        (1, 2, 'John Doe', 'john@example.com', 'Fantastic analysis! The regulatory perspective clears up a lot of misconceptions.', 'approved'),
        (2, 1, 'Editorial Desk', 'admin@morablog.com', 'Thanks for reading! More in-depth business case studies coming later this week.', 'approved');
    ");
}

// Authentication Helpers
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function requireLogin() {
    if (!isLoggedIn()) {
        $_SESSION['flash_error'] = 'Please log in to continue.';
        header('Location: login.php');
        exit;
    }
}

function requireAdmin() {
    if (!isAdmin()) {
        $_SESSION['flash_error'] = 'Access denied. Administrator privileges required.';
        header('Location: ../login.php');
        exit;
    }
}

function sanitize($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}
