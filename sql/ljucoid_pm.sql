-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Nov 04, 2026 at 12:13 PM
-- Server version: 11.4.13-MariaDB-cll-lve-log
-- PHP Version: 8.4.27

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ljucoid_pm`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_log`
--

CREATE TABLE `activity_log` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `project_id` int(11) DEFAULT NULL,
  `task_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `details` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_log`
--

INSERT INTO `activity_log` (`id`, `user_id`, `project_id`, `task_id`, `action`, `details`, `created_at`) VALUES
(1, 7, 1, NULL, 'project_created', 'Project \"Perbaikan Aplikasi Naskah Dinas\" created', '2026-09-25 09:19:44'),
(2, 7, 2, NULL, 'project_created', 'Project \"Perbaikan App\" created', '2026-09-25 09:21:26'),
(3, 7, 2, 1, 'task_created', 'Task \"Tambah fitur 1\" created', '2026-09-25 09:21:58'),
(4, 7, 2, 1, 'task_moved', 'Task moved to In Progress', '2026-09-25 09:27:40'),
(5, 7, 2, 1, 'task_moved', 'Task moved to Done', '2026-09-25 09:27:47'),
(6, 7, 2, 1, 'task_moved', 'Task moved to In Progress', '2026-09-25 09:27:49'),
(7, 7, 1, 2, 'task_created', 'Task \"coba\" created', '2026-09-25 09:36:13'),
(8, 7, 2, 1, 'task_moved', 'Task moved to Done', '2026-09-25 09:36:51'),
(9, 7, 1, NULL, 'task_deleted', 'Task dihapus oleh pembuat: coba', '2026-09-25 10:12:44'),
(10, 7, NULL, NULL, 'project_deleted', 'Project deleted: Perbaikan Aplikasi Naskah Dinas', '2026-09-25 10:13:11'),
(11, 7, NULL, NULL, 'project_deleted', 'Project dihapus oleh pembuat: Perbaikan App', '2026-09-25 10:15:55'),
(12, 7, 3, NULL, 'project_created', 'Project \"Perbaikan website\" created', '2026-09-25 10:26:31'),
(13, 7, 3, 3, 'task_created', 'Task \"Tambah menu\" created', '2026-09-25 10:26:59'),
(14, 7, 3, NULL, 'project_updated', 'Project updated', '2026-09-25 10:49:07'),
(15, 7, 3, 3, 'task_updated', 'Task updated: Tambah menu', '2026-09-25 10:49:33'),
(16, 7, 3, NULL, 'project_updated', 'Project updated: Privacy=all', '2026-09-25 10:51:10'),
(17, 7, 3, NULL, 'column_added', 'Column added: Trouble/Blocking', '2026-09-25 10:51:45'),
(18, 7, 3, 3, 'task_moved', 'Task moved to Trouble/Blocking', '2026-09-25 10:52:00'),
(19, 7, 3, 3, 'task_moved', 'Task moved to In Progress', '2026-09-25 10:52:24'),
(20, 7, 3, NULL, 'project_updated', 'Project updated: Privacy=all', '2026-09-25 10:53:45'),
(21, 7, NULL, NULL, 'project_deleted', 'Project dihapus: Perbaikan website', '2026-09-25 10:54:10'),
(22, 7, 4, NULL, 'project_created', 'Project \"Perbaikan / Pembuatan Aplikasi LJU\" created', '2026-09-25 13:52:11'),
(23, 7, 4, 4, 'task_created', 'Task \"Buat Fitur &quot;Surat Keluar&quot;\" created', '2026-09-25 13:53:00'),
(24, 7, 4, 4, 'task_updated', 'Task updated: Buat Fitur Surat Keluar', '2026-09-25 13:53:18'),
(25, 7, 4, 5, 'task_created', 'Task \"Buat Fitur Surat Tugas\" created', '2026-09-25 13:54:00'),
(26, 7, 4, 6, 'task_created', 'Task \"Buat Aplikasi Whistleblowing system LJU\" created', '2026-09-25 13:54:28'),
(27, 7, 4, 7, 'task_created', 'Task \"Buat Dashboard utama Aplikasi LJU\" created', '2026-09-25 13:55:13'),
(28, 7, 4, 6, 'task_moved', 'Task moved to In Progress', '2026-09-25 13:55:24'),
(29, 7, 4, 7, 'task_moved', 'Task moved to In Progress', '2026-09-25 13:55:31'),
(30, 7, 4, 8, 'task_created', 'Task \"Buat halaman input berita website\" created', '2026-09-25 13:56:49'),
(31, 7, 4, 9, 'task_created', 'Task \"Buat Aplikasi Project Management\" created', '2026-09-25 13:57:27'),
(32, 7, 4, 9, 'task_moved', 'Task moved to Testing', '2026-09-25 13:57:30'),
(33, 7, 5, NULL, 'project_created', 'Project \"Manage Servive Jaringan Kominfo Lampngprov\" created', '2026-09-25 13:58:18'),
(34, 7, 5, NULL, 'project_updated', 'Project updated: Privacy=division', '2026-09-25 13:58:30'),
(35, 7, 5, 10, 'task_created', 'Task \"Cek Internet Inspektorat\" created', '2026-09-25 13:59:00'),
(36, 7, 5, 10, 'task_moved', 'Task moved to In Progress', '2026-09-25 13:59:02'),
(37, 7, 4, NULL, 'project_updated', 'Project updated: Privacy=division', '2026-09-25 14:00:40'),
(38, 7, 5, NULL, 'project_updated', 'Project updated: Privacy=division', '2026-09-25 14:00:52'),
(39, 7, 4, 7, 'task_moved', 'Task moved to Testing', '2026-09-25 14:01:46'),
(40, 29, 6, NULL, 'project_created', 'Project \"Pendataan Inventaris Kantor\" created', '2026-09-26 04:22:36'),
(41, 7, 4, 6, 'task_moved', 'Task moved to Testing', '2026-09-26 04:43:04'),
(42, 7, 4, 5, 'task_moved', 'Task moved to Testing', '2026-09-27 11:57:09'),
(43, 7, 4, 4, 'task_moved', 'Task moved to In Progress', '2026-09-27 11:57:13'),
(44, 7, 4, 8, 'task_moved', 'Task moved to To Do', '2026-09-27 11:57:15'),
(45, 7, 4, 7, 'task_moved', 'Task moved to Done', '2026-09-27 11:57:24'),
(46, 1, 7, NULL, 'project_created', 'Project \"Pembuatan Website PT.LJU (Contoh Project Public View)\" created', '2026-09-28 02:10:34'),
(47, 7, 7, 11, 'task_created', 'Task \"Perancangan Layout Website\" created', '2026-09-28 02:11:18'),
(48, 7, 7, 12, 'task_created', 'Task \"Penentuan Komponen Website\" created', '2026-09-28 02:11:39'),
(49, 7, 7, 13, 'task_created', 'Task \"Penentuan Isi Komponen website\" created', '2026-09-28 02:12:14'),
(50, 7, 7, 11, 'task_moved', 'Task moved to Done', '2026-09-28 02:12:18'),
(51, 7, 7, 12, 'task_moved', 'Task moved to Done', '2026-09-28 02:12:19'),
(52, 7, 7, 13, 'task_moved', 'Task moved to In Progress', '2026-09-28 02:12:21'),
(53, 7, 7, 14, 'task_created', 'Task \"Pembuatan Image/Gambar isi website\" created', '2026-09-28 02:12:50'),
(54, 7, 7, 14, 'task_moved', 'Task moved to In Progress', '2026-09-28 02:12:52'),
(55, 7, 7, 13, 'task_moved', 'Task moved to Done', '2026-09-28 02:12:56'),
(56, 7, 7, 15, 'task_created', 'Task \"Pembuatan video Product Knowledge\" created', '2026-09-28 02:13:16'),
(57, 7, 7, 15, 'task_moved', 'Task moved to In Progress', '2026-09-28 02:13:18'),
(58, 7, 7, 15, 'task_updated', 'Task updated: Pembuatan video Product Knowledge', '2026-09-28 02:13:40'),
(59, 7, 7, 13, 'task_updated', 'Task updated: Penentuan Isi Komponen website', '2026-09-28 02:13:52'),
(60, 7, 7, 15, 'task_moved', 'Task moved to Done', '2026-09-28 02:14:00'),
(61, 7, 7, 16, 'task_created', 'Task \"Coding Proses\" created', '2026-09-28 02:14:19'),
(62, 7, 7, 16, 'task_moved', 'Task moved to In Progress', '2026-09-28 02:14:22'),
(63, 7, 4, 9, 'task_moved', 'Task moved to Testing', '2026-09-28 03:05:06'),
(64, 7, 4, 9, 'task_moved', 'Task moved to Done', '2026-09-28 03:05:09'),
(65, 7, 4, 6, 'task_moved', 'Task moved to Done', '2026-09-28 03:05:13'),
(66, 7, 5, 10, 'task_moved', 'Task moved to Done', '2026-09-28 03:05:44'),
(67, 7, 8, NULL, 'project_created', 'Project \"Pembuatan Aplikasi HRD\" created', '2026-09-29 02:49:15'),
(68, 7, 8, 17, 'task_created', 'Task \"Buat daftar modul/fitur\" created', '2026-09-29 02:50:05'),
(69, 7, 8, 18, 'task_created', 'Task \"Buat rule/sistem flow tiap modul/fitur\" created', '2026-09-29 02:52:13'),
(70, 7, 5, 19, 'task_created', 'Task \"Ruang Rapat Gub\" created', '2026-09-29 03:18:42'),
(71, 8, 7, 16, 'task_moved', 'Task moved to In Progress', '2026-09-30 13:34:00'),
(72, 8, 7, 14, 'task_moved', 'Task moved to In Progress', '2026-09-30 13:34:02'),
(73, 8, 7, 14, 'task_moved', 'Task moved to Done', '2026-09-30 13:34:04'),
(74, 8, 7, 16, 'task_moved', 'Task moved to To Do', '2026-09-30 13:34:09'),
(75, 8, 7, 11, 'task_moved', 'Task moved to Done', '2026-09-30 13:34:15'),
(76, 8, 7, 11, 'task_moved', 'Task moved to Done', '2026-09-30 13:34:15'),
(77, 8, 7, 14, 'task_moved', 'Task moved to Done', '2026-09-30 13:34:17'),
(78, 8, 7, 16, 'task_moved', 'Task moved to In Progress', '2026-09-30 13:34:21'),
(79, 8, 7, 12, 'task_moved', 'Task moved to Done', '2026-09-30 13:34:29'),
(80, 8, 5, 19, 'task_moved', 'Task moved to In Progress', '2026-10-01 02:23:51'),
(81, 8, 5, 19, 'task_moved', 'Task moved to Done', '2026-10-01 02:23:54'),
(82, 8, 4, 9, 'task_moved', 'Task moved to Done', '2026-10-01 06:14:18'),
(83, 8, 4, 9, 'task_moved', 'Task moved to Done', '2026-10-01 06:14:22'),
(84, 8, 4, 7, 'task_moved', 'Task moved to Done', '2026-10-01 06:14:23'),
(85, 8, 4, 8, 'task_moved', 'Task moved to To Do', '2026-10-01 06:14:37'),
(86, 8, 7, 11, 'task_moved', 'Task moved to Done', '2026-10-01 06:15:04'),
(87, 8, 7, 13, 'task_moved', 'Task moved to Done', '2026-10-01 06:15:05'),
(88, 8, 7, 13, 'task_moved', 'Task moved to Done', '2026-10-01 06:15:09'),
(89, 8, 7, 13, 'task_moved', 'Task moved to Done', '2026-10-01 06:15:10'),
(90, 8, 7, 13, 'task_moved', 'Task moved to Done', '2026-10-01 06:15:18'),
(91, 8, 7, 12, 'task_moved', 'Task moved to Done', '2026-10-01 06:15:20'),
(92, 23, 9, NULL, 'project_created', 'Project \"Kerjasama Agribisnis Jagung Pringsewu\" created', '2026-10-01 06:23:51'),
(93, 8, 8, 18, 'task_moved', 'Task moved to To Do', '2026-10-01 06:24:52'),
(94, 8, 8, 18, 'task_moved', 'Task moved to In Progress', '2026-10-01 06:24:54'),
(95, 8, 8, 17, 'task_moved', 'Task moved to In Progress', '2026-10-01 06:26:22'),
(96, 23, 9, 20, 'task_created', 'Task \"Ketemu dengan Koordinator Petani Jagung Pringsewu\" created', '2026-10-01 06:26:40'),
(97, 23, 9, 20, 'task_moved', 'Task moved to To Do', '2026-10-01 06:26:55'),
(98, 23, 9, 20, 'task_moved', 'Task moved to Testing', '2026-10-01 06:27:00'),
(99, 23, 9, 20, 'task_moved', 'Task moved to Done', '2026-10-01 06:27:05'),
(100, 23, 9, 21, 'task_created', 'Task \"Update dengan kordinator petani\" created', '2026-10-01 06:33:47'),
(101, 23, 9, 21, 'task_moved', 'Task moved to To Do', '2026-10-01 06:33:55'),
(102, 23, 9, 21, 'task_moved', 'Task moved to To Do', '2026-10-01 06:36:57'),
(103, 23, 9, 21, 'task_moved', 'Task moved to To Do', '2026-10-01 06:37:09'),
(104, 28, 9, 21, 'task_moved', 'Task moved to Trouble/Blocking', '2026-10-01 06:37:20'),
(105, 28, 9, 21, 'task_moved', 'Task moved to Trouble/Blocking', '2026-10-01 06:37:29'),
(106, 28, 9, 21, 'task_moved', 'Task moved to Trouble/Blocking', '2026-10-01 06:37:29'),
(107, 28, 9, 20, 'task_moved', 'Task moved to Done', '2026-10-01 06:38:20'),
(108, 28, 9, 21, 'task_moved', 'Task moved to Done', '2026-10-01 06:38:44'),
(109, 28, 9, 20, 'task_moved', 'Task moved to To Do', '2026-10-01 06:39:03'),
(110, 23, 9, 20, 'task_moved', 'Task moved to Done', '2026-10-01 06:39:04'),
(111, 23, 9, 21, 'task_moved', 'Task moved to Done', '2026-10-01 06:39:05'),
(112, 23, 9, 20, 'task_moved', 'Task moved to Done', '2026-10-01 06:39:12'),
(113, 23, 9, 21, 'task_moved', 'Task moved to Done', '2026-10-01 06:39:12'),
(114, 28, 9, 21, 'task_moved', 'Task moved to To Do', '2026-10-01 06:39:25'),
(115, 23, 9, 20, 'task_moved', 'Task moved to Testing', '2026-10-01 06:39:53'),
(116, 23, 9, 20, 'task_moved', 'Task moved to Testing', '2026-10-01 06:39:57'),
(117, 23, 9, 20, 'task_moved', 'Task moved to Testing', '2026-10-01 06:40:05'),
(118, 23, 9, 20, 'task_moved', 'Task moved to Done', '2026-10-01 06:40:12'),
(119, 23, 9, 20, 'task_moved', 'Task moved to Done', '2026-10-01 06:42:00'),
(120, 23, 9, 21, 'task_moved', 'Task moved to To Do', '2026-10-01 06:43:25'),
(121, 23, 9, 22, 'task_created', 'Task \"Trs\" created', '2026-10-01 06:43:32'),
(122, 23, 9, 21, 'task_moved', 'Task moved to New Request', '2026-10-01 06:43:58'),
(123, 23, 9, 21, 'task_moved', 'Task moved to Backlog', '2026-10-01 06:44:07'),
(124, 23, 9, 21, 'task_moved', 'Task moved to New Request', '2026-10-01 06:44:10'),
(125, 23, 9, 21, 'task_moved', 'Task moved to New Request', '2026-10-01 06:44:12'),
(126, 23, 9, 21, 'task_moved', 'Task moved to To Do', '2026-10-01 06:44:14'),
(127, 23, 9, 21, 'task_moved', 'Task moved to To Do', '2026-10-01 06:44:19'),
(128, 23, 9, 22, 'task_moved', 'Task moved to To Do', '2026-10-01 06:44:21'),
(129, 23, 9, NULL, 'task_deleted', 'Task dihapus: Trs', '2026-10-01 06:44:26'),
(130, 23, 9, 21, 'task_moved', 'Task moved to To Do', '2026-10-01 06:44:32'),
(131, 23, 9, 21, 'task_moved', 'Task moved to To Do', '2026-10-01 06:45:16'),
(132, 23, 9, 21, 'task_moved', 'Task moved to To Do', '2026-10-01 06:45:19'),
(133, 23, 9, NULL, 'task_deleted', 'Task dihapus: Update dengan kordinator petani', '2026-10-01 06:45:38'),
(134, 23, 9, NULL, 'task_deleted', 'Task dihapus: Ketemu dengan Koordinator Petani Jagung Pringsewu', '2026-10-01 06:45:49'),
(135, 23, NULL, NULL, 'project_deleted', 'Project dihapus: Kerjasama Agribisnis Jagung Pringsewu', '2026-10-01 06:46:01'),
(136, 8, 7, 23, 'task_created', 'Task \"Pengembangan Webiste\" created', '2026-10-01 07:03:35'),
(137, 8, 7, NULL, 'task_deleted', 'Task dihapus: Pengembangan Webiste', '2026-10-01 07:04:46'),
(138, 7, 8, 17, 'task_moved', 'Task moved to Done', '2026-10-04 05:27:59'),
(139, 7, 8, 18, 'task_moved', 'Task moved to Done', '2026-10-04 05:28:02'),
(140, 7, 8, 24, 'task_created', 'Task \"Modul Data Karyawan\" created', '2026-10-04 05:28:30'),
(141, 7, 8, 25, 'task_created', 'Task \"Modul Dokumen Karyawan\" created', '2026-10-04 05:29:10'),
(142, 7, 8, 26, 'task_created', 'Task \"Modul Presensi\" created', '2026-10-04 05:30:09'),
(143, 7, 7, 16, 'task_moved', 'Task moved to In Progress', '2026-10-04 05:30:20'),
(144, 7, 4, 8, 'task_moved', 'Task moved to In Progress', '2026-10-04 05:30:31'),
(145, 8, 4, 8, 'task_moved', 'Task moved to Testing', '2026-10-07 03:16:11'),
(146, 8, 4, 8, 'task_moved', 'Task moved to In Progress', '2026-10-07 03:16:15'),
(147, 8, 4, 8, 'task_moved', 'Task moved to Testing', '2026-10-07 03:16:19'),
(148, 8, 4, 8, 'task_moved', 'Task moved to Done', '2026-10-07 03:16:21'),
(149, 8, 4, 5, 'task_moved', 'Task moved to Done', '2026-10-07 03:16:31'),
(150, 8, 4, 4, 'task_moved', 'Task moved to Done', '2026-10-07 03:16:32'),
(151, 8, 7, 16, 'task_moved', 'Task moved to Done', '2026-10-07 03:17:25'),
(152, 7, 4, 7, 'task_moved', 'Task moved to Testing', '2026-10-11 03:42:54'),
(153, 7, 4, 7, 'task_moved', 'Task moved to Done', '2026-10-11 03:42:58'),
(154, 7, 4, NULL, 'task_deleted', 'Task deleted: Buat halaman input berita website', '2026-10-11 04:00:31'),
(155, 7, 4, 27, 'task_created', 'Task \"coba\" created', '2026-10-14 02:17:53'),
(156, 7, 4, 27, 'task_moved', 'Task moved to To Do', '2026-10-14 03:58:06'),
(157, 7, 4, 27, 'task_updated', 'Task diperbarui via halaman edit', '2026-10-14 04:05:44'),
(158, 7, 4, NULL, 'task_deleted', 'Task dihapus: coba', '2026-10-14 04:10:14'),
(159, 7, 8, 28, 'task_created', 'Task \"Modul Lembur\" created', '2026-10-14 04:14:59'),
(160, 7, 8, 29, 'task_created', 'Task \"Modul cuti\" created', '2026-10-14 04:15:30'),
(161, 7, 10, NULL, 'project_created', 'Project \"Pebuatan Aplikasi Perlengkapan &amp; Pengadaan Barang\" created', '2026-10-14 04:21:38'),
(162, 7, 10, NULL, 'project_updated', 'Project updated: Privacy=team', '2026-10-14 04:22:01');

