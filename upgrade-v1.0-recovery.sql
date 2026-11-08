-- Edtek Review v1.0 — recuperação segura para banco parcialmente atualizado
-- Compatível com a estrutura base v0.4 e com a coluna activation_code_encrypted
-- já adicionada manualmente. Faça backup antes de importar.
-- Este arquivo é idempotente: verifica colunas e constraints antes de criá-las.

SET @db_name = DATABASE();

-- Empresas: habilita os três destinos e mantém o link antigo do Google.
ALTER TABLE companies MODIFY google_review_url TEXT NULL;

SET @ddl = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='companies' AND COLUMN_NAME='product_type')=0,
  "ALTER TABLE companies ADD COLUMN product_type ENUM('google_review','whatsapp','instagram') NOT NULL DEFAULT 'google_review' AFTER google_review_url",
  'SELECT 1');
PREPARE edtek_stmt FROM @ddl; EXECUTE edtek_stmt; DEALLOCATE PREPARE edtek_stmt;

SET @ddl = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='companies' AND COLUMN_NAME='destination_url')=0,
  'ALTER TABLE companies ADD COLUMN destination_url TEXT NULL',
  'SELECT 1');
PREPARE edtek_stmt FROM @ddl; EXECUTE edtek_stmt; DEALLOCATE PREPARE edtek_stmt;

SET @ddl = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='companies' AND COLUMN_NAME='whatsapp_phone')=0,
  'ALTER TABLE companies ADD COLUMN whatsapp_phone VARCHAR(30) NULL',
  'SELECT 1');
PREPARE edtek_stmt FROM @ddl; EXECUTE edtek_stmt; DEALLOCATE PREPARE edtek_stmt;

SET @ddl = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='companies' AND COLUMN_NAME='whatsapp_message')=0,
  'ALTER TABLE companies ADD COLUMN whatsapp_message VARCHAR(500) NULL',
  'SELECT 1');
PREPARE edtek_stmt FROM @ddl; EXECUTE edtek_stmt; DEALLOCATE PREPARE edtek_stmt;

SET @ddl = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='companies' AND COLUMN_NAME='instagram_url')=0,
  'ALTER TABLE companies ADD COLUMN instagram_url VARCHAR(500) NULL',
  'SELECT 1');
PREPARE edtek_stmt FROM @ddl; EXECUTE edtek_stmt; DEALLOCATE PREPARE edtek_stmt;

-- Novas tabelas administrativas e de clientes.
CREATE TABLE IF NOT EXISTS projects (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  company_id BIGINT UNSIGNED NULL,
  name VARCHAR(180) NOT NULL,
  description VARCHAR(500) NULL,
  client_label VARCHAR(180) NULL,
  status ENUM('active','archived') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_projects_status(status),
  INDEX idx_projects_company(company_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @ddl = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='projects' AND COLUMN_NAME='company_id')=0,
  'ALTER TABLE projects ADD COLUMN company_id BIGINT UNSIGNED NULL AFTER id',
  'SELECT 1');
PREPARE edtek_stmt FROM @ddl; EXECUTE edtek_stmt; DEALLOCATE PREPARE edtek_stmt;

