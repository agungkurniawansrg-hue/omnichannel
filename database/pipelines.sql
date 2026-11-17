-- SMD Pipeline (Sales & Marketing) dan OPD Pipeline (Operasional). Jalankan SETELAH omnichannel_rental.sql.
-- Idempotent (CREATE TABLE IF NOT EXISTS); tidak menghapus data.
USE omnichannel_rental;

CREATE TABLE IF NOT EXISTS smd_deals (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  deal_no VARCHAR(20) NOT NULL UNIQUE,
  customer_id INT UNSIGNED NULL,
  company_name VARCHAR(120) NOT NULL,
  contact_name VARCHAR(120) NULL,
  phone VARCHAR(30) NULL,
  email VARCHAR(120) NULL,
  source_channel_id TINYINT UNSIGNED NULL,
  stage ENUM('leads_masuk','follow_up','penawaran','deal') NOT NULL DEFAULT 'leads_masuk',
  status ENUM('active','won','lost') NOT NULL DEFAULT 'active',
  est_value DECIMAL(14,2) NOT NULL DEFAULT 0,
  notes TEXT NULL,
  spk_no VARCHAR(40) NULL,
  spk_date DATE NULL,
  lost_reason VARCHAR(255) NULL,
  owner_user_id INT UNSIGNED NOT NULL COMMENT 'PIC sales',
  created_by INT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  closed_at DATETIME NULL,
  KEY idx_smd_stage (stage, status), KEY idx_smd_owner (owner_user_id),
  KEY idx_smd_phone (phone), KEY idx_smd_email (email),
  CONSTRAINT fk_smd_cust FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL,
  CONSTRAINT fk_smd_channel FOREIGN KEY (source_channel_id) REFERENCES channels(id) ON DELETE SET NULL,
  CONSTRAINT fk_smd_owner FOREIGN KEY (owner_user_id) REFERENCES users(id),
  CONSTRAINT fk_smd_creator FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS opd_jobs (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  job_no VARCHAR(20) NOT NULL UNIQUE,
  customer_id INT UNSIGNED NULL,
  customer_name VARCHAR(120) NOT NULL,
  phone VARCHAR(30) NULL,
  vehicle_id INT UNSIGNED NULL,
  plate_no VARCHAR(15) NOT NULL,
  vehicle_model VARCHAR(80) NULL,
  service_type ENUM('servis_umum','overhaul') NOT NULL DEFAULT 'servis_umum',
  complaint TEXT NOT NULL,
  stage ENUM('gate_awal','estimasi','proses_servis','selesai') NOT NULL DEFAULT 'gate_awal',
  status ENUM('active','picked_up') NOT NULL DEFAULT 'active',
  pic_user_id INT UNSIGNED NOT NULL COMMENT 'PIC operasional untuk kartu ini',
  qc_passed TINYINT(1) NOT NULL DEFAULT 0,
  qc_note VARCHAR(500) NULL,
  ready_at DATETIME NULL,
  picked_up_at DATETIME NULL,
  created_by INT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_opd_stage (stage, status), KEY idx_opd_pic (pic_user_id), KEY idx_opd_plate (plate_no),
  CONSTRAINT fk_opd_cust FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL,
  CONSTRAINT fk_opd_veh FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE SET NULL,
  CONSTRAINT fk_opd_pic FOREIGN KEY (pic_user_id) REFERENCES users(id),
  CONSTRAINT fk_opd_creator FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS opd_estimate_items (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  job_id INT UNSIGNED NOT NULL,
  item_type ENUM('sparepart','pekerjaan') NOT NULL,
  description VARCHAR(200) NOT NULL,
  qty DECIMAL(10,2) NOT NULL DEFAULT 1,
  unit_price DECIMAL(14,2) NOT NULL DEFAULT 0,
  KEY idx_est_job (job_id),
  CONSTRAINT fk_est_job FOREIGN KEY (job_id) REFERENCES opd_jobs(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Riwayat perpindahan tahap dan catatan untuk kedua pipeline.
CREATE TABLE IF NOT EXISTS pipeline_events (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  pipeline ENUM('smd','opd') NOT NULL,
  card_id INT UNSIGNED NOT NULL,
  event_type ENUM('created','move','note','assign','update','close') NOT NULL,
  from_stage VARCHAR(30) NULL,
  to_stage VARCHAR(30) NULL,
  note VARCHAR(500) NULL,
  user_id INT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_pe_card (pipeline, card_id, id),
  CONSTRAINT fk_pe_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;