-- --------------------------------------------------------

--
-- Table structure for table `projects`
--

CREATE TABLE `projects` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_by` int(11) NOT NULL COMMENT 'user_id dari tabel users di DB lain',
  `project_leader_id` int(11) NOT NULL COMMENT 'user_id project leader',
  `start_date` date DEFAULT NULL,
  `target_end_date` date DEFAULT NULL,
  `actual_end_date` date DEFAULT NULL,
  `privacy` enum('personal','team','division','department','all') NOT NULL DEFAULT 'team',
  `status` enum('planning','active','on_hold','completed','cancelled') NOT NULL DEFAULT 'planning',
  `priority` enum('low','medium','high','critical') NOT NULL DEFAULT 'medium',
  `progress_percentage` decimal(5,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `projects`
--

INSERT INTO `projects` (`id`, `title`, `description`, `created_by`, `project_leader_id`, `start_date`, `target_end_date`, `actual_end_date`, `privacy`, `status`, `priority`, `progress_percentage`, `created_at`, `updated_at`) VALUES
(4, 'Perbaikan / Pembuatan Aplikasi LJU', 'Perbaikan minor / major aplikasi LJU', 7, 8, '2026-09-25', '2026-12-31', '2026-10-07', 'division', 'completed', 'medium', 100.00, '2026-09-25 13:52:11', '2026-10-14 04:10:14'),
(5, 'Manage Service Jaringan Kominfo Lampngprov', '', 7, 8, '2026-09-25', '2026-12-31', '2026-09-28', 'division', 'completed', 'medium', 100.00, '2026-09-25 13:58:18', '2026-10-01 02:23:54'),
(6, 'Pendataan Inventaris Kantor', '', 29, 29, '2026-09-26', '2026-10-31', NULL, 'division', 'active', 'medium', 0.00, '2026-09-26 04:22:36', '2026-09-26 04:22:36'),
(7, 'Pembuatan Website PT.LJU (Contoh Project Public View)', '', 1, 8, '2026-08-01', '2026-09-30', '2026-10-07', 'all', 'completed', 'high', 100.00, '2026-09-28 02:10:34', '2026-10-07 03:17:25'),
(8, 'Pembuatan Aplikasi HRD', '', 7, 8, '2026-09-29', '2026-11-20', '2026-10-04', 'division', 'completed', 'medium', 28.57, '2026-09-29 02:49:15', '2026-10-14 04:15:30'),
(10, 'Pebuatan Aplikasi Perlengkapan dan Pengadaan Barang', 'aplikasi perlengkapan, inventaris, pengajuan pengadaan barang', 7, 8, '2026-10-14', '2026-11-30', NULL, 'team', 'active', 'medium', 0.00, '2026-10-14 04:21:38', '2026-10-14 04:22:01');

-- --------------------------------------------------------

--
-- Table structure for table `project_columns`
--

CREATE TABLE `project_columns` (
  `id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `position` int(11) NOT NULL DEFAULT 0,
  `color` varchar(7) DEFAULT '#6c757d',
  `wip_limit` int(11) DEFAULT NULL COMMENT 'Work In Progress limit',
  `is_done_column` tinyint(1) DEFAULT 0 COMMENT 'Menandai kolom sebagai kolom selesai',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `project_columns`
