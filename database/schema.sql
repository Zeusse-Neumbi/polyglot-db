-- ------------------------------------------------------------
-- Configuration recommandée pre-DDL (exécutée par l'admin si besoin)
-- (Non-exécuter si tu n'as pas les droits ou si tu veux garder defaults)
-- ------------------------------------------------------------
-- SET GLOBAL innodb_file_per_table = ON;
-- SET GLOBAL innodb_buffer_pool_size = 8G; -- adapter selon RAM
-- SET GLOBAL max_connections = 500;
-- SET GLOBAL innodb_flush_log_at_trx_commit = 2; -- pour perf (perdre 1s possible)
-- ------------------------------------------------------------

-- ------------------------------------------------------------
-- TABLE users (complet + index recherche)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    nom VARCHAR(100),
    prenom VARCHAR(100),
    date_naissance DATE,
    genre ENUM('homme', 'femme', 'autre') DEFAULT 'autre',
    bio TEXT,
    profile_photo VARCHAR(255),
    cover_photo VARCHAR(255),
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,
    INDEX idx_users_created_at (created_at),
    FULLTEXT KEY ft_users_name (nom, prenom, username)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- TABLE publications (posts) : partitionnée par HASH(user_id)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS publications (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    content TEXT,
    media_url VARCHAR(512) NULL,
    media_type ENUM(
        'image',
        'video',
        'audio',
        'link',
        'none'
    ) DEFAULT 'none',
    visibility ENUM(
        'public',
        'friends',
        'private'
    ) DEFAULT 'public',
    likes_count BIGINT UNSIGNED NOT NULL DEFAULT 0,
    comments_count BIGINT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,
    PRIMARY KEY (id, user_id), -- user_id included for partitioning and some engines
    KEY idx_pub_user_created (user_id, created_at),
    KEY idx_pub_created (created_at),
    FULLTEXT KEY ft_publications_content (content),
    CONSTRAINT fk_pub_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci
-- ajuster nombre partitions

-- ------------------------------------------------------------
-- TABLE commentaires : partitionnée sur publication_id via HASH
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS commentaires (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    publication_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    content TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,
    PRIMARY KEY (id, publication_id),
    KEY idx_comment_pub_created (publication_id, created_at),
    CONSTRAINT fk_comment_pub FOREIGN KEY (publication_id) REFERENCES publications (id) ON DELETE CASCADE,
    CONSTRAINT fk_comment_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci

-- ------------------------------------------------------------
-- TABLE reactions (likes / réactions)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS reactions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    publication_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    reaction_type ENUM(
        'like',
        'love',
        'haha',
        'wow',
        'sad',
        'angry'
    ) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id, publication_id),
    UNIQUE KEY ux_reaction_pub_user (publication_id, user_id),
    KEY idx_reaction_pub (publication_id),
    KEY idx_reaction_user (user_id),
    CONSTRAINT fk_reaction_pub FOREIGN KEY (publication_id) REFERENCES publications (id) ON DELETE CASCADE,
    CONSTRAINT fk_reaction_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci

-- ------------------------------------------------------------
-- TABLE amis (friendships)
-- Use canonical ordering to avoid duplicate pairs (user_a < user_b)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS amis (
    user_a BIGINT UNSIGNED NOT NULL,
    user_b BIGINT UNSIGNED NOT NULL,
    status ENUM(
        'pending',
        'accepted',
        'rejected',
        'blocked'
    ) NOT NULL DEFAULT 'pending',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    accepted_at DATETIME NULL,
    PRIMARY KEY (user_a, user_b),
    CHECK (user_a <> user_b),
    CONSTRAINT fk_amis_a FOREIGN KEY (user_a) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_amis_b FOREIGN KEY (user_b) REFERENCES users (id) ON DELETE CASCADE,
    INDEX idx_amis_user_a (user_a),
    INDEX idx_amis_user_b (user_b)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- TABLE conversations & messages (messagerie)
-- partition messages by conversation_id
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS conversations (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    type ENUM('private', 'group') DEFAULT 'private',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS conversation_user (
    conversation_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    joined_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    role ENUM('member', 'admin') DEFAULT 'member',
    PRIMARY KEY (conversation_id, user_id),
    CONSTRAINT fk_conv_user_conv FOREIGN KEY (conversation_id) REFERENCES conversations (id) ON DELETE CASCADE,
    CONSTRAINT fk_conv_user_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS messages (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    conversation_id BIGINT UNSIGNED NOT NULL,
    sender_id BIGINT UNSIGNED NOT NULL,
    body TEXT,
    attachments JSON NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id, conversation_id),
    KEY idx_messages_conv_created (conversation_id, created_at),
    CONSTRAINT fk_msg_conv FOREIGN KEY (conversation_id) REFERENCES conversations (id) ON DELETE CASCADE,
    CONSTRAINT fk_msg_sender FOREIGN KEY (sender_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci

-- ------------------------------------------------------------
-- TABLE groupes & pivot groupe_user
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS groupes (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    owner_id BIGINT UNSIGNED NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    avatar VARCHAR(255),
    visibility ENUM('public', 'private') DEFAULT 'public',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_group_owner FOREIGN KEY (owner_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS groupe_user (
    groupe_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    role ENUM(
        'member',
        'moderator',
        'admin'
    ) DEFAULT 'member',
    joined_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (groupe_id, user_id),
    CONSTRAINT fk_gu_group FOREIGN KEY (groupe_id) REFERENCES groupes (id) ON DELETE CASCADE,
    CONSTRAINT fk_gu_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- TABLE pages & abonnement_page
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS pages (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    category VARCHAR(100),
    avatar VARCHAR(255),
    owner_id BIGINT UNSIGNED,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_page_owner FOREIGN KEY (owner_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

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
-- TABLE notifications (persistantes)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS notifications (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL, -- destinataire
    type VARCHAR(50) NOT NULL,
    actor_user_id BIGINT UNSIGNED NULL, -- qui a déclenché l'action
    object_type VARCHAR(50) NULL, -- 'publication','commentaire','user', ...
    object_id BIGINT UNSIGNED NULL,
    data JSON NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_notification_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- TABLE sessions (audit)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS sessions_audit (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    session_token VARCHAR(255),
    ip_address VARCHAR(45),
    device VARCHAR(255),
    user_agent VARCHAR(1024),
    login_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    logout_at DATETIME NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- TRIGGERS : maintenir counts likes_count & comments_count
-- Note : utiliser avec précaution ; sur forte charge, préférer Redis
-- ------------------------------------------------------------

DELIMITER $$

-- On incrémente likes_count après insertion d'une réaction
CREATE TRIGGER IF NOT EXISTS trg_reaction_after_insert
AFTER INSERT ON reactions
FOR EACH ROW
BEGIN
    UPDATE publications SET likes_count = likes_count + 1 WHERE id = NEW.publication_id;
END$$

-- On décrémente likes_count après suppression d'une réaction
CREATE TRIGGER IF NOT EXISTS trg_reaction_after_delete
AFTER DELETE ON reactions
FOR EACH ROW
BEGIN
    UPDATE publications SET likes_count = GREATEST(likes_count - 1, 0) WHERE id = OLD.publication_id;
END$$

-- On incrémente comments_count après insertion d'un commentaire
CREATE TRIGGER IF NOT EXISTS trg_comment_after_insert
AFTER INSERT ON commentaires
FOR EACH ROW
BEGIN
    UPDATE publications SET comments_count = comments_count + 1 WHERE id = NEW.publication_id;
END$$

-- On décrémente comments_count après suppression d'un commentaire
CREATE TRIGGER IF NOT EXISTS trg_comment_after_delete
AFTER DELETE ON commentaires
FOR EACH ROW
BEGIN
    UPDATE publications SET comments_count = GREATEST(comments_count - 1, 0) WHERE id = OLD.publication_id;
END$$

DELIMITER;