# 03 — Category Module

Event categories — Long Run, Short Run, Major Events, Super Long.

```sql
CREATE TABLE categories (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255),
  description TEXT NULL,
  icon VARCHAR(255) NULL,
  sort_order INT DEFAULT 0
);
```

# Seed Data

- 1. Long Run
- 2. Short Run
- 3. Major Events
- 4. Super Long