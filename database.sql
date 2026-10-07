-- ========================================================
-- Database Schema for Mora Blog Web Application
-- Project: Mora Blog with Dynamic Category and News Portal
-- ========================================================

CREATE DATABASE IF NOT EXISTS `mora_blog` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `mora_blog`;

DROP TABLE IF EXISTS `subscribers`;
DROP TABLE IF EXISTS `comments`;
DROP TABLE IF EXISTS `posts`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `users`;

-- 1. Users Table (Supports 'admin' and 'user' roles)
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(60) NOT NULL UNIQUE,
  `email` VARCHAR(120) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `full_name` VARCHAR(100) NOT NULL,
  `role` ENUM('admin', 'user') NOT NULL DEFAULT 'user',
  `avatar` VARCHAR(255) DEFAULT 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=150',
  `bio` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Categories Table
CREATE TABLE `categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(80) NOT NULL,
  `slug` VARCHAR(100) NOT NULL UNIQUE,
  `description` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Posts Table
CREATE TABLE `posts` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `category_id` INT NOT NULL,
  `author_id` INT NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL UNIQUE,
  `summary` TEXT NOT NULL,
  `content` LONGTEXT NOT NULL,
  `image` VARCHAR(500) DEFAULT 'https://images.unsplash.com/photo-1499750310107-5fef28a66643?w=800',
  `tag` VARCHAR(50) DEFAULT 'General',
  `views` INT DEFAULT 0,
  `likes` INT DEFAULT 0,
  `is_featured` TINYINT(1) DEFAULT 0,
  `is_breaking` TINYINT(1) DEFAULT 0,
  `status` ENUM('published', 'draft') DEFAULT 'published',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`author_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Comments Table