--

INSERT INTO `project_columns` (`id`, `project_id`, `title`, `slug`, `position`, `color`, `wip_limit`, `is_done_column`, `created_at`) VALUES
(13, 4, 'New Request', 'new_request', 0, '#0dcaf0', NULL, 0, '2026-09-25 13:52:11'),
(14, 4, 'To Do', 'todo', 1, '#ffc107', NULL, 0, '2026-09-25 13:52:11'),
(15, 4, 'In Progress', 'in_progress', 2, '#0d6efd', NULL, 0, '2026-09-25 13:52:11'),
(16, 4, 'Testing', 'testing', 3, '#6f42c1', NULL, 0, '2026-09-25 13:52:11'),
(17, 4, 'Done', 'done', 4, '#198754', NULL, 1, '2026-09-25 13:52:11'),
(18, 5, 'To Do', 'todo', 0, '#ffc107', NULL, 0, '2026-09-25 13:58:18'),
(19, 5, 'In Progress', 'in_progress', 1, '#0d6efd', NULL, 0, '2026-09-25 13:58:18'),
(20, 5, 'Done', 'done', 2, '#198754', NULL, 1, '2026-09-25 13:58:18'),
(21, 6, 'To Do', 'todo', 0, '#ffc107', NULL, 0, '2026-09-26 04:22:36'),
(22, 6, 'In Progress', 'in_progress', 1, '#0d6efd', NULL, 0, '2026-09-26 04:22:36'),
(23, 6, 'Done', 'done', 2, '#198754', NULL, 1, '2026-09-26 04:22:36'),
(24, 7, 'To Do', 'todo', 0, '#ffc107', NULL, 0, '2026-09-28 02:10:34'),
(25, 7, 'In Progress', 'in_progress', 1, '#0d6efd', NULL, 0, '2026-09-28 02:10:34'),
(26, 7, 'Done', 'done', 2, '#198754', NULL, 1, '2026-09-28 02:10:34'),
(27, 8, 'To Do', 'todo', 0, '#ffc107', NULL, 0, '2026-09-29 02:49:15'),
(28, 8, 'In Progress', 'in_progress', 1, '#0d6efd', NULL, 0, '2026-09-29 02:49:15'),
(29, 8, 'Testing', 'testing', 2, '#6f42c1', NULL, 0, '2026-09-29 02:49:15'),
(30, 8, 'Done', 'done', 3, '#198754', NULL, 1, '2026-09-29 02:49:15'),
(38, 10, 'To Do', 'todo', 0, '#ffc107', NULL, 0, '2026-10-14 04:21:38'),
(39, 10, 'In Progress', 'in_progress', 1, '#0d6efd', NULL, 0, '2026-10-14 04:21:38'),
(40, 10, 'Testing', 'testing', 2, '#6f42c1', NULL, 0, '2026-10-14 04:21:38'),
(41, 10, 'Done', 'done', 3, '#198754', NULL, 1, '2026-10-14 04:21:38');

