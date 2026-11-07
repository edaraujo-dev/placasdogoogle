-- Edtek Review v0.4: activation code metadata for individually printed plaques
ALTER TABLE plaques
  ADD COLUMN activation_code_hash VARCHAR(255) NULL AFTER code,
  ADD COLUMN activation_code_hint VARCHAR(12) NULL AFTER activation_code_hash,
  ADD COLUMN batch_ref CHAR(16) NULL AFTER activation_code_hint,
  ADD INDEX idx_plaques_batch_ref (batch_ref);