CREATE TABLE IF NOT EXISTS customer_accounts (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  company_id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  status ENUM('active','pending','blocked') NOT NULL DEFAULT 'active',
  email_verified_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_customer_company(company_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS password_resets (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  account_type ENUM('admin','customer','reseller') NOT NULL,
  account_id BIGINT UNSIGNED NOT NULL,
  token_hash CHAR(64) NOT NULL UNIQUE,
  expires_at DATETIME NOT NULL,
  used_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_password_reset_expiry(expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS email_settings (
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

CREATE TABLE IF NOT EXISTS reseller_accounts (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  phone VARCHAR(40) NULL,
  status ENUM('active','blocked') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS reseller_kit_orders (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  reseller_id BIGINT UNSIGNED NOT NULL,
  kit_size SMALLINT UNSIGNED NOT NULL,
  order_reference VARCHAR(80) NULL,
  status ENUM('requested','paid','delivered','cancelled') NOT NULL DEFAULT 'requested',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_kit_reseller(reseller_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS plaque_activation_attempts (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  plaque_id BIGINT UNSIGNED NULL,
  ip_hash CHAR(64) NOT NULL,
  attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_activation_attempt_ip_date(ip_hash,attempted_at),
  INDEX idx_activation_attempt_plaque(plaque_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Placas: campos usados pelo portal, produtos, projetos e revendedores.
SET @ddl = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='plaques' AND COLUMN_NAME='activation_code_encrypted')=0,
  'ALTER TABLE plaques ADD COLUMN activation_code_encrypted TEXT NULL AFTER activation_code_hash',
  'SELECT 1');
PREPARE edtek_stmt FROM @ddl; EXECUTE edtek_stmt; DEALLOCATE PREPARE edtek_stmt;

SET @ddl = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='plaques' AND COLUMN_NAME='activated_by_user_id')=0,
  'ALTER TABLE plaques ADD COLUMN activated_by_user_id BIGINT UNSIGNED NULL',
  'SELECT 1');
PREPARE edtek_stmt FROM @ddl; EXECUTE edtek_stmt; DEALLOCATE PREPARE edtek_stmt;

SET @ddl = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='plaques' AND COLUMN_NAME='project_id')=0,
  'ALTER TABLE plaques ADD COLUMN project_id BIGINT UNSIGNED NULL',
  'SELECT 1');
PREPARE edtek_stmt FROM @ddl; EXECUTE edtek_stmt; DEALLOCATE PREPARE edtek_stmt;

SET @ddl = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='plaques' AND COLUMN_NAME='reseller_id')=0,
  'ALTER TABLE plaques ADD COLUMN reseller_id BIGINT UNSIGNED NULL',
  'SELECT 1');
PREPARE edtek_stmt FROM @ddl; EXECUTE edtek_stmt; DEALLOCATE PREPARE edtek_stmt;

SET @ddl = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='plaques' AND COLUMN_NAME='product_type')=0,
  "ALTER TABLE plaques ADD COLUMN product_type ENUM('google_review','whatsapp','instagram') NOT NULL DEFAULT 'google_review'",
  'SELECT 1');
PREPARE edtek_stmt FROM @ddl; EXECUTE edtek_stmt; DEALLOCATE PREPARE edtek_stmt;

SET @ddl = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='plaques' AND COLUMN_NAME='sold_at')=0,
  'ALTER TABLE plaques ADD COLUMN sold_at DATETIME NULL',
  'SELECT 1');
PREPARE edtek_stmt FROM @ddl; EXECUTE edtek_stmt; DEALLOCATE PREPARE edtek_stmt;

SET @ddl = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='plaques' AND COLUMN_NAME='destination_url')=0,
  'ALTER TABLE plaques ADD COLUMN destination_url TEXT NULL',
  'SELECT 1');
PREPARE edtek_stmt FROM @ddl; EXECUTE edtek_stmt; DEALLOCATE PREPARE edtek_stmt;

SET @ddl = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='plaques' AND COLUMN_NAME='whatsapp_phone')=0,
  'ALTER TABLE plaques ADD COLUMN whatsapp_phone VARCHAR(30) NULL',
  'SELECT 1');
PREPARE edtek_stmt FROM @ddl; EXECUTE edtek_stmt; DEALLOCATE PREPARE edtek_stmt;

SET @ddl = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='plaques' AND COLUMN_NAME='whatsapp_message')=0,
  'ALTER TABLE plaques ADD COLUMN whatsapp_message VARCHAR(500) NULL',
  'SELECT 1');
PREPARE edtek_stmt FROM @ddl; EXECUTE edtek_stmt; DEALLOCATE PREPARE edtek_stmt;

SET @ddl = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='plaques' AND COLUMN_NAME='instagram_url')=0,
  'ALTER TABLE plaques ADD COLUMN instagram_url VARCHAR(500) NULL',
  'SELECT 1');
