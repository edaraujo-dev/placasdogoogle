CREATE TABLE admins (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE companies (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(180) NOT NULL,
  contact_name VARCHAR(120) NULL,
  email VARCHAR(190) NULL,
  phone VARCHAR(40) NULL,
  google_review_url TEXT NOT NULL,
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE plaques (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(40) NOT NULL UNIQUE,
  activation_code_hash VARCHAR(255) NULL,
  activation_code_hint VARCHAR(12) NULL,
  batch_ref CHAR(16) NULL,
  company_id BIGINT UNSIGNED NULL,
  status ENUM('stock','active','paused') NOT NULL DEFAULT 'stock',
  notes VARCHAR(255) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  activated_at DATETIME NULL,
  CONSTRAINT fk_plaques_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE SET NULL,
  INDEX idx_plaques_company (company_id),
  INDEX idx_plaques_status (status),
  INDEX idx_plaques_batch_ref (batch_ref)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE scans (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  plaque_id BIGINT UNSIGNED NOT NULL,
  source ENUM('qr','nfc','unknown') NOT NULL DEFAULT 'unknown',
  ip_hash CHAR(64) NULL,
  user_agent VARCHAR(500) NULL,
  scanned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_scans_plaque FOREIGN KEY (plaque_id) REFERENCES plaques(id) ON DELETE CASCADE,
  INDEX idx_scans_plaque_date (plaque_id, scanned_at),
  INDEX idx_scans_date (scanned_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