CREATE TABLE `comments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `post_id` INT NOT NULL,
  `user_id` INT DEFAULT NULL,
  `author_name` VARCHAR(100) NOT NULL,
  `author_email` VARCHAR(120) NOT NULL,
  `comment` TEXT NOT NULL,
  `status` ENUM('approved', 'pending') DEFAULT 'approved',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`post_id`) REFERENCES `posts`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Newsletter Subscribers Table
CREATE TABLE `subscribers` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `email` VARCHAR(120) NOT NULL UNIQUE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ========================================================
-- SEED DATA
-- Default Passwords: password123
-- ========================================================

INSERT INTO `users` (`id`, `username`, `email`, `password`, `full_name`, `role`, `bio`) VALUES
(1, 'admin', 'admin@morablog.com', '$2y$10$tZ2c69vGkL9R1FomZJkeNu7wK2Q1h7bU0m.L0Y9uKxGf9Pz7f2Oqy', 'Editor-in-Chief', 'admin', 'Lead Editor and Administrator at Mora Blog.'),
(2, 'john_doe', 'john@example.com', '$2y$10$tZ2c69vGkL9R1FomZJkeNu7wK2Q1h7bU0m.L0Y9uKxGf9Pz7f2Oqy', 'John Doe', 'user', 'Avid reader and tech enthusiast.');

INSERT INTO `categories` (`id`, `name`, `slug`, `description`) VALUES
(1, 'Business', 'business', 'Global markets, corporate innovation, and venture economics.'),
(2, 'Technology', 'technology', 'Artificial intelligence, web platforms, and cutting-edge software engineering.'),
(3, 'Travel', 'travel', 'Exploration narratives, destination guides, and cultural journeys.'),
(4, 'Fashion', 'fashion', 'Sustainable styling, contemporary aesthetics, and luxury trend analysis.'),
(5, 'Finance', 'finance', 'Cryptocurrency, digital asset funds, and personal wealth strategies.');

INSERT INTO `posts` (`id`, `category_id`, `author_id`, `title`, `slug`, `summary`, `content`, `image`, `tag`, `views`, `likes`, `is_featured`, `is_breaking`, `status`) VALUES
(1, 5, 1, 'Futures Firm Cboe Filed for 6 Bitcoin ETFs This Week', 'futures-firm-cboe-filed-for-6-bitcoin-etfs', 'Major futures operator Cboe submits fresh regulatory filings looking to greenlight digital currency funds.', 'Cryptocurrency adoption continues to accelerate across global institutions, prompting regulators to review investment custody and security protocols with greater urgency. Market analysts expect major institutional inflows should approvals proceed smoothly.\n\nInstitutional capital allocators have underscored the necessity of robust regulatory frameworks. As institutional custody solutions mature, trading volumes across derivative and spot pairings demonstrate significant resilience.', 'https://images.unsplash.com/photo-1621416894569-0f39ed31d247?w=1200', 'Investing', 1450, 48, 1, 1, 'published'),

(2, 1, 1, 'Earned $9,000,000 per Year with a Modern Publishing Platform', 'earned-9000000-per-year-publishing-platform', 'How digital publications are redefining subscription and advertisement revenues in 2026.', 'The publishing industry is witnessing a structural shift toward direct audience relationships. By integrating dynamic content delivery and personalized newsletters, modern digital magazines are unlocking unprecedented monetization potential.\n\nIn this investigative report, we deconstruct the revenue models powering top high-growth media houses. Through audience segmentation, subscription tiering, and proprietary editorial syndication, digital creators are outperforming legacy news conglomerates.', 'https://images.unsplash.com/photo-1460925895917-afdab827c52f?w=1200', 'Business', 980, 36, 1, 1, 'published'),

(3, 1, 1, 'Inside High-Growth Ventures: Scaling Modern Digital Products', 'inside-high-growth-ventures-scaling-digital-products', 'Key methodologies founders apply when taking web applications from prototype to global scale.', 'Scaling an enterprise software platform requires relentless focus on architectural reliability, robust database indexing, and streamlined user interfaces. When teams minimize unnecessary design baggage and standardize their workflows, development velocities improve markedly.\n\nEngineers must navigate bottlenecks in database queries, concurrency bottlenecks, and microservice decoupling while keeping customer experience responsive and friction-free.', 'https://images.unsplash.com/photo-1551836022-d5d88e9218df?w=800', 'Finance', 420, 29, 0, 0, 'published'),

(4, 3, 2, 'Solo Travel Guide: Navigating Mountain Trails with Confidence', 'solo-travel-guide-navigating-mountain-trails', 'Essential strategies, packing lists, and safety precautions for remote wilderness adventures.', 'Embarking on solo adventures challenges personal limits and connects travellers deeply with nature. From lightweight packing essentials to offline navigational planning, preparation is the key to rewarding exploration.\n\nWhether navigating alpine passes or coastal ridges, understanding weather patterns and maintaining contingency gear makes the difference between an exhausting ordeal and an unforgettable voyage.', 'https://images.unsplash.com/photo-1506744038136-46273834b3fb?w=800', 'Travel', 612, 54, 0, 1, 'published'),

(5, 4, 1, 'Contemporary Minimalism: The Evolution of Sustainable Wardrobes', 'contemporary-minimalism-sustainable-wardrobes', 'How capsule collections and eco-conscious textiles are reshaping everyday fashion aesthetics.', 'Sustainable apparel has transitioned from an ethical niche to a mainstream aesthetic revolution. Designers are championing enduring silhouettes, recycled weaves, and timeless craftsmanship over rapid micro-trends.\n\nConsumers are demanding verifiable supply chain provenance and low-impact textile dyeing techniques, reshaping the retail landscape worldwide.', 'https://images.unsplash.com/photo-1490481651871-ab68de25d43d?w=800', 'Fashion', 340, 22, 0, 0, 'published'),

(6, 2, 1, 'Next-Generation Full-Stack Web Architecture in 2026', 'next-generation-full-stack-web-architecture-2026', 'A look at high-performance server-side rendering, secure PDO database patterns, and modular UI styling.', 'Dynamic web applications demand strict separation of concerns, secure parameterized SQL executions, and clean role-based authorization matrices. By eliminating boilerplate bloat and focusing on core business logic, web engineers deliver resilient software on time.', 'https://images.unsplash.com/photo-1517694712202-14dd9538aa97?w=800', 'Tech', 890, 71, 0, 0, 'published'),

(7, 2, 1, 'Artificial Intelligence in Media: Transforming Content Operations', 'artificial-intelligence-media-content-operations', 'Examining how modern editorial desks utilize intelligent automation for tagging, summaries, and verification.', 'As news cycles compress, editorial intelligence tools assist journalists with rapid transcription, factual corroboration, and multi-language syndication without compromising editorial integrity.', 'https://images.unsplash.com/photo-1485827404703-89b55fcc595e?w=800', 'AI', 510, 31, 0, 1, 'published'),

(8, 3, 2, 'Top 10 Hidden Coastal Gems Across Southern Europe', 'top-10-hidden-coastal-gems-southern-europe', 'An insider guide to secluded bays, historic seaside villages, and off-the-beaten-path destinations.', 'Stepping away from crowded resort corridors reveals centuries-old fishing hamlets perched atop dramatic cliffs. Experience Mediterranean culture at its most authentic and serene.', 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?w=800', 'Destinations', 475, 43, 0, 0, 'published'),

(9, 5, 1, 'Understanding Decentralized Finance: Liquidity Pools & Risk', 'understanding-decentralized-finance-liquidity-pools', 'A comprehensive primer on automated market makers, smart contract audits, and portfolio safeguards.', 'Decentralized liquidity protocols offer algorithmic yields but demand rigorous security vigilance. Learn how leading treasuries calculate impermanent loss and assess protocol solvency.', 'https://images.unsplash.com/photo-1639762681485-074b7f938ba0?w=800', 'Crypto', 390, 18, 0, 0, 'published');

INSERT INTO `comments` (`id`, `post_id`, `user_id`, `author_name`, `author_email`, `comment`, `status`) VALUES
(1, 1, 2, 'John Doe', 'john@example.com', 'Fantastic analysis! The regulatory perspective clears up a lot of misconceptions.', 'approved'),
(2, 2, 1, 'Editorial Desk', 'admin@morablog.com', 'Thanks for reading! More in-depth business case studies coming later this week.', 'approved'),
(3, 6, 2, 'John Doe', 'john@example.com', 'The PDO parameterized queries architecture makes this dynamic app very fast and secure.', 'approved');

INSERT INTO `subscribers` (`id`, `email`) VALUES
(1, 'reader@example.com'),
(2, 'techinvestor@domain.com');
