-- Seed: Shipping configuration
INSERT INTO shipping_configs (name, currency_code, is_active, valid_from)
VALUES ('default', 'PLN', TRUE, NULL);

SET @config_id = LAST_INSERT_ID();

-- Rule 1: Base Rate Configuration
INSERT INTO base_rate_configs (config_id, priority, is_enabled, default_amount, currency_code)
VALUES (@config_id, 100, TRUE, 39.99, 'PLN');

SET @base_rate_id = LAST_INSERT_ID();

-- Base rate scopes: PL -> 10, DE -> 20, US -> 50
INSERT INTO rule_scopes (rule_type, rule_id, country_id, amount) VALUES
  ('base_rate', @base_rate_id, (SELECT id FROM countries WHERE code = 'PL'), 10.00),
  ('base_rate', @base_rate_id, (SELECT id FROM countries WHERE code = 'DE'), 20.00),
  ('base_rate', @base_rate_id, (SELECT id FROM countries WHERE code = 'US'), 50.00);

-- Rule 2: Weight Surcharge Configuration
INSERT INTO weight_surcharge_configs (config_id, priority, is_enabled, limit_kg, surcharge_per_kg, currency_code)
VALUES (@config_id, 200, TRUE, 5.00, 3.00, 'PLN');

-- Rule 3: Free Shipping Configuration
INSERT INTO free_shipping_configs (config_id, priority, is_enabled, threshold, currency_code)
VALUES (@config_id, 300, TRUE, 400.00, 'PLN');

SET @free_shipping_id = LAST_INSERT_ID();

-- Free shipping scopes: PL, DE, FR, GB (all countries except US)
INSERT INTO rule_scopes (rule_type, rule_id, country_id) VALUES
  ('free_shipping', @free_shipping_id, (SELECT id FROM countries WHERE code = 'PL')),
  ('free_shipping', @free_shipping_id, (SELECT id FROM countries WHERE code = 'DE')),
  ('free_shipping', @free_shipping_id, (SELECT id FROM countries WHERE code = 'FR')),
  ('free_shipping', @free_shipping_id, (SELECT id FROM countries WHERE code = 'GB'));

-- Rule 4: Half-Price Shipping Configuration
INSERT INTO half_price_shipping_configs (config_id, priority, is_enabled, threshold, discount_percent, currency_code)
VALUES (@config_id, 305, TRUE, 400.00, 50, 'PLN');

SET @half_price_id = LAST_INSERT_ID();

-- Half-price shipping scope: US only
INSERT INTO rule_scopes (rule_type, rule_id, country_id) VALUES
  ('half_price_shipping', @half_price_id, (SELECT id FROM countries WHERE code = 'US'));

-- Rule 5: Friday Promotion Configuration
INSERT INTO friday_promotion_configs (config_id, priority, is_enabled, discount_percent)
VALUES (@config_id, 400, TRUE, 50);