-- --------------------------------------------------------

--
-- Table structure for table `project_members`
--

CREATE TABLE `project_members` (
  `id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `role` enum('leader','member','viewer') NOT NULL DEFAULT 'member',
  `added_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `added_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `project_members`
--

INSERT INTO `project_members` (`id`, `project_id`, `user_id`, `role`, `added_at`, `added_by`) VALUES
(5, 4, 8, 'leader', '2026-09-25 13:52:11', 7),
(6, 5, 8, 'leader', '2026-09-25 13:58:18', 7),
(7, 4, 7, 'member', '2026-09-25 14:00:40', 7),
(8, 5, 7, 'member', '2026-09-25 14:00:52', 7),
(9, 6, 29, 'leader', '2026-09-26 04:22:36', 29),
(10, 7, 8, 'leader', '2026-09-28 02:10:34', 1),
(11, 7, 7, 'member', '2026-09-28 02:10:34', 1),
(12, 8, 8, 'leader', '2026-09-29 02:49:15', 7),
(13, 8, 7, 'member', '2026-09-29 02:49:15', 7),
(16, 10, 8, 'leader', '2026-10-14 04:21:38', 7),
(18, 10, 7, 'member', '2026-10-14 04:22:01', 7);

-- --------------------------------------------------------

--
-- Table structure for table `project_status_history`
--

CREATE TABLE `project_status_history` (
  `id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `from_status` enum('planning','active','on_hold','completed','cancelled') DEFAULT NULL,
  `to_status` enum('planning','active','on_hold','completed','cancelled') NOT NULL,
  `changed_by` int(11) NOT NULL,
  `changed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `project_status_history`
--

INSERT INTO `project_status_history` (`id`, `project_id`, `from_status`, `to_status`, `changed_by`, `changed_at`, `notes`) VALUES
(5, 4, NULL, 'active', 7, '2026-09-25 13:52:11', 'Project created'),
(6, 5, NULL, 'active', 7, '2026-09-25 13:58:18', 'Project created'),
(7, 6, NULL, 'active', 29, '2026-09-26 04:22:36', 'Project created'),
(8, 7, NULL, 'active', 1, '2026-09-28 02:10:34', 'Project created'),
(9, 8, NULL, 'active', 7, '2026-09-29 02:49:15', 'Project created'),
(11, 10, NULL, 'active', 7, '2026-10-14 04:21:38', 'Project created');

-- --------------------------------------------------------

--
-- Table structure for table `tasks`
--

CREATE TABLE `tasks` (
  `id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `column_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `assigned_to` int(11) DEFAULT NULL COMMENT 'user_id yang ditugaskan',
  `created_by` int(11) NOT NULL,
  `start_date` date DEFAULT NULL,
  `target_end_date` date DEFAULT NULL,
  `actual_end_date` date DEFAULT NULL,
  `card_color` varchar(7) DEFAULT '#ffffff',
  `priority` enum('low','medium','high','critical') NOT NULL DEFAULT 'medium',
  `position` int(11) NOT NULL DEFAULT 0 COMMENT 'urutan dalam kolom',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tasks`
--

INSERT INTO `tasks` (`id`, `project_id`, `column_id`, `title`, `description`, `notes`, `assigned_to`, `created_by`, `start_date`, `target_end_date`, `actual_end_date`, `card_color`, `priority`, `position`, `created_at`, `updated_at`) VALUES
(4, 4, 17, 'Buat Fitur Surat Keluar', 'penambahan fitur pembuatan surat keluar di aplikasi DMS (Naskah Dinas)', '', 7, 7, '2026-09-25', NULL, '2026-10-07', '#fff3cd', 'medium', 2, '2026-09-25 13:53:00', '2026-10-11 03:42:58'),
(5, 4, 17, 'Buat Fitur Surat Tugas', 'pnambahan fitur surat tugas di aplikasi DMS(naskah dinas)', '', 7, 7, '2026-09-25', NULL, '2026-10-07', '#fff3cd', 'medium', 3, '2026-09-25 13:54:00', '2026-10-11 03:42:58'),
(6, 4, 17, 'Buat Aplikasi Whistleblowing system LJU', '', '', 7, 7, '2026-09-25', NULL, '2026-09-28', '#cff4fc', 'medium', 5, '2026-09-25 13:54:28', '2026-10-11 03:42:58'),
(7, 4, 17, 'Buat Dashboard utama Aplikasi LJU', 'semua aplikasi LJU tampil di satu dashboard, switch lebih mudah, single sign-on system', '', 7, 7, '2026-09-25', NULL, '2026-10-11', '#d1e7dd', 'high', 0, '2026-09-25 13:55:13', '2026-10-11 03:42:58'),
(9, 4, 17, 'Buat Aplikasi Project Management', 'membangun aplikasi project manajemen LJU', '', 7, 7, '2026-09-25', NULL, '2026-09-28', '#e2d9f3', 'high', 4, '2026-09-25 13:57:27', '2026-10-11 03:42:58'),
(10, 5, 20, 'Cek Internet Inspektorat', '', '', 7, 7, '2026-09-25', '2026-09-26', '2026-09-28', '#fff3cd', 'medium', 0, '2026-09-25 13:59:00', '2026-09-28 03:05:44'),
(11, 7, 26, 'Perancangan Layout Website', '', '', 7, 7, '2026-09-28', NULL, '2026-09-28', '#fff3cd', 'high', 0, '2026-09-28 02:11:18', '2026-10-01 06:15:04'),
(12, 7, 26, 'Penentuan Komponen Website', '', '', 7, 7, '2026-09-28', NULL, '2026-09-28', '#fff3cd', 'medium', 4, '2026-09-28 02:11:39', '2026-10-07 03:17:25'),
(13, 7, 26, 'Penentuan Isi Komponen website', '', '', 8, 7, '2026-09-28', NULL, '2026-09-28', '#fff3cd', 'medium', 3, '2026-09-28 02:12:14', '2026-10-07 03:17:25'),
(14, 7, 26, 'Pembuatan Image/Gambar isi website', '', '', 7, 7, '2026-09-28', NULL, '2026-09-30', '#cff4fc', 'medium', 1, '2026-09-28 02:12:50', '2026-10-01 06:15:04'),
(15, 7, 26, 'Pembuatan video Product Knowledge', '', '', 8, 7, '2026-09-28', NULL, '2026-09-28', '#cff4fc', 'medium', 5, '2026-09-28 02:13:16', '2026-10-07 03:17:25'),
(16, 7, 26, 'Coding Proses', '', '', 7, 7, '2026-09-28', NULL, '2026-10-07', '#f8d7da', 'medium', 2, '2026-09-28 02:14:19', '2026-10-07 03:17:25'),
(17, 8, 30, 'Buat daftar modul/fitur', 'tentukan modul/fitur yang akan dibuat ke dalam sistem', '', 7, 7, '2026-09-29', NULL, '2026-10-04', '#fff3cd', 'medium', 0, '2026-09-29 02:50:05', '2026-10-04 05:27:59'),
(18, 8, 30, 'Buat rule/sistem flow tiap modul/fitur', '', '', 7, 7, '2026-09-29', NULL, '2026-10-04', '#fff3cd', 'medium', 1, '2026-09-29 02:52:13', '2026-10-04 05:28:02'),
(19, 5, 20, 'Ruang Rapat Gub', 'internet down', '', 7, 7, '2026-09-29', NULL, '2026-10-01', '#fff3cd', 'medium', 1, '2026-09-29 03:18:41', '2026-10-01 02:23:54'),
(24, 8, 27, 'Modul Data Karyawan', 'pembuatan modul data detil karyawan', '', 7, 7, '2026-10-04', NULL, NULL, '#cff4fc', 'medium', 0, '2026-10-04 05:28:30', '2026-10-04 05:28:30'),
(25, 8, 27, 'Modul Dokumen Karyawan', 'buat modul dokumen karyawan (ijazah, sertifikat,dll) --&gt; review HRD', '', 7, 7, '2026-10-04', NULL, NULL, '#cff4fc', 'medium', 1, '2026-10-04 05:29:10', '2026-10-04 05:29:10'),
(26, 8, 27, 'Modul Presensi', 'Buat modul pencatatan kehadiran online (dengan GPS, selfie camera) - geofence - radius absensi', '', 7, 7, '2026-10-04', NULL, NULL, '#cff4fc', 'medium', 2, '2026-10-04 05:30:09', '2026-10-04 05:30:09'),
(28, 8, 27, 'Modul Lembur', 'modul pengajuan, persetujuan, perhitungan lembur', '', 7, 7, '2026-10-14', NULL, NULL, '#cff4fc', 'medium', 3, '2026-10-14 04:14:59', '2026-10-14 04:14:59'),
(29, 8, 27, 'Modul cuti', 'modul pengajuan,persetujuan cuti', '', 7, 7, '2026-10-14', NULL, NULL, '#cff4fc', 'medium', 4, '2026-10-14 04:15:30', '2026-10-14 04:15:30');

-- --------------------------------------------------------

--
-- Table structure for table `task_attachments`
--

CREATE TABLE `task_attachments` (
  `id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `file_size` int(11) DEFAULT NULL,
  `file_type` varchar(100) DEFAULT NULL,
  `uploaded_by` int(11) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `task_comments`
--

CREATE TABLE `task_comments` (
  `id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `comment` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `task_status_history`
--

CREATE TABLE `task_status_history` (
  `id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL,
  `from_column_id` int(11) DEFAULT NULL,
  `to_column_id` int(11) NOT NULL,
  `changed_by` int(11) NOT NULL,
  `changed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `task_status_history`
--

INSERT INTO `task_status_history` (`id`, `task_id`, `from_column_id`, `to_column_id`, `changed_by`, `changed_at`, `notes`) VALUES
(10, 4, NULL, 13, 7, '2026-09-25 13:53:00', 'Task created'),
(11, 5, NULL, 13, 7, '2026-09-25 13:54:00', 'Task created'),
(12, 6, NULL, 13, 7, '2026-09-25 13:54:28', 'Task created'),
(13, 7, NULL, 13, 7, '2026-09-25 13:55:13', 'Task created'),
(14, 6, 13, 15, 7, '2026-09-25 13:55:24', 'Task moved via Kanban board'),
(15, 7, 13, 15, 7, '2026-09-25 13:55:31', 'Task moved via Kanban board'),
(17, 9, NULL, 13, 7, '2026-09-25 13:57:27', 'Task created'),
(18, 9, 13, 16, 7, '2026-09-25 13:57:30', 'Task moved via Kanban board'),
(19, 10, NULL, 18, 7, '2026-09-25 13:59:00', 'Task created'),
(20, 10, 18, 19, 7, '2026-09-25 13:59:02', 'Task moved via Kanban board'),
(21, 7, 15, 16, 7, '2026-09-25 14:01:46', 'Task moved via Kanban board'),
(22, 6, 15, 16, 7, '2026-09-26 04:43:04', 'Task moved via Kanban board'),
(23, 5, 13, 16, 7, '2026-09-27 11:57:09', 'Task moved via Kanban board'),
(24, 4, 13, 15, 7, '2026-09-27 11:57:13', 'Task moved via Kanban board'),
(26, 7, 16, 17, 7, '2026-09-27 11:57:24', 'Task moved via Kanban board'),
(27, 11, NULL, 24, 7, '2026-09-28 02:11:18', 'Task created'),
(28, 12, NULL, 24, 7, '2026-09-28 02:11:39', 'Task created'),
(29, 13, NULL, 24, 7, '2026-09-28 02:12:14', 'Task created'),
(30, 11, 24, 26, 7, '2026-09-28 02:12:18', 'Task moved via Kanban board'),
(31, 12, 24, 26, 7, '2026-09-28 02:12:19', 'Task moved via Kanban board'),
(32, 13, 24, 25, 7, '2026-09-28 02:12:21', 'Task moved via Kanban board'),
(33, 14, NULL, 24, 7, '2026-09-28 02:12:50', 'Task created'),
(34, 14, 24, 25, 7, '2026-09-28 02:12:52', 'Task moved via Kanban board'),
(35, 13, 25, 26, 7, '2026-09-28 02:12:56', 'Task moved via Kanban board'),
(36, 15, NULL, 24, 7, '2026-09-28 02:13:16', 'Task created'),
(37, 15, 24, 25, 7, '2026-09-28 02:13:18', 'Task moved via Kanban board'),
(38, 15, 25, 26, 7, '2026-09-28 02:14:00', 'Task moved via Kanban board'),
(39, 16, NULL, 24, 7, '2026-09-28 02:14:19', 'Task created'),
(40, 16, 24, 25, 7, '2026-09-28 02:14:22', 'Task moved via Kanban board'),
(41, 9, 16, 17, 7, '2026-09-28 03:05:09', 'Task moved via Kanban board'),
(42, 6, 16, 17, 7, '2026-09-28 03:05:13', 'Task moved via Kanban board'),
(43, 10, 19, 20, 7, '2026-09-28 03:05:44', 'Task moved via Kanban board'),
(44, 17, NULL, 28, 7, '2026-09-29 02:50:05', 'Task created'),
(45, 18, NULL, 28, 7, '2026-09-29 02:52:13', 'Task created'),
(46, 19, NULL, 18, 7, '2026-09-29 03:18:42', 'Task created'),
(47, 14, 25, 26, 8, '2026-09-30 13:34:04', 'Task moved via Kanban board'),
(48, 16, 25, 24, 8, '2026-09-30 13:34:09', 'Task moved via Kanban board'),
(49, 16, 24, 25, 8, '2026-09-30 13:34:21', 'Task moved via Kanban board'),
(50, 19, 18, 19, 8, '2026-10-01 02:23:51', 'Task moved via Kanban board'),
(51, 19, 19, 20, 8, '2026-10-01 02:23:54', 'Task moved via Kanban board'),
(52, 18, 28, 27, 8, '2026-10-01 06:24:52', 'Task moved via Kanban board'),
(53, 18, 27, 28, 8, '2026-10-01 06:24:54', 'Task moved via Kanban board'),
(71, 17, 28, 30, 7, '2026-10-04 05:27:59', 'Task moved via Kanban board'),
(72, 18, 28, 30, 7, '2026-10-04 05:28:02', 'Task moved via Kanban board'),
(73, 24, NULL, 27, 7, '2026-10-04 05:28:30', 'Task created'),
(74, 25, NULL, 27, 7, '2026-10-04 05:29:10', 'Task created'),
(75, 26, NULL, 27, 7, '2026-10-04 05:30:09', 'Task created'),
(81, 5, 16, 17, 8, '2026-10-07 03:16:31', 'Task moved via Kanban board'),
(82, 4, 15, 17, 8, '2026-10-07 03:16:32', 'Task moved via Kanban board'),
(83, 16, 25, 26, 8, '2026-10-07 03:17:25', 'Task moved via Kanban board'),
(84, 7, 17, 16, 7, '2026-10-11 03:42:54', 'Task moved via Kanban board'),
(85, 7, 16, 17, 7, '2026-10-11 03:42:58', 'Task moved via Kanban board'),
(87, 28, NULL, 27, 7, '2026-10-14 04:14:59', 'Task created'),
(88, 29, NULL, 27, 7, '2026-10-14 04:15:30', 'Task created');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_activity_user` (`user_id`),
  ADD KEY `idx_activity_project` (`project_id`),
  ADD KEY `idx_activity_created` (`created_at`);

--
-- Indexes for table `projects`
--
ALTER TABLE `projects`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_created_by` (`created_by`),
  ADD KEY `idx_project_leader` (`project_leader_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_privacy` (`privacy`);

--
-- Indexes for table `project_columns`
--
ALTER TABLE `project_columns`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_project_columns` (`project_id`);

--
-- Indexes for table `project_members`
--
ALTER TABLE `project_members`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_project_member` (`project_id`,`user_id`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- Indexes for table `project_status_history`
--
ALTER TABLE `project_status_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_project_status_history` (`project_id`);

--
-- Indexes for table `tasks`
--
ALTER TABLE `tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_project_id` (`project_id`),
  ADD KEY `idx_column_id` (`column_id`),
  ADD KEY `idx_assigned_to` (`assigned_to`),
  ADD KEY `idx_created_by` (`created_by`);

--
-- Indexes for table `task_attachments`
--
ALTER TABLE `task_attachments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_attachment_task` (`task_id`);

--
-- Indexes for table `task_comments`
--
ALTER TABLE `task_comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_comment_task` (`task_id`);

--
-- Indexes for table `task_status_history`
--
ALTER TABLE `task_status_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_task_history` (`task_id`),
  ADD KEY `idx_changed_at` (`changed_at`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_log`
--
ALTER TABLE `activity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=163;

--
-- AUTO_INCREMENT for table `projects`
--
ALTER TABLE `projects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `project_columns`
--
ALTER TABLE `project_columns`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT for table `project_members`
--
ALTER TABLE `project_members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `project_status_history`
--
ALTER TABLE `project_status_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `task_attachments`
--
ALTER TABLE `task_attachments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `task_comments`
--
ALTER TABLE `task_comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `task_status_history`
--
ALTER TABLE `task_status_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=89;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `project_columns`
--
ALTER TABLE `project_columns`
  ADD CONSTRAINT `fk_columns_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `project_members`
--
ALTER TABLE `project_members`
  ADD CONSTRAINT `fk_members_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `project_status_history`
--
ALTER TABLE `project_status_history`
  ADD CONSTRAINT `fk_psh_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tasks`
--
ALTER TABLE `tasks`
  ADD CONSTRAINT `fk_tasks_column` FOREIGN KEY (`column_id`) REFERENCES `project_columns` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_tasks_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `task_attachments`
--
ALTER TABLE `task_attachments`
  ADD CONSTRAINT `fk_attachments_task` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `task_comments`
--
ALTER TABLE `task_comments`
  ADD CONSTRAINT `fk_comments_task` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `task_status_history`
--
ALTER TABLE `task_status_history`
  ADD CONSTRAINT `fk_history_task` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
