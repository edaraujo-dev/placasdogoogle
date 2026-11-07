-- Edtek Review v1.0 - contas, produtos, projetos, revendedores e SMTP
-- Faça backup antes de importar. Executar uma única vez no banco existente.

ALTER TABLE companies
  MODIFY google_review_url TEXT NULL,
  ADD COLUMN product_type ENUM('google_review','whatsapp','instagram') NOT NULL DEFAULT 'google_review' AFTER google_review_url,
  ADD COLUMN destination_url TEXT NULL AFTER product_type,
  ADD COLUMN whatsapp_phone VARCHAR(30) NULL AFTER destination_url,
  ADD COLUMN whatsapp_message VARCHAR(500) NULL AFTER whatsapp_phone,
  ADD COLUMN instagram_url VARCHAR(500) NULL AFTER whatsapp_message,
  ADD COLUMN project_id BIGINT UNSIGNED NULL AFTER status;

CREATE TABLE projects (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(180) NOT NULL,
  description VARCHAR(500) NULL,
  client_label VARCHAR(180) NULL,
  status ENUM('active','archived') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_projects_status(status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE companies
  ADD CONSTRAINT fk_companies_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE SET NULL;

CREATE TABLE customer_accounts (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  company_id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  status ENUM('active','pending','blocked') NOT NULL DEFAULT 'active',
  email_verified_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_customer_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE password_resets (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  account_type ENUM('admin','customer','reseller') NOT NULL,
  account_id BIGINT UNSIGNED NOT NULL,
  token_hash CHAR(64) NOT NULL UNIQUE,
  expires_at DATETIME NOT NULL,
  used_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_password_reset_expiry(expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE email_settings (
  id TINYINT UNSIGNED PRIMARY KEY,
  host VARCHAR(190) NOT NULL,
  port SMALLINT UNSIGNED NOT NULL DEFAULT 587,
  username VARCHAR(190) NOT NULL,
  password_encrypted TEXT NULL,
  encryption ENUM('none','ssl','tls') NOT NULL DEFAULT 'tls',
  from_name VARCHAR(120) NOT NULL,
  from_email VARCHAR(190) NOT NULL,
  reply_to VARCHAR(190) NULL,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE plaques
  ADD COLUMN activated_by_user_id BIGINT UNSIGNED NULL,
  ADD COLUMN project_id BIGINT UNSIGNED NULL,
  ADD COLUMN reseller_id BIGINT UNSIGNED NULL,
  ADD COLUMN product_type ENUM('google_review','whatsapp','instagram') NOT NULL DEFAULT 'google_review',
  ADD COLUMN sold_at DATETIME NULL,
  ADD INDEX idx_plaques_product_type(product_type),
  ADD CONSTRAINT fk_plaques_customer FOREIGN KEY (activated_by_user_id) REFERENCES customer_accounts(id) ON DELETE SET NULL,
  ADD CONSTRAINT fk_plaques_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE SET NULL;

CREATE TABLE reseller_accounts (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  phone VARCHAR(40) NULL,
  status ENUM('active','blocked') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE plaques
  ADD CONSTRAINT fk_plaques_reseller FOREIGN KEY (reseller_id) REFERENCES reseller_accounts(id) ON DELETE SET NULL;

CREATE TABLE reseller_kit_orders (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  reseller_id BIGINT UNSIGNED NOT NULL,
  kit_size SMALLINT UNSIGNED NOT NULL,
  order_reference VARCHAR(80) NULL,
  status ENUM('requested','paid','delivered','cancelled') NOT NULL DEFAULT 'requested',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_kit_reseller FOREIGN KEY (reseller_id) REFERENCES reseller_accounts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Migra o destino antigo do Google para a coluna genérica usada pelo roteador.
UPDATE companies SET destination_url=google_review_url WHERE destination_url IS NULL AND google_review_url IS NOT NULL;