PREPARE edtek_stmt FROM @ddl; EXECUTE edtek_stmt; DEALLOCATE PREPARE edtek_stmt;

-- Preserva o destino Google legado e o copia para a coluna genérica.
UPDATE companies SET destination_url=google_review_url
WHERE destination_url IS NULL AND google_review_url IS NOT NULL AND google_review_url <> '';

-- Chaves estrangeiras: só são criadas se ainda não existirem.
SET @ddl = IF((SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=@db_name AND TABLE_NAME='projects' AND CONSTRAINT_NAME='fk_projects_company')=0,
  'ALTER TABLE projects ADD CONSTRAINT fk_projects_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE',
  'SELECT 1');
PREPARE edtek_stmt FROM @ddl; EXECUTE edtek_stmt; DEALLOCATE PREPARE edtek_stmt;

SET @ddl = IF((SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=@db_name AND TABLE_NAME='customer_accounts' AND CONSTRAINT_NAME='fk_customer_company')=0,
  'ALTER TABLE customer_accounts ADD CONSTRAINT fk_customer_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE',
  'SELECT 1');
PREPARE edtek_stmt FROM @ddl; EXECUTE edtek_stmt; DEALLOCATE PREPARE edtek_stmt;

SET @ddl = IF((SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=@db_name AND TABLE_NAME='plaques' AND CONSTRAINT_NAME='fk_plaques_customer')=0,
  'ALTER TABLE plaques ADD CONSTRAINT fk_plaques_customer FOREIGN KEY (activated_by_user_id) REFERENCES customer_accounts(id) ON DELETE SET NULL',
  'SELECT 1');
PREPARE edtek_stmt FROM @ddl; EXECUTE edtek_stmt; DEALLOCATE PREPARE edtek_stmt;

SET @ddl = IF((SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=@db_name AND TABLE_NAME='plaques' AND CONSTRAINT_NAME='fk_plaques_project')=0,
  'ALTER TABLE plaques ADD CONSTRAINT fk_plaques_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE SET NULL',
  'SELECT 1');
PREPARE edtek_stmt FROM @ddl; EXECUTE edtek_stmt; DEALLOCATE PREPARE edtek_stmt;

SET @ddl = IF((SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=@db_name AND TABLE_NAME='plaques' AND CONSTRAINT_NAME='fk_plaques_reseller')=0,
  'ALTER TABLE plaques ADD CONSTRAINT fk_plaques_reseller FOREIGN KEY (reseller_id) REFERENCES reseller_accounts(id) ON DELETE SET NULL',
  'SELECT 1');
PREPARE edtek_stmt FROM @ddl; EXECUTE edtek_stmt; DEALLOCATE PREPARE edtek_stmt;

SET @ddl = IF((SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=@db_name AND TABLE_NAME='reseller_kit_orders' AND CONSTRAINT_NAME='fk_kit_reseller')=0,
  'ALTER TABLE reseller_kit_orders ADD CONSTRAINT fk_kit_reseller FOREIGN KEY (reseller_id) REFERENCES reseller_accounts(id) ON DELETE CASCADE',
  'SELECT 1');
PREPARE edtek_stmt FROM @ddl; EXECUTE edtek_stmt; DEALLOCATE PREPARE edtek_stmt;

SET @ddl = IF((SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=@db_name AND TABLE_NAME='plaque_activation_attempts' AND CONSTRAINT_NAME='fk_activation_attempt_plaque')=0,
  'ALTER TABLE plaque_activation_attempts ADD CONSTRAINT fk_activation_attempt_plaque FOREIGN KEY (plaque_id) REFERENCES plaques(id) ON DELETE SET NULL',
  'SELECT 1');
PREPARE edtek_stmt FROM @ddl; EXECUTE edtek_stmt; DEALLOCATE PREPARE edtek_stmt;

-- Registro informativo da migração executada.
CREATE TABLE IF NOT EXISTS edtek_schema_migrations (
  version VARCHAR(40) PRIMARY KEY,
  applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO edtek_schema_migrations(version) VALUES ('v1.0-recovery-2026-11');
