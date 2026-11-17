-- AutoTRANZ Omnichannel Management System - Skema database (Phase 2)
-- MySQL 8.0 / InnoDB / utf8mb4. Hanya membuat database `omnichannel_rental`; tidak menyentuh database lain.
-- Jalankan ulang aman: seluruh tabel di-DROP lalu dibuat ulang (DATA HILANG).

CREATE DATABASE IF NOT EXISTS omnichannel_rental CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE omnichannel_rental;
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS integration_logs, channel_integrations, ticket_replies, tickets, ticket_categories,
  conversation_assignments, messages, conversations, agents, customer_interactions, leads, customer_notes,
  customer_contacts, customers, channels, payments, financial_transactions, expenses, invoices,
  rental_contracts, booking_details, bookings, drivers, vehicles, vehicle_categories,
  attendance, leave_requests, employee_schedules, activity_logs, users, employees, departments, roles;
SET FOREIGN_KEY_CHECKS = 1;

-- ===== User & Organisasi =====
CREATE TABLE roles (
  id TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(40) NOT NULL UNIQUE,
  name VARCHAR(60) NOT NULL,
  description VARCHAR(255) NULL
) ENGINE=InnoDB;

CREATE TABLE departments (
  id TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(40) NOT NULL UNIQUE,
  name VARCHAR(80) NOT NULL,
  description VARCHAR(255) NULL,
  head_employee_id INT UNSIGNED NULL
) ENGINE=InnoDB;

CREATE TABLE employees (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  employee_no VARCHAR(20) NOT NULL UNIQUE,
  department_id TINYINT UNSIGNED NOT NULL,
  full_name VARCHAR(120) NOT NULL,
  position VARCHAR(80) NULL,
  phone VARCHAR(30) NULL,
  email VARCHAR(120) NULL,
  hire_date DATE NULL,
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_emp_dept (department_id),
  CONSTRAINT fk_emp_dept FOREIGN KEY (department_id) REFERENCES departments(id)
) ENGINE=InnoDB;
ALTER TABLE departments ADD CONSTRAINT fk_dept_head FOREIGN KEY (head_employee_id) REFERENCES employees(id) ON DELETE SET NULL;

CREATE TABLE users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  role_id TINYINT UNSIGNED NOT NULL,
  employee_id INT UNSIGNED NULL,
  username VARCHAR(50) NOT NULL UNIQUE,
  email VARCHAR(120) NOT NULL UNIQUE,
  full_name VARCHAR(120) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  failed_logins TINYINT UNSIGNED NOT NULL DEFAULT 0,
  locked_until DATETIME NULL,
  last_login_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_user_role (role_id),
  CONSTRAINT fk_user_role FOREIGN KEY (role_id) REFERENCES roles(id),
  CONSTRAINT fk_user_emp FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE activity_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NULL,
  action VARCHAR(60) NOT NULL,
  entity VARCHAR(60) NULL,
  entity_id VARCHAR(40) NULL,
  details VARCHAR(500) NULL,
  ip_address VARCHAR(45) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_log_user (user_id), KEY idx_log_created (created_at),
  CONSTRAINT fk_log_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ===== Omnichannel (channels dibuat lebih dulu karena dirujuk customers) =====
CREATE TABLE channels (
  id TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `key` VARCHAR(30) NOT NULL UNIQUE,
  name VARCHAR(80) NOT NULL,
  group_type ENUM('online','offline') NOT NULL,
  integration_status ENUM('simulation','manual','not_configured','connected') NOT NULL DEFAULT 'not_configured'
) ENGINE=InnoDB;

