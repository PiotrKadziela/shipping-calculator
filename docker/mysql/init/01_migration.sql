-- Migration: Shipping configuration schema v2 (Option 3 - Hybrid)
-- Removes EAV pattern, creates dedicated tables per rule type

CREATE TABLE currencies (
  code CHAR(3) NOT NULL PRIMARY KEY,
  name VARCHAR(50) NOT NULL,
  minor_unit TINYINT NOT NULL DEFAULT 2,
  active BOOLEAN NOT NULL DEFAULT TRUE,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE countries (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  code CHAR(2) NOT NULL UNIQUE,
  name VARCHAR(100) NOT NULL,
  active BOOLEAN NOT NULL DEFAULT TRUE,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE country_currencies (
  country_id BIGINT UNSIGNED NOT NULL,
  currency_code CHAR(3) NOT NULL,
  is_primary BOOLEAN NOT NULL DEFAULT TRUE,
  PRIMARY KEY (country_id, currency_code),
  CONSTRAINT fk_country_currencies_country
    FOREIGN KEY (country_id) REFERENCES countries(id)
    ON DELETE CASCADE,
  CONSTRAINT fk_country_currencies_currency
    FOREIGN KEY (currency_code) REFERENCES currencies(code)
) ENGINE=InnoDB;

CREATE TABLE shipping_configs (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  currency_code CHAR(3) NOT NULL,
  is_active BOOLEAN NOT NULL DEFAULT FALSE,
  valid_from DATE NULL,
  valid_to DATE NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_shipping_configs_currency
    FOREIGN KEY (currency_code) REFERENCES currencies(code)
) ENGINE=InnoDB;

-- Rule 1: Base rate configuration
CREATE TABLE base_rate_configs (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  config_id BIGINT UNSIGNED NOT NULL,
  priority INT NOT NULL DEFAULT 100,
  is_enabled BOOLEAN NOT NULL DEFAULT TRUE,
  default_amount DECIMAL(12, 2) NOT NULL,
  currency_code CHAR(3) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_config (config_id),
  CONSTRAINT fk_base_rate_config
    FOREIGN KEY (config_id) REFERENCES shipping_configs(id) ON DELETE CASCADE,
  CONSTRAINT fk_base_rate_currency
    FOREIGN KEY (currency_code) REFERENCES currencies(code)
) ENGINE=InnoDB;

-- Rule 2: Weight surcharge configuration
CREATE TABLE weight_surcharge_configs (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  config_id BIGINT UNSIGNED NOT NULL,
  priority INT NOT NULL DEFAULT 200,
  is_enabled BOOLEAN NOT NULL DEFAULT TRUE,
  limit_kg DECIMAL(8, 2) NOT NULL,
  surcharge_per_kg DECIMAL(12, 2) NOT NULL,
  currency_code CHAR(3) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_config (config_id),
  CONSTRAINT fk_weight_surcharge_config
    FOREIGN KEY (config_id) REFERENCES shipping_configs(id) ON DELETE CASCADE,
  CONSTRAINT fk_weight_surcharge_currency
    FOREIGN KEY (currency_code) REFERENCES currencies(code)
) ENGINE=InnoDB;

-- Rule 3: Free shipping configuration
CREATE TABLE free_shipping_configs (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  config_id BIGINT UNSIGNED NOT NULL,
  priority INT NOT NULL DEFAULT 300,
  is_enabled BOOLEAN NOT NULL DEFAULT TRUE,
  threshold DECIMAL(12, 2) NOT NULL,
  currency_code CHAR(3) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_config (config_id),
  CONSTRAINT fk_free_shipping_config
    FOREIGN KEY (config_id) REFERENCES shipping_configs(id) ON DELETE CASCADE,
  CONSTRAINT fk_free_shipping_currency
    FOREIGN KEY (currency_code) REFERENCES currencies(code)
) ENGINE=InnoDB;

-- Rule 4: Half-price shipping configuration
CREATE TABLE half_price_shipping_configs (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  config_id BIGINT UNSIGNED NOT NULL,
  priority INT NOT NULL DEFAULT 300,
  is_enabled BOOLEAN NOT NULL DEFAULT TRUE,
  threshold DECIMAL(12, 2) NOT NULL,
  discount_percent TINYINT NOT NULL DEFAULT 50,
  currency_code CHAR(3) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_config (config_id),
  CONSTRAINT fk_half_price_config
    FOREIGN KEY (config_id) REFERENCES shipping_configs(id) ON DELETE CASCADE,
  CONSTRAINT fk_half_price_currency
    FOREIGN KEY (currency_code) REFERENCES currencies(code)
) ENGINE=InnoDB;

-- Rule 5: Friday promotion configuration
CREATE TABLE friday_promotion_configs (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  config_id BIGINT UNSIGNED NOT NULL,
  priority INT NOT NULL DEFAULT 400,
  is_enabled BOOLEAN NOT NULL DEFAULT TRUE,
  discount_percent TINYINT NOT NULL DEFAULT 50,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_config (config_id),
  CONSTRAINT fk_friday_promo_config
    FOREIGN KEY (config_id) REFERENCES shipping_configs(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Shared: Country scopes for rules that are country-specific
-- rule_type can be: 'base_rate', 'free_shipping', 'half_price_shipping'
-- amount: optional override for specific country (used by base_rate)
CREATE TABLE rule_scopes (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  rule_type VARCHAR(50) NOT NULL,
  rule_id BIGINT UNSIGNED NOT NULL,
  country_id BIGINT UNSIGNED NOT NULL,
  amount DECIMAL(12, 2) NULL COMMENT 'Optional country-specific amount (e.g., base rates per country)',
  UNIQUE KEY uq_scope (rule_type, rule_id, country_id),
  CONSTRAINT fk_rule_scope_country
    FOREIGN KEY (country_id) REFERENCES countries(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Exchange rates (for future multi-currency support)
CREATE TABLE exchange_rates (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  config_id BIGINT UNSIGNED NOT NULL,
  base_currency CHAR(3) NOT NULL,
  quote_currency CHAR(3) NOT NULL,
  rate DECIMAL(18, 8) NOT NULL,
  as_of DATE NOT NULL,
  UNIQUE KEY uq_rate (config_id, base_currency, quote_currency, as_of),
  CONSTRAINT fk_exchange_rates_config
    FOREIGN KEY (config_id) REFERENCES shipping_configs(id) ON DELETE CASCADE,
  CONSTRAINT fk_exchange_rates_base
    FOREIGN KEY (base_currency) REFERENCES currencies(code),
  CONSTRAINT fk_exchange_rates_quote
    FOREIGN KEY (quote_currency) REFERENCES currencies(code)
) ENGINE=InnoDB;
