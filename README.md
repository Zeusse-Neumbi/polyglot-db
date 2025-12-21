# TP_BD — Polyglot Database Research Project

<p align="center">
  <strong>📊 Comparative Study: Polyglot vs Single Database Architecture</strong><br>
  <em>Academic Research Project — Database Performance Analysis</em>
</p>

---

## 🎯 Research Objective

This project is an **academic research** to compare and analyze the performance differences between:

| Architecture | Description |
|--------------|-------------|
| **Polyglot DB** | Hybrid system using MariaDB + MongoDB + Redis |
| **Simple DB** | Traditional single relational database (MariaDB only) |

The goal is to **monitor database traffic** and measure efficiency to demonstrate when and why polyglot architectures outperform (or underperform) simple database solutions.

---

## 🏗️ Research Approach

### Hypothesis

A polyglot database architecture provides better performance for complex social network workloads by leveraging:
- **MariaDB** — Structured, relational data (users, relationships)
- **MongoDB** — Flexible, document-based data (feeds, activity logs)
- **Redis** — High-speed caching (sessions, real-time counters)

### Methodology

1. **Build identical schema** on both architectures
2. **Seed with realistic data** (users, posts, comments, reactions, etc.)
3. **Run workload benchmarks** (read-heavy, write-heavy, mixed)
4. **Monitor & measure** query latency, throughput, resource usage
5. **Analyze results** to compare efficiency

---

## ✅ What's Done

### Database Schema

- [x] Complete SQL schema design (`database/schema.sql`)
- [x] 12+ tables: users, publications, comments, reactions, friends, conversations, messages, groups, pages, notifications, sessions_audit
- [x] Indexes and foreign keys for relational integrity
- [x] Triggers for automatic count updates (`likes_count`, `comments_count`)

### Laravel Implementation

- [x] 13 migrations for all tables
- [x] 14 Eloquent models with relationships
- [x] 11 factories for realistic test data
- [x] 8 modular seeders (Users → Friends → Posts → Groups → Pages → Conversations → Notifications)
- [x] Hybrid relations support (`HybridRelations` trait for MongoDB)

### Multi-DB Configuration

- [x] MariaDB connection configured
- [x] MongoDB connection configured
- [x] Redis connection configured

---

## 📋 TODO — Remaining Tasks

### 🔬 Benchmark Setup

- [ ] Create **simple DB version** (MariaDB only, no MongoDB/Redis)
- [ ] Design **benchmark workloads** (CRUD operations, feeds, searches)
- [ ] Implement **load testing scripts** (concurrent users simulation)

### 📈 Monitoring & Metrics

- [ ] Set up **query logging** for both architectures
- [ ] Implement **traffic monitoring** (queries/second, latency)
- [ ] Track **resource usage** (CPU, memory, I/O)
- [ ] Configure **profiling tools** (MySQL slow query log, MongoDB profiler)

### 📊 Data Collection

- [ ] Define **metrics to capture**:
  - Query execution time (avg, p95, p99)
  - Throughput (queries/second)
  - Connection pool usage
  - Cache hit/miss ratio (Redis)
  - Read vs write latency
- [ ] Create **data collection scripts**
- [ ] Build **results database/spreadsheet**

### 🧪 Experiments to Run

- [ ] **Experiment 1**: Read-heavy (90% reads / 10% writes)
- [ ] **Experiment 2**: Write-heavy (30% reads / 70% writes)
- [ ] **Experiment 3**: Mixed workload (50/50)
- [ ] **Experiment 4**: Spike traffic simulation
- [ ] **Experiment 5**: Large dataset (100K+ users, 1M+ posts)

### 📝 Analysis & Report

- [ ] Compile **benchmark results**
- [ ] Create **comparison charts/graphs**
- [ ] Write **research findings**
- [ ] Prepare **conclusions & recommendations**

---

## 🛠️ Tech Stack

| Component | Technology | Role |
|-----------|------------|------|
| Framework | Laravel 12 | Application layer |
| Primary DB | MariaDB | Relational data |
| Document Store | MongoDB | Flexible storage |
| Cache | Redis | High-speed caching |
| Language | PHP 8.2+ | Backend |

---

## 🚀 Quick Start

```bash
# Install dependencies
composer install

# Configure environment
cp .env.example .env
php artisan key:generate

# Run migrations
php artisan migrate

# Seed test data
php artisan db:seed

# Start server
composer dev
```

---

## 📂 Project Structure

```
TP_BD/
├── app/Models/           # 14 Eloquent models
├── database/
│   ├── factories/        # 11 factories
│   ├── migrations/       # 13 migrations
│   ├── seeders/          # 8 seeders
│   └── schema.sql        # Raw SQL schema
└── config/database.php   # Multi-DB config
```

---

## 📄 License

MIT License — Academic Research Project
