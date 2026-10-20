-- =====================================================
--  ZENIN - Aplikasi Pengaduan Sarana Sekolah
--  Database : ZENIN   (MySQL / MariaDB - XAMPP)
--  Cara impor: phpMyAdmin -> tab "Import" -> pilih file ini -> Go
--  (database ZENIN dibuat otomatis oleh file ini)
-- =====================================================

CREATE DATABASE IF NOT EXISTS `ZENIN`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `ZENIN`;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `riwayat_status`;
DROP TABLE IF EXISTS `pengaduan`;
DROP TABLE IF EXISTS `kategori`;
DROP TABLE IF EXISTS `users`;
SET FOREIGN_KEY_CHECKS = 1;

-- -----------------------------------------------------
-- Tabel users : akun admin dan siswa (login)
-- -----------------------------------------------------
CREATE TABLE `users` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nama`       VARCHAR(100) NOT NULL,
  `username`   VARCHAR(50)  NOT NULL,
  `email`      VARCHAR(100) NOT NULL,
  `password`   VARCHAR(255) NOT NULL,
  `role`       ENUM('admin','siswa') NOT NULL DEFAULT 'siswa',
  `kelas`      VARCHAR(30)  DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_users_username` (`username`),
  UNIQUE KEY `uk_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Tabel kategori : jenis sarana yang dilaporkan
-- -----------------------------------------------------
CREATE TABLE `kategori` (
  `id`   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nama` VARCHAR(60) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_kategori_nama` (`nama`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Tabel pengaduan : laporan kerusakan sarana oleh siswa
-- -----------------------------------------------------
CREATE TABLE `pengaduan` (
  `id`                 INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`            INT UNSIGNED NOT NULL,
  `kategori_id`        INT UNSIGNED NOT NULL,
  `judul`              VARCHAR(150) NOT NULL,
  `lokasi`             VARCHAR(100) NOT NULL,
  `deskripsi`          TEXT NOT NULL,
  `foto`               VARCHAR(255) DEFAULT NULL,
  `status`             ENUM('Menunggu','Diproses','Selesai','Ditolak') NOT NULL DEFAULT 'Menunggu',
  `tanggal_pengaduan`  DATE NOT NULL,
  `created_at`         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_pengaduan_user` (`user_id`),
  KEY `idx_pengaduan_kategori` (`kategori_id`),
  KEY `idx_pengaduan_status` (`status`),
  KEY `idx_pengaduan_tanggal` (`tanggal_pengaduan`),
  CONSTRAINT `fk_pengaduan_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_pengaduan_kategori` FOREIGN KEY (`kategori_id`) REFERENCES `kategori` (`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Tabel riwayat_status : riwayat perubahan status + umpan balik admin
-- -----------------------------------------------------
CREATE TABLE `riwayat_status` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `pengaduan_id` INT UNSIGNED NOT NULL,
  `admin_id`     INT UNSIGNED DEFAULT NULL,
  `status_lama`  ENUM('Menunggu','Diproses','Selesai','Ditolak') DEFAULT NULL,
  `status_baru`  ENUM('Menunggu','Diproses','Selesai','Ditolak') NOT NULL,
  `umpan_balik`  TEXT DEFAULT NULL,
  `created_at`   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_riwayat_pengaduan` (`pengaduan_id`),
  KEY `idx_riwayat_admin` (`admin_id`),
  CONSTRAINT `fk_riwayat_pengaduan` FOREIGN KEY (`pengaduan_id`) REFERENCES `pengaduan` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_riwayat_admin` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
