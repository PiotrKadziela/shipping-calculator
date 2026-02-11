-- Seed: Currencies
INSERT INTO currencies (code, name, minor_unit, active) VALUES
  ('PLN', 'Polish Zloty', 2, TRUE),
  ('EUR', 'Euro', 2, TRUE),
  ('GBP', 'British Pound', 2, TRUE),
  ('USD', 'US Dollar', 2, TRUE);

-- Seed: Countries
INSERT INTO countries (code, name, active) VALUES
  ('PL', 'Poland', TRUE),
  ('DE', 'Germany', TRUE),
  ('US', 'United States', TRUE),
  ('FR', 'France', TRUE),
  ('GB', 'United Kingdom', TRUE);

-- Seed: Country Currencies
INSERT INTO country_currencies (country_id, currency_code, is_primary) VALUES
  ((SELECT id FROM countries WHERE code = 'PL'), 'PLN', TRUE),
  ((SELECT id FROM countries WHERE code = 'DE'), 'EUR', TRUE),
  ((SELECT id FROM countries WHERE code = 'US'), 'USD', TRUE),
  ((SELECT id FROM countries WHERE code = 'FR'), 'EUR', TRUE),
  ((SELECT id FROM countries WHERE code = 'GB'), 'GBP', TRUE);