-- ===== Customer & CRM =====
CREATE TABLE customers (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  customer_code VARCHAR(20) NOT NULL UNIQUE,
  type ENUM('individual','corporate') NOT NULL DEFAULT 'individual',
  full_name VARCHAR(120) NOT NULL,
  company_name VARCHAR(120) NULL,
  phone VARCHAR(30) NULL UNIQUE,
  email VARCHAR(120) NULL UNIQUE,
  address TEXT NULL,
  status ENUM('lead','prospect','active','inactive') NOT NULL DEFAULT 'lead',
  vehicle_preference VARCHAR(255) NULL,
  source_channel_id TINYINT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_cust_name (full_name),
  CONSTRAINT fk_cust_channel FOREIGN KEY (source_channel_id) REFERENCES channels(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE customer_contacts (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  customer_id INT UNSIGNED NOT NULL,
  channel_id TINYINT UNSIGNED NOT NULL,
  handle VARCHAR(150) NOT NULL COMMENT 'nomor WA, username IG, PSID, email, dst.',
  UNIQUE KEY uq_contact (channel_id, handle),
  KEY idx_contact_cust (customer_id),
  CONSTRAINT fk_cc_cust FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
  CONSTRAINT fk_cc_channel FOREIGN KEY (channel_id) REFERENCES channels(id)
) ENGINE=InnoDB;

CREATE TABLE customer_notes (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  customer_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NULL,
  note TEXT NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_note_cust (customer_id),
  CONSTRAINT fk_cn_cust FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
  CONSTRAINT fk_cn_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE leads (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  customer_id INT UNSIGNED NOT NULL,
  source_channel_id TINYINT UNSIGNED NULL,
  owner_user_id INT UNSIGNED NULL,
  stage ENUM('new','contacted','quotation','negotiation','won','lost') NOT NULL DEFAULT 'new',
  expected_value DECIMAL(14,2) NULL,
  notes VARCHAR(500) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_lead_stage (stage),
  CONSTRAINT fk_lead_cust FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
  CONSTRAINT fk_lead_channel FOREIGN KEY (source_channel_id) REFERENCES channels(id) ON DELETE SET NULL,
  CONSTRAINT fk_lead_owner FOREIGN KEY (owner_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE customer_interactions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  customer_id INT UNSIGNED NOT NULL,
  channel_id TINYINT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NULL,
  direction ENUM('inbound','outbound') NOT NULL,
  summary VARCHAR(500) NOT NULL,
  follow_up TEXT NULL,
  occurred_at DATETIME NOT NULL,
  KEY idx_int_cust (customer_id, occurred_at),
  CONSTRAINT fk_ci_cust FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
  CONSTRAINT fk_ci_channel FOREIGN KEY (channel_id) REFERENCES channels(id),
  CONSTRAINT fk_ci_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ===== Omnichannel lanjutan =====
CREATE TABLE agents (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL UNIQUE,
  display_name VARCHAR(80) NOT NULL,
  is_available TINYINT(1) NOT NULL DEFAULT 1,
  CONSTRAINT fk_agent_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE conversations (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  customer_id INT UNSIGNED NOT NULL,
  channel_id TINYINT UNSIGNED NOT NULL,
  external_thread_id VARCHAR(150) NULL,
  status ENUM('new','open','pending','resolved') NOT NULL DEFAULT 'new',
  priority ENUM('low','medium','high') NOT NULL DEFAULT 'medium',
  assigned_agent_id INT UNSIGNED NULL,
  is_simulated TINYINT(1) NOT NULL DEFAULT 0,
  last_message_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_conv_status (status), KEY idx_conv_last (last_message_at), KEY idx_conv_cust (customer_id),
  CONSTRAINT fk_conv_cust FOREIGN KEY (customer_id) REFERENCES customers(id),
  CONSTRAINT fk_conv_channel FOREIGN KEY (channel_id) REFERENCES channels(id),
  CONSTRAINT fk_conv_agent FOREIGN KEY (assigned_agent_id) REFERENCES agents(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE messages (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  conversation_id BIGINT UNSIGNED NOT NULL,
  sender_type ENUM('customer','agent','system') NOT NULL,
  sender_user_id INT UNSIGNED NULL,
  is_internal_note TINYINT(1) NOT NULL DEFAULT 0,
  body TEXT NOT NULL,
  attachment_path VARCHAR(255) NULL,
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_msg_conv (conversation_id, created_at),
  CONSTRAINT fk_msg_conv FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE,
  CONSTRAINT fk_msg_user FOREIGN KEY (sender_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE conversation_assignments (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  conversation_id BIGINT UNSIGNED NOT NULL,
  from_agent_id INT UNSIGNED NULL,
  to_agent_id INT UNSIGNED NOT NULL,
  assigned_by INT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_ca_conv FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE,
  CONSTRAINT fk_ca_from FOREIGN KEY (from_agent_id) REFERENCES agents(id) ON DELETE SET NULL,
  CONSTRAINT fk_ca_to FOREIGN KEY (to_agent_id) REFERENCES agents(id),
  CONSTRAINT fk_ca_by FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE channel_integrations (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  channel_id TINYINT UNSIGNED NOT NULL UNIQUE,
  webhook_url VARCHAR(255) NULL,
  config_json TEXT NULL COMMENT 'kredensial API; isi di Phase 6, jangan commit',
  connection_status ENUM('not_configured','simulation','connected','error') NOT NULL DEFAULT 'not_configured',
  last_checked_at DATETIME NULL,
  CONSTRAINT fk_ci2_channel FOREIGN KEY (channel_id) REFERENCES channels(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE integration_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  channel_id TINYINT UNSIGNED NOT NULL,
  level ENUM('info','warning','error') NOT NULL DEFAULT 'info',
  message VARCHAR(500) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_il_channel FOREIGN KEY (channel_id) REFERENCES channels(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ===== Ticketing =====
CREATE TABLE ticket_categories (
  id TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(80) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE tickets (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ticket_no VARCHAR(20) NOT NULL UNIQUE,
  customer_id INT UNSIGNED NOT NULL,
  conversation_id BIGINT UNSIGNED NULL,
  category_id TINYINT UNSIGNED NOT NULL,
  subject VARCHAR(200) NOT NULL,
  description TEXT NULL,
  priority ENUM('low','medium','high','urgent') NOT NULL DEFAULT 'medium',
  status ENUM('open','in_progress','pending','resolved','closed') NOT NULL DEFAULT 'open',
  assigned_user_id INT UNSIGNED NULL,
  first_response_at DATETIME NULL,
  resolved_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_tk_status (status),
  CONSTRAINT fk_tk_cust FOREIGN KEY (customer_id) REFERENCES customers(id),
  CONSTRAINT fk_tk_conv FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE SET NULL,
  CONSTRAINT fk_tk_cat FOREIGN KEY (category_id) REFERENCES ticket_categories(id),
  CONSTRAINT fk_tk_user FOREIGN KEY (assigned_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE ticket_replies (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ticket_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NULL,
  body TEXT NOT NULL,
  is_internal TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_tr_ticket FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
  CONSTRAINT fk_tr_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ===== Rental & Booking =====
CREATE TABLE vehicle_categories (
  id TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(80) NOT NULL UNIQUE,
  kind ENUM('passenger','commercial') NOT NULL
) ENGINE=InnoDB;

CREATE TABLE vehicles (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  category_id TINYINT UNSIGNED NOT NULL,
  plate_no VARCHAR(15) NOT NULL UNIQUE,
  brand VARCHAR(50) NOT NULL,
  model VARCHAR(60) NOT NULL,
  year SMALLINT UNSIGNED NULL,
  seats TINYINT UNSIGNED NULL,
  daily_rate DECIMAL(12,2) NOT NULL DEFAULT 0,
  status ENUM('available','rented','maintenance','inactive') NOT NULL DEFAULT 'available',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_veh_status (status),
  CONSTRAINT fk_veh_cat FOREIGN KEY (category_id) REFERENCES vehicle_categories(id)
) ENGINE=InnoDB;

CREATE TABLE drivers (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  employee_id INT UNSIGNED NULL,
  full_name VARCHAR(120) NOT NULL,
  phone VARCHAR(30) NULL,
  license_no VARCHAR(40) NULL,
  license_expiry DATE NULL,
  status ENUM('available','on_duty','off') NOT NULL DEFAULT 'available',
  CONSTRAINT fk_drv_emp FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE bookings (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  booking_code VARCHAR(20) NOT NULL UNIQUE,
  customer_id INT UNSIGNED NOT NULL,
  channel_id TINYINT UNSIGNED NULL,
  sales_user_id INT UNSIGNED NULL,
  start_date DATE NOT NULL,
  end_date DATE NOT NULL,
  total_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
  status ENUM('draft','pending_payment','confirmed','ongoing','completed','cancelled') NOT NULL DEFAULT 'draft',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_bk_status (status), KEY idx_bk_dates (start_date, end_date),
  CONSTRAINT fk_bk_cust FOREIGN KEY (customer_id) REFERENCES customers(id),
  CONSTRAINT fk_bk_channel FOREIGN KEY (channel_id) REFERENCES channels(id) ON DELETE SET NULL,
  CONSTRAINT fk_bk_sales FOREIGN KEY (sales_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE booking_details (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  booking_id INT UNSIGNED NOT NULL,
  vehicle_id INT UNSIGNED NOT NULL,
  driver_id INT UNSIGNED NULL,
  daily_rate DECIMAL(12,2) NOT NULL,
  days SMALLINT UNSIGNED NOT NULL,
  subtotal DECIMAL(14,2) NOT NULL,
  KEY idx_bd_vehicle (vehicle_id),
  CONSTRAINT fk_bd_booking FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
  CONSTRAINT fk_bd_vehicle FOREIGN KEY (vehicle_id) REFERENCES vehicles(id),
  CONSTRAINT fk_bd_driver FOREIGN KEY (driver_id) REFERENCES drivers(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE rental_contracts (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  contract_no VARCHAR(30) NOT NULL UNIQUE,
  booking_id INT UNSIGNED NOT NULL,
  signed_at DATE NULL,
  file_path VARCHAR(255) NULL,
  terms TEXT NULL,
  CONSTRAINT fk_rc_booking FOREIGN KEY (booking_id) REFERENCES bookings(id)
) ENGINE=InnoDB;

-- ===== Finance =====
CREATE TABLE invoices (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  invoice_no VARCHAR(20) NOT NULL UNIQUE,
  booking_id INT UNSIGNED NOT NULL,
  customer_id INT UNSIGNED NOT NULL,
  issue_date DATE NOT NULL,
  due_date DATE NULL,
  amount DECIMAL(14,2) NOT NULL,
  status ENUM('unpaid','partial','paid','void') NOT NULL DEFAULT 'unpaid',
  KEY idx_inv_status (status),
  CONSTRAINT fk_inv_booking FOREIGN KEY (booking_id) REFERENCES bookings(id),
  CONSTRAINT fk_inv_cust FOREIGN KEY (customer_id) REFERENCES customers(id)
) ENGINE=InnoDB;

CREATE TABLE payments (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  invoice_id INT UNSIGNED NOT NULL,
  paid_at DATETIME NOT NULL,
  amount DECIMAL(14,2) NOT NULL,
  method ENUM('cash','transfer','card','other') NOT NULL DEFAULT 'transfer',
  reference_no VARCHAR(60) NULL,
  received_by INT UNSIGNED NULL,
  CONSTRAINT fk_pay_inv FOREIGN KEY (invoice_id) REFERENCES invoices(id),
  CONSTRAINT fk_pay_user FOREIGN KEY (received_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE expenses (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  expense_date DATE NOT NULL,
  category VARCHAR(60) NOT NULL,
  description VARCHAR(255) NULL,
  amount DECIMAL(14,2) NOT NULL,
  vehicle_id INT UNSIGNED NULL,
  created_by INT UNSIGNED NULL,
  CONSTRAINT fk_exp_veh FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE SET NULL,
  CONSTRAINT fk_exp_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE financial_transactions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  txn_no VARCHAR(24) NOT NULL UNIQUE,
  txn_date DATE NOT NULL,
  type ENUM('income','expense') NOT NULL,
  amount DECIMAL(14,2) NOT NULL,
  payment_id INT UNSIGNED NULL,
  expense_id INT UNSIGNED NULL,
  KEY idx_ft_date (txn_date),
  CONSTRAINT fk_ft_pay FOREIGN KEY (payment_id) REFERENCES payments(id) ON DELETE SET NULL,
  CONSTRAINT fk_ft_exp FOREIGN KEY (expense_id) REFERENCES expenses(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ===== Human Resource =====
CREATE TABLE attendance (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  employee_id INT UNSIGNED NOT NULL,
  work_date DATE NOT NULL,
  check_in TIME NULL, check_out TIME NULL,
  status ENUM('present','late','absent','leave','permit') NOT NULL DEFAULT 'present',
  UNIQUE KEY uq_att (employee_id, work_date),
  CONSTRAINT fk_att_emp FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE leave_requests (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  employee_id INT UNSIGNED NOT NULL,
  type ENUM('annual','sick','permit','other') NOT NULL,
  start_date DATE NOT NULL, end_date DATE NOT NULL,
  reason VARCHAR(255) NULL,
  status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  decided_by INT UNSIGNED NULL,
  CONSTRAINT fk_lr_emp FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
  CONSTRAINT fk_lr_user FOREIGN KEY (decided_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE employee_schedules (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  employee_id INT UNSIGNED NOT NULL,
  work_date DATE NOT NULL,
  shift_start TIME NOT NULL, shift_end TIME NOT NULL,
  UNIQUE KEY uq_sched (employee_id, work_date),
  CONSTRAINT fk_es_emp FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ===== Seed referensi (bukan data transaksi) =====
INSERT INTO roles (slug, name, description) VALUES
 ('administrator','Administrator','Akses penuh sistem'),
 ('managing-director','Managing Director','Monitoring seluruh divisi'),
 ('sales-marketing','Sales & Marketing','CRM, leads, quotation, booking, inbox'),
 ('finance','Finance','Invoice, pembayaran, laporan keuangan'),
 ('operasional','Operasional','Armada, driver, jadwal rental'),
 ('human-resource','Human Resource','Karyawan, absensi, cuti');

INSERT INTO departments (slug, name, description) VALUES
 ('managing-director','Managing Director','Pengawasan & pengambilan keputusan perusahaan'),
 ('sales-marketing','Sales & Marketing','Penjualan, pelanggan, quotation & booking'),
 ('finance','Finance','Invoice, pembayaran, pengeluaran & laporan'),
 ('operasional','Operasional','Armada, pengemudi, jadwal & perawatan'),
 ('human-resource','Human Resource','Karyawan, absensi, cuti & evaluasi');

INSERT INTO channels (`key`, name, group_type, integration_status) VALUES
 ('whatsapp','WhatsApp Business','online','not_configured'),
 ('instagram','Instagram DM','online','not_configured'),
 ('messenger','Facebook Messenger','online','not_configured'),
 ('livechat','Website Live Chat','online','not_configured'),
 ('telegram','Telegram','online','not_configured'),
 ('email','Email','online','not_configured'),
 ('marketplace','Marketplace / Platform Lain','online','not_configured'),
 ('phone','Telepon','offline','manual'),
 ('walkin','Walk-in','offline','manual'),
 ('salesvisit','Sales Visit','offline','manual'),
 ('manual','Interaksi Manual','offline','manual');

INSERT INTO ticket_categories (name) VALUES
 ('Informasi harga rental'),('Booking kendaraan'),('Pembayaran'),('Perubahan jadwal'),('Kendaraan bermasalah'),
 ('Keluhan pelanggan'),('Pembatalan pesanan'),('Permintaan pengemudi'),('Kerja sama korporasi');

INSERT INTO vehicle_categories (name, kind) VALUES
 ('MPV / SUV','passenger'),('Minibus / Van','passenger'),('Mobil Mewah','passenger'),('Truk / Pickup','commercial');
