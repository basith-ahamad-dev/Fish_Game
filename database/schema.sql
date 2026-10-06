-- DeepCatch — Fish Game platform schema
-- Compatible with MySQL 5.7+ and MariaDB 10.2+ (utf8mb4).
-- Import into an existing, selected database (phpMyAdmin → Import, or `mysql fish_game < schema.sql`).
-- Locally you can create one first with:
--   CREATE DATABASE fish_game CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS remember_tokens;
DROP TABLE IF EXISTS login_attempts;
DROP TABLE IF EXISTS user_badges;
DROP TABLE IF EXISTS badges;
DROP TABLE IF EXISTS game_rounds;
DROP TABLE IF EXISTS games;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------------
-- Players. Aggregate columns (xp, total_score, …) are denormalised for fast
-- dashboards and leaderboards; Admin → Leaderboard → "Recalculate" rebuilds them.
-- ---------------------------------------------------------------------------
CREATE TABLE users (
    id                 INT UNSIGNED NOT NULL AUTO_INCREMENT,
    username           VARCHAR(20)  NOT NULL,
    email              VARCHAR(120) NOT NULL,
    password           VARCHAR(255) NOT NULL,
    avatar             VARCHAR(255) NULL,
    bio                VARCHAR(160) NULL,
    role               ENUM('player','admin') NOT NULL DEFAULT 'player',
    status             ENUM('active','disabled') NOT NULL DEFAULT 'active',
    level              INT UNSIGNED NOT NULL DEFAULT 1,
    xp                 INT UNSIGNED NOT NULL DEFAULT 0,
    total_score        INT UNSIGNED NOT NULL DEFAULT 0,
    games_played       INT UNSIGNED NOT NULL DEFAULT 0,
    games_won          INT UNSIGNED NOT NULL DEFAULT 0,
    best_score         INT UNSIGNED NOT NULL DEFAULT 0,
    fish_caught        INT UNSIGNED NOT NULL DEFAULT 0,
    current_streak     INT UNSIGNED NOT NULL DEFAULT 0,
    best_streak        INT UNSIGNED NOT NULL DEFAULT 0,
    sound_enabled      TINYINT(1)   NOT NULL DEFAULT 1,
    animations_enabled TINYINT(1)   NOT NULL DEFAULT 1,
    theme              ENUM('dark','light') NOT NULL DEFAULT 'dark',
    last_login_at      DATETIME     NULL,
    created_at         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_username (username),
    UNIQUE KEY uq_users_email (email),
    KEY idx_users_total_score (status, total_score),
    KEY idx_users_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- One row per game session. Score/XP are written only by the server once the
-- final round is resolved — never taken from the browser.
-- ---------------------------------------------------------------------------
CREATE TABLE games (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id         INT UNSIGNED NOT NULL,
    difficulty      ENUM('easy','medium','hard') NOT NULL,
    status          ENUM('in_progress','completed') NOT NULL DEFAULT 'in_progress',
    result          ENUM('won','lost','abandoned') NULL,
    score           INT UNSIGNED NOT NULL DEFAULT 0,
    xp_earned       INT UNSIGNED NOT NULL DEFAULT 0,
    total_rounds    TINYINT UNSIGNED NOT NULL,
    rounds_played   TINYINT UNSIGNED NOT NULL DEFAULT 0,
    correct_answers TINYINT UNSIGNED NOT NULL DEFAULT 0,
    lives_left      TINYINT UNSIGNED NOT NULL,
    fish_caught     SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    rank_after      INT UNSIGNED NULL,
    game_data       TEXT NULL COMMENT 'JSON summary: level change, badges, personal best',
    started_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    played_at       DATETIME NULL COMMENT 'Completion time',
    PRIMARY KEY (id),
    KEY idx_games_user_played (user_id, status, played_at),
    KEY idx_games_played (status, played_at),
    KEY idx_games_difficulty (difficulty, status, played_at),
    CONSTRAINT fk_games_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Each picture served from the Fish API. The solution is stored here and never
-- sent to the browser until the round is answered. Timing uses server clocks.
-- ---------------------------------------------------------------------------
CREATE TABLE game_rounds (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    game_id      INT UNSIGNED NOT NULL,
    round_number TINYINT UNSIGNED NOT NULL,
    fish_count   TINYINT UNSIGNED NOT NULL,
    chest_count  TINYINT UNSIGNED NOT NULL DEFAULT 0,
    answer       TINYINT UNSIGNED NULL,
    status       ENUM('pending','answered','timeout') NOT NULL DEFAULT 'pending',
    is_correct   TINYINT(1) NOT NULL DEFAULT 0,
    points       INT UNSIGNED NOT NULL DEFAULT 0,
    served_ms    BIGINT UNSIGNED NOT NULL,
    answered_ms  BIGINT UNSIGNED NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_rounds_game_round (game_id, round_number),
    CONSTRAINT fk_rounds_game FOREIGN KEY (game_id) REFERENCES games (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE badges (
    id                INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name              VARCHAR(60)  NOT NULL,
    description       VARCHAR(255) NOT NULL,
    icon              VARCHAR(30)  NOT NULL DEFAULT 'medal',
    requirement_type  VARCHAR(30)  NOT NULL,
    requirement_value INT UNSIGNED NOT NULL,
    sort_order        INT NOT NULL DEFAULT 0,
    created_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_badges_name (name),
    KEY idx_badges_requirement (requirement_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE user_badges (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id     INT UNSIGNED NOT NULL,
    badge_id    INT UNSIGNED NOT NULL,
    unlocked_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_user_badge (user_id, badge_id),
    KEY idx_user_badges_unlocked (user_id, unlocked_at),
    CONSTRAINT fk_user_badges_user  FOREIGN KEY (user_id)  REFERENCES users (id)  ON DELETE CASCADE,
    CONSTRAINT fk_user_badges_badge FOREIGN KEY (badge_id) REFERENCES badges (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Failed logins, used for rate limiting.
CREATE TABLE login_attempts (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    ip_address   VARCHAR(45)  NOT NULL,
    identifier   VARCHAR(120) NOT NULL,
    attempted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_attempts_ip (ip_address, attempted_at),
    KEY idx_attempts_identifier (identifier, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- "Remember me" tokens (selector/validator pattern; only a hash of the validator is stored).
CREATE TABLE remember_tokens (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id    INT UNSIGNED NOT NULL,
    selector   CHAR(24) NOT NULL,
    token_hash CHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_remember_selector (selector),
    KEY idx_remember_user (user_id),
    CONSTRAINT fk_remember_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
