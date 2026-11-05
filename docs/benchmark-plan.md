# Benchmark plan

## Question

For a social-network workload, when does a polyglot persistence setup
(MariaDB + MongoDB + Redis) outperform a single relational database, and when
does the extra operational complexity cost more than it saves?

## Status

**Not run.** The current repository implements the polyglot stack, seeders,
traffic simulator, and Prometheus/Grafana monitoring. The single-database
baseline and the measurement harness are the next phase.

## Baseline

Create `docker-compose.simple.yml` with:

- one Laravel app container
- one MariaDB container
- one Redis container for cache/queue
- no MongoDB, no replica set, no load balancer

Use the same application code and migrations, with the document models mapped
to MariaDB tables. The baseline must run the same endpoints and seed the same
logical dataset.

## Workloads

| Workload | Read/write mix | Main operations |
| --- | --- | --- |
| Read-heavy | 90 / 10 | feed reads, profile reads, conversation reads |
| Write-heavy | 30 / 70 | posts, comments, reactions, messages |
| Mixed | 50 / 50 | feed + write + notification fan-out |
| Spike | variable | burst traffic after a simulated event |

## Metrics

- p50, p95, p99 request latency
- requests per second and error rate
- MariaDB query latency and slow-query count
- MongoDB operation latency
- Redis cache hit/miss ratio
- CPU and memory per container
- bytes read/written per request

## Method

1. Seed the same logical dataset into both stacks (small scale first: 1,000 users).
2. Warm the cache and run a 60-second smoke test.
3. Run each workload for 5 minutes at three concurrency levels.
4. Repeat each run three times; report median and spread.
5. Record the exact image tags, host CPU/RAM, and Compose file in the results.
6. Publish raw Prometheus snapshots and a summary in `docs/results.md`.

## What would make the result credible

- A committed harness, not screenshots of ad-hoc terminal runs.
- The same dataset and endpoint behavior in both stacks.
- A stated null hypothesis and a result that can show "no meaningful difference".
- Failure cases included: cold cache, replica lag, queue backlog.
- A cost section: extra containers, memory, and operational surface.

## Non-goals

- A production capacity plan.
- A claim that one database is universally better.
- Benchmarking hardware rather than architecture.
