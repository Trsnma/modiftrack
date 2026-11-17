-- ModifTrack: import via phpMyAdmin (tab Import) atau: mysql -u root < database.sql
CREATE DATABASE IF NOT EXISTS db_modiftrack CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE db_modiftrack;

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,           -- hasil password_hash()
  role ENUM('user','admin') NOT NULL DEFAULT 'user'
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS vehicles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  merk VARCHAR(50) NOT NULL,
  model VARCHAR(50) NOT NULL,
  tahun_rilis INT NOT NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS parts_catalog (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama_part VARCHAR(100) NOT NULL,
  kategori ENUM('Eksterior','Interior','Kaki-kaki','Performa') NOT NULL,
  kompatibel_merk VARCHAR(50) NOT NULL,
  kompatibel_model VARCHAR(50) NOT NULL,
  tahun_min INT NOT NULL,
  tahun_max INT NOT NULL,
  harga_part INT NOT NULL,
  estimasi_jasa INT NOT NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS build_plans (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  vehicle_id INT NOT NULL,
  part_id INT NOT NULL,
  status_beli ENUM('Rencana','Sudah Dibeli','Terpasang') NOT NULL DEFAULT 'Rencana',
  catatan TEXT,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE,
  FOREIGN KEY (part_id) REFERENCES parts_catalog(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Akun admin awal: username "admin" / password "Admin#12345" (WAJIB diganti setelah login pertama)
INSERT IGNORE INTO users (username, password, role) VALUES ('admin', '$2b$12$JsGwE2gkJXI1n7gtqsKfQebpVObugWM4jssTM6gJzHkaqQjpfuLda', 'admin');

-- Data contoh katalog
INSERT INTO parts_catalog (nama_part,kategori,kompatibel_merk,kompatibel_model,tahun_min,tahun_max,harga_part,estimasi_jasa) VALUES
('Knalpot Racing Slip-on','Performa','Honda','Vario 150',2018,2024,1850000,150000),
('Velg Racing 17 inch','Kaki-kaki','Honda','Vario 150',2018,2024,2400000,200000),
('Windshield Touring','Eksterior','Yamaha','NMAX',2015,2024,650000,75000),
('Shockbreaker Belakang Tabung','Kaki-kaki','Yamaha','NMAX',2015,2024,1500000,150000),
('Jok Custom Kulit','Interior','Vespa','Primavera',2014,2024,1200000,100000),
('Filter Udara Racing','Performa','Honda','Vario 150',2018,2024,350000,50000),
('Spion Retro Chrome','Eksterior','Vespa','Primavera',2014,2024,550000,50000),
('Footstep Aluminium','Eksterior','Yamaha','NMAX',2015,2024,450000,60000);
