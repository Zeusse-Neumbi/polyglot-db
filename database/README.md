# Database & Seeding System Documentation

A comprehensive documentation of the hybrid database architecture (MariaDB + MongoDB) and the advanced seeding system for the Social Network application.

---

## Table of Contents

- [Database Architecture](#database-architecture)
- [Schema Overview](#schema-overview)
- [Seeding System](#seeding-system)
- [Usage](#usage)
- [Configuration](#configuration)
- [Performance Optimizations](#performance-optimizations)

---

## Database Architecture

This application uses a **hybrid database architecture**:

| Database | Purpose | Connection |
|:---------|:--------|:-----------|
| **MariaDB** | Relational data (users, friendships, groups) | `mysql` |
| **MongoDB** | Document data (posts, comments, messages) | `mongodb` |
| **Redis** | Caching & sessions | `redis` |

### Connection Configuration

Database connections are configured in `config/database.php`:

```php
// MariaDB (default)
'mysql' => [
    'driver' => 'mysql',
    'database' => env('DB_DATABASE', 'social_network'),
]

// MongoDB
'mongodb' => [
    'driver' => 'mongodb',
    'dsn' => env('MONGODB_URI'),
]
```

---

## Schema Overview

### MariaDB Tables

| Table | Description | Key Fields |
|:------|:------------|:-----------|
| `users` | User accounts | id, nom, prenom, email, date_naissance |
| `friends` | Friendship relationships | user_id, friend_id, status, blocked_at |
| `groups` | User groups | id, name, description, visibility, avatar |
| `group_user` | Group memberships | group_id, user_id, role |
| `pages` | Brand/community pages | id, name, category, avatar, owner_id |
| `page_followers` | Page followers | page_id, user_id |
| `conversations` | Chat conversations | id, type (private/group) |
| `conversation_user` | Conversation participants | conversation_id, user_id, role |

### MongoDB Collections

| Collection | Description | Key Fields |
|:-----------|:------------|:-----------|
| `posts` | User/Page posts | author_id, author_type, content, likes_count |
| `comments` | Post comments | post_id, user_id, content |
| `reactions` | Post reactions | post_id, user_id, reaction_type |
| `messages` | Chat messages | conversation_id, sender_id, body, attachments |
| `notifications` | User notifications | user_id, type, data, read_at |
| `sessions_audit` | Session logs | user_id, ip_address, device_info |

---

## Seeding System

The seeding system generates **5 years of realistic social network data** with configurable scale.

### Features

- ⚡ **Scalable**: From 1k to 250k users
- 📅 **Temporal Consistency**: 5-year history with realistic growth
- 👤 **User Personas**: Consistent activity patterns (power_user, casual, lurker, inactive)
- 📝 **Varied Content**: Short reactions, questions, long paragraphs
- 🔄 **Resumable**: Checkpoint system for interrupted seeding
- 🚀 **Optimized**: Transactions, pre-generated faker data, direct arrays

### Scale Options

| Scale | Users | Est. Time | Total Records |
|:------|------:|----------:|:--------------|
| Small | 1,000 | ~8 mins | ~600k |
| Medium | 10,000 | ~80 mins | ~6M |
| Large | 50,000 | ~6 hours | ~30M |
| XL | 100,000 | ~12 hours | ~60M |
| MAX | 250,000 | ~30+ hours | ~150M |

### Data Proportions

Based on real-world social network research:

| Entity | Ratio | Example (1k users) |
|:-------|:------|:-------------------|
| Groups | 10% of users | 100 groups |
| Pages | 5% of users | 50 pages |
| Friends/User | ~50 avg | 25k friendships |
| Posts/User | ~22 avg | 22k posts |
| Conversations | 5x users | 5k conversations |

---

## Usage

### Quick Start

```bash
# Fresh migration + seed (interactive)
php artisan migrate:fresh-all --seed

# Or manually:
php artisan migrate:fresh
php artisan db:seed
```

### Seed Only (Existing DB)

```bash
php artisan db:seed
```

The system will prompt you to select a scale:

```
🚀 Starting Social Network Data Seeder

 Select Seeding Scale [Small (1k users - ~3 mins)]:
  [0] Small (1k users - ~3 mins)
  [1] Medium (10k users - ~30 mins)
  [2] Large (50k users - ~2.5 hours)
  [3] XL (100k users - ~5 hours)
  [4] MAX (250k users - ~12+ hours)
```

### Resume Interrupted Seeding

If seeding is interrupted, restart and you'll be prompted:

```
⚠️  Found checkpoint: PostSeeder at offset 5000 (saved at 2024-12-21 20:00:00)
 Do you want to resume from this checkpoint? (yes/no)
```

---

## Configuration

### Helper Classes

Located in `database/seeders/Helpers/`:

| File | Purpose |
|:-----|:--------|
| `SeedingConfig.php` | Scale definitions, ratios, personas |
| `TimeSimulator.php` | 5-year timestamp distribution |
| `SeedingCheckpoint.php` | Resumable seeding |

### Customizing Ratios

Edit `SeedingConfig.php` to adjust:

```php
// Conversations per user
public function getConversationsPerUser(): int { return 5; }

// Messages per conversation
public function getMessagesPerConversation(): array { return [50, 150]; }

// Attachment probability (%)
public function getAttachmentProbability(): int { return 20; }

// User activity distribution
public static function getUserPersona(int $userId): string {
    // 10% power_user, 40% casual, 30% lurker, 20% inactive
}
```

---

## Performance Optimizations

The seeding system includes several performance optimizations:

| Optimization | Impact |
|:-------------|:-------|
| **Disable query logging** | Saves memory |
| **Eloquent unguard** | Skips model events |
| **Database transactions** | Faster bulk writes |
| **Direct array generation** | Skips factory overhead |
| **Pre-generated faker pools** | Reuses generated data |
| **Chunked processing** | Prevents OOM errors |
| **Memory management** | `gc_collect_cycles()` |

### FakerPool

Pre-generated data accessible via:

```php
use Database\Seeders\FakerPool;

FakerPool::randomSentence();
FakerPool::randomParagraph();
FakerPool::randomReaction();
FakerPool::randomImageUrl();
```

---

## Seeders

| Seeder | Creates | MongoDB |
|:-------|:--------|:-------:|
| `UserSeeder` | Users | ❌ |
| `GroupSeeder` | Groups, memberships | ❌ |
| `PageSeeder` | Pages, followers | ❌ |
| `FriendSeeder` | Friendships | ❌ |
| `PostSeeder` | Posts, comments, reactions | ✅ |
| `ConversationSeeder` | Conversations, messages | ✅ |
| `NotificationSeeder` | Notifications, session audits | ✅ |

### Execution Order

```
UserSeeder → GroupSeeder → PageSeeder → FriendSeeder → PostSeeder → ConversationSeeder → NotificationSeeder
```

---

## Verification

After seeding, verify data integrity:

```bash
php artisan tinker --execute="
echo 'Users: ' . \App\Models\User::count();
echo 'Posts: ' . \App\Models\Post::count();
echo 'Messages: ' . \App\Models\Message::count();
"
```

---

## Troubleshooting

### Common Issues

| Issue | Solution |
|:------|:---------|
| Memory exhausted | Use smaller scale or increase `php.ini` memory_limit |
| MongoDB timeout | Increase connection timeout in `config/database.php` |
| Slow seeding | Ensure indexes exist, use SSD storage |

### Reset Everything

```bash
php artisan migrate:fresh-all --seed
```

This drops all MariaDB tables and MongoDB collections before re-seeding.
