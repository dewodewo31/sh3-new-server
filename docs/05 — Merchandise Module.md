# 05 — Merchandise Module

Merchandise/jersey management & order system.

```sql
CREATE TABLE merchandise (
  id, name, description, price, size_options (JSON),
  stock INT, image, status (available|sold_out|discontinued)
);
CREATE TABLE merchandise_orders (
  id, merchandise_id, participant_id, customer_name,
  customer_contact, size, quantity, total_price,
  payment_status (pending|paid|cancelled), payment_id
);
```

# Admin Routes

- /merchandise — Full Access, Laman, Merchandise
- /merchandise-orders — Plus Bendahara