--  DATA AWAL
--  Akun admin : admin / admin123
--  Akun siswa : budi, sinta, raka, dewi  (password: siswa123)
-- =====================================================
INSERT INTO `users` (`id`,`nama`,`username`,`email`,`password`,`role`,`kelas`) VALUES
(1,'Administrator Sekolah','admin','admin@zenin.sch.id','$2y$10$Xd8KfkrXn5uK/2keM4T8iOIJ0JBaeJEbjXXe4JbMREWMb527EVPCi','admin',NULL),
(2,'Budi Santoso','budi','budi@zenin.sch.id','$2y$10$N2FfRMvhZeAdxPofBTctWuh10PG3/ywSKqFG3g1GPH.9kIRasSU1.','siswa','XII RPL 1'),
(3,'Sinta Maharani','sinta','sinta@zenin.sch.id','$2y$10$N2FfRMvhZeAdxPofBTctWuh10PG3/ywSKqFG3g1GPH.9kIRasSU1.','siswa','XII RPL 2'),
(4,'Raka Pratama','raka','raka@zenin.sch.id','$2y$10$N2FfRMvhZeAdxPofBTctWuh10PG3/ywSKqFG3g1GPH.9kIRasSU1.','siswa','XI TKJ 1'),
(5,'Dewi Anggraini','dewi','dewi@zenin.sch.id','$2y$10$N2FfRMvhZeAdxPofBTctWuh10PG3/ywSKqFG3g1GPH.9kIRasSU1.','siswa','X MM 1');

INSERT INTO `kategori` (`id`,`nama`) VALUES
(1,'Ruang Kelas'),
(2,'Laboratorium'),
(3,'Perpustakaan'),
(4,'Toilet'),
(5,'Kantin'),
(6,'Lapangan & Olahraga'),
(7,'Listrik & Air'),
(8,'Lainnya');

INSERT INTO `pengaduan` (`id`,`user_id`,`kategori_id`,`judul`,`lokasi`,`deskripsi`,`foto`,`status`,`tanggal_pengaduan`) VALUES
(1,2,1,'Kipas angin kelas tidak berfungsi','Ruang XII RPL 1','Kipas angin di sisi kanan ruangan mati total sehingga kelas sangat panas saat siang hari.',NULL,'Selesai','2026-09-14'),
(2,2,2,'Komputer Lab 2 sering restart sendiri','Laboratorium Komputer 2','Tiga unit komputer di barisan belakang restart sendiri saat dipakai praktik.',NULL,'Diproses','2026-10-05'),
(3,3,4,'Keran wastafel toilet putra bocor','Toilet lantai 2','Keran wastafel bocor terus-menerus dan membuat lantai licin.',NULL,'Diproses','2026-10-08'),
(4,3,3,'Lampu rak buku perpustakaan redup','Perpustakaan','Beberapa lampu di area rak referensi berkedip dan redup sehingga sulit membaca.',NULL,'Menunggu','2026-10-15'),
(5,4,6,'Ring basket miring','Lapangan basket','Ring basket sisi timur miring dan berbahaya saat dipakai bermain.',NULL,'Menunggu','2026-10-17'),
(6,4,1,'Papan tulis retak','Ruang XI TKJ 1','Permukaan papan tulis retak sehingga spidol sulit dihapus.',NULL,'Selesai','2026-09-22'),
(7,5,5,'Meja kantin goyang','Kantin utama','Beberapa meja kantin tidak stabil dan membuat minuman tumpah.',NULL,'Ditolak','2026-10-02'),
(8,5,7,'Stop kontak di kelas longgar','Ruang X MM 1','Stop kontak dekat meja guru longgar dan sempat mengeluarkan percikan kecil.',NULL,'Menunggu','2026-10-19');

INSERT INTO `riwayat_status` (`pengaduan_id`,`admin_id`,`status_lama`,`status_baru`,`umpan_balik`,`created_at`) VALUES
(1,1,'Menunggu','Diproses','Laporan diterima, teknisi akan memeriksa kipas angin.','2026-09-15 09:10:00'),
(1,1,'Diproses','Selesai','Kipas angin sudah diperbaiki dan berfungsi normal.','2026-09-18 13:30:00'),
(2,1,'Menunggu','Diproses','Tim lab sedang memeriksa catu daya komputer.','2026-10-06 10:00:00'),
(3,1,'Menunggu','Diproses','Petugas sarana akan mengganti keran pada hari Jumat.','2026-10-09 08:45:00'),
(6,1,'Menunggu','Diproses','Papan tulis dijadwalkan untuk diganti.','2026-09-23 11:00:00'),
(6,1,'Diproses','Selesai','Papan tulis baru sudah terpasang.','2026-09-30 14:15:00'),
(7,1,'Menunggu','Ditolak','Meja sudah dijadwalkan diganti pada anggaran semester depan, sementara mohon digunakan dengan hati-hati.','2026-10-03 10:20:00');
