-- ============================================================
-- SCHEMA DOCUMENTATION: TP_BD - Social Network Application
-- ============================================================
-- Architecture: Hybrid Database (MariaDB + MongoDB + Redis)
-- Updated: 2025-12-19
-- ============================================================
--
-- DATABASE LAYOUT:
--   MariaDB:  users, sessions, password_reset_tokens, friends,
--             conversations, conversation_user, groups, group_user,
--             pages, page_followers, cache, cache_locks, jobs, etc.
--   MongoDB:  posts, comments, reactions, messages, notifications,
--             sessions_audit
--   Redis:    Post counters cache (likes_count, comments_count)
--
-- ============================================================

-- ============================================================
--                      MARIADB TABLES
-- ============================================================

-- ------------------------------------------------------------
-- TABLE users
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    nom VARCHAR(100) NULL,
    prenom VARCHAR(100) NULL,
    date_naissance DATE NULL,
    genre ENUM('homme', 'femme', 'autre') DEFAULT 'autre',
    bio TEXT NULL,
    profile_photo VARCHAR(255) NULL,
    cover_photo VARCHAR(255) NULL,
    is_active TINYINT(1) DEFAULT 1,
    remember_token VARCHAR(100) NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    INDEX idx_users_created_at (created_at),
    FULLTEXT KEY ft_users_name (nom, prenom, username)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- TABLE password_reset_tokens
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS password_reset_tokens (
    email VARCHAR(255) NOT NULL PRIMARY KEY,
    token VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- TABLE sessions (Laravel session storage)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS sessions (
    id VARCHAR(255) NOT NULL PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    payload LONGTEXT NOT NULL,
    last_activity INT NOT NULL,
    INDEX idx_sessions_user_id (user_id),
    INDEX idx_sessions_last_activity (last_activity)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- TABLE friends (composite primary key)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS friends (
    user_id_1 BIGINT UNSIGNED NOT NULL,
    user_id_2 BIGINT UNSIGNED NOT NULL,
    status ENUM(
        'pending',
        'accepted',
        'rejected',
        'blocked'
    ) DEFAULT 'pending',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    accepted_at DATETIME NULL,
    PRIMARY KEY (user_id_1, user_id_2),
    CONSTRAINT fk_friends_user_1 FOREIGN KEY (user_id_1) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_friends_user_2 FOREIGN KEY (user_id_2) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT check_users_diff CHECK (user_id_1 <> user_id_2),
    INDEX idx_friends_user_1 (user_id_1),
    INDEX idx_friends_user_2 (user_id_2)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- TABLE conversations
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS conversations (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    type ENUM('private', 'group') DEFAULT 'private',
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- TABLE conversation_user (pivot)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS conversation_user (
    conversation_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    joined_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    role ENUM('member', 'admin') DEFAULT 'member',
    PRIMARY KEY (conversation_id, user_id),
    CONSTRAINT fk_conv_user_conv FOREIGN KEY (conversation_id) REFERENCES conversations (id) ON DELETE CASCADE,
    CONSTRAINT fk_conv_user_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- TABLE groups
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS groups (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    owner_id BIGINT UNSIGNED NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    avatar VARCHAR(255) NULL,
    visibility ENUM('public', 'private') DEFAULT 'public',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_groups_owner FOREIGN KEY (owner_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- TABLE group_user (pivot)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS group_user (
    group_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    role ENUM(
        'member',
        'moderator',
        'admin'
    ) DEFAULT 'member',
    joined_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (group_id, user_id),
    CONSTRAINT fk_gu_group FOREIGN KEY (group_id) REFERENCES groups (id) ON DELETE CASCADE,
    CONSTRAINT fk_gu_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- TABLE pages
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS pages (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    category VARCHAR(100) NULL,
    avatar VARCHAR(255) NULL,
    owner_id BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_pages_owner FOREIGN KEY (owner_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- TABLE page_followers (pivot)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS page_followers (
    page_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    notifications_on TINYINT(1) DEFAULT 1,
    followed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (page_id, user_id),
    CONSTRAINT fk_pf_page FOREIGN KEY (page_id) REFERENCES pages (id) ON DELETE CASCADE,
    CONSTRAINT fk_pf_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- TABLE cache (Laravel cache)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS cache (
    `key` VARCHAR(255) NOT NULL PRIMARY KEY,
    value MEDIUMTEXT NOT NULL,
    expiration INT NOT NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- TABLE cache_locks (Laravel cache locks)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS cache_locks (
    `key` VARCHAR(255) NOT NULL PRIMARY KEY,
    owner VARCHAR(255) NOT NULL,
    expiration INT NOT NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ============================================================
--                     MONGODB COLLECTIONS
-- ============================================================
-- The following collections are stored in MongoDB (database: social_network_data)
-- They are documented here as pseudo-SQL for reference.
-- Actual schema is managed via Laravel migrations with mongodb connection.
-- ============================================================

-- ------------------------------------------------------------
-- COLLECTION posts (MongoDB)
-- Relationship: user_id -> users.id (MariaDB, cross-database)
-- ------------------------------------------------------------
/*
{
_id: ObjectId,
user_id: Number,                    -- FK to users.id (MariaDB)
content: String | null,
media_url: String | null,
media_type: "image"|"video"|"audio"|"link"|"none",
visibility: "public"|"friends"|"private",
likes_count: Number,                -- Cached, also in Redis
comments_count: Number,             -- Cached, also in Redis
created_at: ISODate,
updated_at: ISODate,
deleted_at: ISODate | null
}
Indexes:
- idx_post_user_created (user_id, created_at)
- idx_post_created (created_at)
- ft_posts_content (content: text)
*/

-- ------------------------------------------------------------
-- COLLECTION comments (MongoDB)
-- Relationships:
--   post_id -> posts._id (MongoDB)
--   user_id -> users.id (MariaDB, cross-database)
-- ------------------------------------------------------------
/*
{
_id: ObjectId,
post_id: ObjectId,                  -- FK to posts._id
user_id: Number,                    -- FK to users.id (MariaDB)
content: String,
created_at: ISODate,
updated_at: ISODate,
deleted_at: ISODate | null
}
Indexes:
- idx_comment_post_created (post_id, created_at)
*/

-- ------------------------------------------------------------
-- COLLECTION reactions (MongoDB)
-- Relationships:
--   post_id -> posts._id (MongoDB, nullable)
--   message_id -> messages._id (MongoDB, nullable)
--   user_id -> users.id (MariaDB, cross-database)
-- ------------------------------------------------------------
/*
{
_id: ObjectId,
post_id: ObjectId | null,           -- FK to posts._id
user_id: Number,                    -- FK to users.id (MariaDB)
message_id: ObjectId | null,        -- FK to messages._id
reaction_type: "like"|"love"|"haha"|"wow"|"sad"|"angry",
created_at: ISODate,
updated_at: ISODate
}
Indexes:
- ux_reaction_post_user (post_id, user_id) UNIQUE
- idx_reaction_post (post_id)
- idx_reaction_user (user_id)
- idx_reaction_message (message_id)
*/

-- ------------------------------------------------------------
-- COLLECTION messages (MongoDB)
-- Relationships:
--   conversation_id -> conversations.id (MariaDB, cross-database)
--   sender_id -> users.id (MariaDB, cross-database)
-- ------------------------------------------------------------
/*
{
_id: ObjectId,
conversation_id: Number,            -- FK to conversations.id (MariaDB)
sender_id: Number,                  -- FK to users.id (MariaDB)
body: String | null,
attachments: Array | null,          -- JSON: [{type: "image", url: "..."}]
is_read: Boolean,
created_at: ISODate,
updated_at: ISODate
}
Indexes:
- idx_messages_conv_created (conversation_id, created_at)
*/

-- ------------------------------------------------------------
-- COLLECTION notifications (MongoDB)
-- Relationships:
--   user_id -> users.id (MariaDB, cross-database)
--   actor_user_id -> users.id (MariaDB, cross-database)
-- ------------------------------------------------------------
/*
{
_id: ObjectId,
user_id: Number,                    -- FK to users.id (MariaDB)
type: String,                       -- e.g. "like", "comment", "friend_request"
actor_user_id: Number | null,       -- Who triggered the notification
object_type: String | null,         -- e.g. "post", "comment", "user"
object_id: Number | null,
data: Object | null,                -- Additional JSON data
is_read: Boolean,
created_at: ISODate
}
*/

-- ------------------------------------------------------------
-- COLLECTION sessions_audit (MongoDB)
-- Relationships:
--   user_id -> users.id (MariaDB, cross-database)
-- ------------------------------------------------------------
/*
{
_id: ObjectId,
user_id: Number | null,             -- FK to users.id (MariaDB)
session_token: String | null,
ip_address: String | null,
device: String | null,
user_agent: String | null,
login_at: ISODate,
logout_at: ISODate | null
}
*/

-- ============================================================
--                      REDIS CACHE KEYS
-- ============================================================
-- Redis is used for caching frequently accessed counters.
-- TTL: 3600 seconds (1 hour)
-- ============================================================
/*
Key patterns:
post:{id}:likes_count     -- Cached likes count for a post
post:{id}:comments_count  -- Cached comments count for a post

Operations handled by App\Models\Post:
- getCachedLikesCountAttribute()
- getCachedCommentsCountAttribute()
- incrementLikesCount() / decrementLikesCount()
- incrementCommentsCount() / decrementCommentsCount()
*/

-- ============================================================
--                      NOTES
-- ============================================================
--
-- 1. TRIGGERS REMOVED: The original schema had triggers for maintaining
--    likes_count and comments_count. These are now handled by:
--    - Eloquent model events (booted() in Comment.php, Reaction.php)
--    - Redis caching (Post.php)
--
-- 2. NAMING CHANGES from original schema.sql:
--    - publications -> posts (MongoDB)
--    - commentaires -> comments (MongoDB)
--    - amis -> friends (MariaDB)
--    - groupes -> groups (MariaDB)
--    - groupe_user -> group_user (MariaDB)
--
-- 3. HYBRID RELATIONSHIPS:
--    - MongoDB models use HybridRelations trait for cross-DB queries
--    - User model (MariaDB) can access posts, comments (MongoDB)
--    - Post model (MongoDB) can access user (MariaDB)
--
-- 4. CUSTOM ARTISAN COMMAND:
--    php artisan migrate:fresh-all
--    Drops both MariaDB tables AND MongoDB collections before migrating.
--
-- ============================================================