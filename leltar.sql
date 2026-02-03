-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Gép: 127.0.0.1
-- Létrehozás ideje: 2026. Feb 02. 18:27
-- Kiszolgáló verziója: 10.4.32-MariaDB
-- PHP verzió: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Adatbázis: `leltar`
--

-- --------------------------------------------------------

--
-- Tábla szerkezet ehhez a táblához `companies`
--

CREATE TABLE `companies` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- A tábla adatainak kiíratása `companies`
--

INSERT INTO `companies` (`id`, `name`, `created_at`) VALUES
(1, 'Test Company', '2025-11-26 15:26:16'),
(2, 'Orion Solutions', '2025-12-11 10:02:56'),
(3, 'SilverTech Global', '2025-12-11 10:02:56'),
(4, 'Nova Services Ltd', '2025-12-11 10:02:56'),
(5, 'Minegzy', '2026-01-16 09:17:28'),
(6, 'VTS', '2026-01-16 09:19:51');

-- --------------------------------------------------------

--
-- Tábla szerkezet ehhez a táblához `company_user`
--

CREATE TABLE `company_user` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- A tábla adatainak kiíratása `company_user`
--

INSERT INTO `company_user` (`id`, `user_id`, `company_id`) VALUES
(3, 36, 2),
(4, 36, 3),
(5, 36, 4),
(7, 40, 2),
(8, 40, 3),
(9, 40, 4),
(10, 40, 1),
(11, 41, 2),
(12, 14, 1),
(14, 32, 2),
(15, 16, 4),
(16, 26, 6),
(17, 42, 2),
(18, 18, 2),
(19, 20, 5),
(20, 17, 4),
(21, 21, 2),
(22, 41, 6);

-- --------------------------------------------------------

--
-- Tábla szerkezet ehhez a táblához `inventories`
--

CREATE TABLE `inventories` (
  `id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `status` enum('active','scheduled','finished') NOT NULL DEFAULT 'scheduled',
  `start_date` datetime DEFAULT NULL,
  `end_date` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- A tábla adatainak kiíratása `inventories`
--

INSERT INTO `inventories` (`id`, `company_id`, `name`, `status`, `start_date`, `end_date`, `created_at`) VALUES
(1, 1, 'Test Inventory', '', '2025-12-22 00:27:00', NULL, '2025-11-26 15:26:16'),
(2, 1, 'Balázs Ákos', '', '2025-12-08 09:28:00', NULL, '2025-11-27 11:40:21'),
(3, 1, 'A0', '', '2025-12-15 00:00:00', NULL, '2025-12-11 10:24:05'),
(4, 1, 'A0', '', '2025-12-15 07:35:00', NULL, '2025-12-11 10:27:00'),
(5, 2, 'Apple', '', '2026-01-15 00:00:00', NULL, '2026-01-14 18:58:02'),
(6, 3, 'SzaftosLeltar', 'scheduled', '2026-01-15 00:00:00', NULL, '2026-01-14 19:38:41'),
(7, 2, 'SzaftosLeltar', 'finished', '2026-01-15 00:00:00', NULL, '2026-01-14 19:57:00'),
(8, 2, 'ProlomVOda', 'finished', NULL, NULL, '2026-01-14 20:10:57'),
(9, 2, 'ProlomVodaPT2', 'finished', NULL, NULL, '2026-01-14 20:58:28'),
(10, 2, 'DelfinTrip', 'active', NULL, NULL, '2026-01-14 21:22:40'),
(11, 2, 'Leltarozas1', 'scheduled', '2026-01-15 12:00:00', NULL, '2026-01-15 17:03:26'),
(12, 2, 'Leltarozas2', 'finished', '2026-01-17 00:00:00', NULL, '2026-01-16 09:11:58'),
(13, 6, 'Leltar1', 'finished', NULL, NULL, '2026-01-30 20:12:40'),
(14, 2, 'UjLeltar', 'finished', '2026-01-30 19:00:00', NULL, '2026-01-30 20:13:34'),
(15, 2, 'UjLeltarPT2', 'active', '2026-01-29 17:20:00', NULL, '2026-01-30 20:19:18'),
(16, 2, 'Leltar3', 'active', '2026-01-31 16:00:00', NULL, '2026-01-30 20:39:07'),
(17, 2, 'Leltar4', 'active', '2026-02-01 21:00:00', NULL, '2026-01-30 20:39:27'),
(18, 2, 'Leltar5', 'active', '2026-02-02 00:00:00', NULL, '2026-01-30 20:45:02'),
(19, 2, 'Leltar6', 'finished', '2026-02-03 00:00:00', NULL, '2026-01-30 20:45:13'),
(20, 2, 'Leltar7', 'finished', '2026-02-04 00:00:00', NULL, '2026-01-30 20:45:24'),
(21, 6, 'Sziamai', 'scheduled', '2026-02-02 00:00:00', NULL, '2026-02-01 14:39:59');

-- --------------------------------------------------------

--
-- Tábla szerkezet ehhez a táblához `inventory_items`
--

CREATE TABLE `inventory_items` (
  `id` int(11) NOT NULL,
  `inventory_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `is_present` tinyint(1) NOT NULL DEFAULT 1,
  `note` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- A tábla adatainak kiíratása `inventory_items`
--

INSERT INTO `inventory_items` (`id`, `inventory_id`, `item_id`, `user_id`, `is_present`, `note`, `created_at`) VALUES
(2, 8, 106, 41, 1, 'Megtalálva', '2026-01-14 20:40:47'),
(5, 8, 101, 41, 0, 'Hiányzik', '2026-01-14 20:43:02'),
(6, 9, 106, 41, 1, 'Megtalálva', '2026-01-14 21:11:31'),
(9, 9, 101, 41, 1, 'Megtalálva', '2026-01-14 21:11:31'),
(10, 10, 108, 41, 1, 'Megtalálva', '2026-01-14 21:30:33'),
(11, 8, 107, 41, 1, 'Megtalálva', '2026-01-16 09:08:44'),
(12, 8, 108, 41, 0, 'Hiányzik', '2026-01-16 09:08:52'),
(13, 8, 109, 41, 1, 'Megtalálva', '2026-01-16 09:08:54'),
(14, 8, 110, 41, 1, 'Megtalálva', '2026-01-16 09:08:55'),
(15, 15, 106, 42, 1, 'Megtalálva', '2026-01-30 20:36:11'),
(16, 15, 111, 42, 1, 'Megtalálva', '2026-01-30 20:36:11'),
(17, 15, 109, 42, 1, 'Megtalálva', '2026-01-30 20:36:11'),
(18, 15, 107, 42, 0, 'Hiányzik', '2026-01-30 20:36:11'),
(19, 15, 110, 42, 1, 'Megtalálva', '2026-01-30 20:36:11'),
(20, 15, 108, 42, 0, 'Hiányzik', '2026-01-30 20:36:11'),
(21, 15, 101, 42, 1, 'Megtalálva', '2026-01-30 20:36:11'),
(22, 8, 106, 42, 1, 'Megtalálva', '2026-01-30 20:52:36'),
(23, 8, 111, 42, 1, 'Megtalálva', '2026-01-30 20:52:36'),
(24, 8, 109, 42, 1, 'Megtalálva', '2026-01-30 20:52:36'),
(25, 8, 107, 42, 1, 'Megtalálva', '2026-01-30 20:52:36'),
(26, 8, 110, 42, 1, 'Megtalálva', '2026-01-30 20:52:36'),
(27, 8, 108, 42, 1, 'Megtalálva', '2026-01-30 20:52:36'),
(28, 8, 101, 42, 1, 'Megtalálva', '2026-01-30 20:52:36'),
(29, 20, 106, 42, 0, 'Hiányzik', '2026-01-30 20:53:32'),
(30, 20, 111, 42, 1, 'Megtalálva', '2026-01-30 20:53:32'),
(31, 20, 110, 42, 1, 'Megtalálva', '2026-01-30 20:53:32'),
(32, 20, 109, 42, 0, 'Hiányzik', '2026-01-30 20:53:32'),
(33, 20, 107, 42, 0, 'Hiányzik', '2026-01-30 20:53:32'),
(34, 20, 108, 42, 1, 'Megtalálva', '2026-01-30 20:53:32'),
(35, 20, 101, 42, 1, 'Megtalálva', '2026-01-30 20:53:32'),
(36, 10, 109, 42, 1, 'Megtalálva', '2026-01-31 11:52:59'),
(37, 10, 111, 42, 0, 'Hiányzik', '2026-01-31 11:52:59'),
(38, 10, 106, 42, 1, 'Megtalálva', '2026-01-31 11:52:59'),
(39, 10, 107, 42, 0, 'Hiányzik', '2026-01-31 11:52:59'),
(40, 10, 110, 42, 1, 'Megtalálva', '2026-01-31 11:52:59'),
(41, 10, 108, 42, 1, 'Megtalálva', '2026-01-31 11:52:59'),
(42, 10, 101, 42, 0, 'Hiányzik', '2026-01-31 11:52:59'),
(43, 18, 106, 42, 1, 'Megtalálva', '2026-01-31 12:20:19'),
(44, 18, 111, 42, 1, 'Megtalálva', '2026-01-31 12:20:19'),
(45, 18, 109, 42, 1, 'Megtalálva', '2026-01-31 12:20:19'),
(46, 18, 107, 42, 1, 'Megtalálva', '2026-01-31 12:20:19'),
(47, 18, 110, 42, 1, 'Megtalálva', '2026-01-31 12:20:19'),
(48, 18, 108, 42, 1, 'Megtalálva', '2026-01-31 12:20:19'),
(49, 18, 101, 42, 0, 'Hiányzik', '2026-01-31 12:20:19');

-- --------------------------------------------------------

--
-- Tábla szerkezet ehhez a táblához `inventory_schedules`
--

CREATE TABLE `inventory_schedules` (
  `id` int(11) NOT NULL,
  `inventory_id` int(11) NOT NULL,
  `target_type` enum('team','room','all') NOT NULL,
  `target_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- A tábla adatainak kiíratása `inventory_schedules`
--

INSERT INTO `inventory_schedules` (`id`, `inventory_id`, `target_type`, `target_id`) VALUES
(1, 2, 'team', 2),
(2, 2, 'all', NULL),
(3, 4, 'all', NULL),
(4, 4, 'team', 3),
(5, 4, 'room', 11),
(6, 1, 'all', NULL),
(7, 1, 'team', 2),
(8, 1, 'room', 1),
(9, 1, 'room', 2),
(10, 1, 'room', 11),
(11, 11, 'team', 4),
(12, 11, 'room', 3),
(13, 14, 'all', NULL),
(14, 14, 'team', 4),
(15, 14, 'team', 5),
(16, 14, 'room', 3),
(17, 14, 'room', 4),
(18, 15, 'all', NULL),
(19, 15, 'team', 6),
(20, 15, 'room', 3),
(21, 15, 'room', 4),
(22, 16, 'all', NULL),
(23, 16, 'team', 6),
(24, 16, 'room', 3),
(25, 16, 'room', 4),
(26, 17, 'all', NULL),
(27, 17, 'team', 6),
(28, 17, 'room', 3),
(29, 17, 'room', 4);

-- --------------------------------------------------------

--
-- Tábla szerkezet ehhez a táblához `inventory_submissions`
--

CREATE TABLE `inventory_submissions` (
  `id` int(11) NOT NULL,
  `inventory_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `payload` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `status` enum('pending','approved','rejected') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- A tábla adatainak kiíratása `inventory_submissions`
--

INSERT INTO `inventory_submissions` (`id`, `inventory_id`, `user_id`, `payload`, `created_at`, `status`) VALUES
(1, 9, 41, '{\"items\":[{\"item_id\":106,\"is_present\":1,\"note\":\"Megtal\\u00e1lva\",\"photo\":null},{\"item_id\":77,\"is_present\":1,\"note\":\"Megtal\\u00e1lva\",\"photo\":null},{\"item_id\":78,\"is_present\":1,\"note\":\"Megtal\\u00e1lva\",\"photo\":null},{\"item_id\":101,\"is_present\":1,\"note\":\"Megtal\\u00e1lva\",\"photo\":null}]}', '2026-01-14 21:11:31', 'pending'),
(2, 9, 41, '{\"items\":[{\"item_id\":106,\"is_present\":1,\"note\":\"Megtal\\u00e1lva\",\"photo\":null},{\"item_id\":77,\"is_present\":1,\"note\":\"Megtal\\u00e1lva\",\"photo\":null},{\"item_id\":78,\"is_present\":1,\"note\":\"Megtal\\u00e1lva\",\"photo\":null},{\"item_id\":101,\"is_present\":1,\"note\":\"Megtal\\u00e1lva\",\"photo\":null}]}', '2026-01-14 21:11:37', 'pending'),
(3, 9, 41, '{\"items\":[{\"item_id\":106,\"is_present\":1,\"note\":\"Megtal\\u00e1lva\",\"photo\":null},{\"item_id\":77,\"is_present\":1,\"note\":\"Megtal\\u00e1lva\",\"photo\":null},{\"item_id\":78,\"is_present\":1,\"note\":\"Megtal\\u00e1lva\",\"photo\":null},{\"item_id\":101,\"is_present\":1,\"note\":\"Megtal\\u00e1lva\",\"photo\":null}]}', '2026-01-14 21:12:07', 'pending'),
(4, 9, 41, '{\"items\":[{\"item_id\":106,\"is_present\":1,\"note\":\"Megtal\\u00e1lva\",\"photo\":null},{\"item_id\":77,\"is_present\":0,\"note\":\"Hi\\u00e1nyzik\",\"photo\":null},{\"item_id\":78,\"is_present\":1,\"note\":\"Megtal\\u00e1lva\",\"photo\":null},{\"item_id\":101,\"is_present\":1,\"note\":\"Megtal\\u00e1lva\",\"photo\":null}]}', '2026-01-14 21:12:32', 'pending'),
(5, 9, 41, '{\"items\":[{\"item_id\":106,\"is_present\":1,\"note\":\"Megtal\\u00e1lva\",\"photo\":null},{\"item_id\":77,\"is_present\":1,\"note\":\"Megtal\\u00e1lva\",\"photo\":null},{\"item_id\":78,\"is_present\":1,\"note\":\"Megtal\\u00e1lva\",\"photo\":null},{\"item_id\":101,\"is_present\":1,\"note\":\"Megtal\\u00e1lva\",\"photo\":null}]}', '2026-01-14 21:12:37', 'pending'),
(6, 8, 41, '{\"items\":[{\"item_id\":106,\"is_present\":1,\"note\":\"Megtal\\u00e1lva\",\"photo\":null},{\"item_id\":77,\"is_present\":1,\"note\":\"Megtal\\u00e1lva\",\"photo\":null},{\"item_id\":78,\"is_present\":1,\"note\":\"Megtal\\u00e1lva\",\"photo\":null},{\"item_id\":101,\"is_present\":1,\"note\":\"Megtal\\u00e1lva\",\"photo\":null}]}', '2026-01-14 21:14:10', 'approved'),
(7, 10, 41, '{\"items\":[{\"item_id\":108,\"is_present\":1,\"note\":\"Megtal\\u00e1lva\",\"photo\":null}]}', '2026-01-14 21:30:33', 'pending'),
(8, 10, 41, '{\"items\":[{\"item_id\":108,\"is_present\":1,\"note\":\"Megtal\\u00e1lva\",\"photo\":null}]}', '2026-01-14 21:30:52', 'pending'),
(9, 15, 42, '{\"items\":[{\"item_id\":106,\"is_present\":1,\"note\":\"Megtal\\u00e1lva\",\"photo\":null},{\"item_id\":111,\"is_present\":1,\"note\":\"Megtal\\u00e1lva\",\"photo\":null},{\"item_id\":109,\"is_present\":1,\"note\":\"Megtal\\u00e1lva\",\"photo\":null},{\"item_id\":107,\"is_present\":0,\"note\":\"Hi\\u00e1nyzik\",\"photo\":null},{\"item_id\":110,\"is_present\":1,\"note\":\"Megtal\\u00e1lva\",\"photo\":null},{\"item_id\":108,\"is_present\":0,\"note\":\"Hi\\u00e1nyzik\",\"photo\":null},{\"item_id\":101,\"is_present\":1,\"note\":\"Megtal\\u00e1lva\",\"photo\":null}]}', '2026-01-30 20:36:11', 'pending'),
(10, 8, 42, '{\"items\":[{\"item_id\":106,\"is_present\":1,\"note\":\"Megtal\\u00e1lva\",\"photo\":null},{\"item_id\":111,\"is_present\":1,\"note\":\"Megtal\\u00e1lva\",\"photo\":null},{\"item_id\":109,\"is_present\":1,\"note\":\"Megtal\\u00e1lva\",\"photo\":null},{\"item_id\":107,\"is_present\":1,\"note\":\"Megtal\\u00e1lva\",\"photo\":null},{\"item_id\":110,\"is_present\":1,\"note\":\"Megtal\\u00e1lva\",\"photo\":null},{\"item_id\":108,\"is_present\":1,\"note\":\"Megtal\\u00e1lva\",\"photo\":null},{\"item_id\":101,\"is_present\":1,\"note\":\"Megtal\\u00e1lva\",\"photo\":null}]}', '2026-01-30 20:52:36', 'approved'),
(11, 20, 42, '{\"items\":[{\"item_id\":106,\"is_present\":0,\"note\":\"Hi\\u00e1nyzik\",\"photo\":null},{\"item_id\":111,\"is_present\":1,\"note\":\"Megtal\\u00e1lva\",\"photo\":null},{\"item_id\":110,\"is_present\":1,\"note\":\"Megtal\\u00e1lva\",\"photo\":null},{\"item_id\":109,\"is_present\":0,\"note\":\"Hi\\u00e1nyzik\",\"photo\":null},{\"item_id\":107,\"is_present\":0,\"note\":\"Hi\\u00e1nyzik\",\"photo\":null},{\"item_id\":108,\"is_present\":1,\"note\":\"Megtal\\u00e1lva\",\"photo\":null},{\"item_id\":101,\"is_present\":1,\"note\":\"Megtal\\u00e1lva\",\"photo\":null}]}', '2026-01-30 20:53:32', 'approved'),
(12, 8, 42, '{\"items\":[{\"item_id\":106,\"is_present\":1,\"note\":\"Megtal\\u00e1lva\",\"photo\":null},{\"item_id\":111,\"is_present\":1,\"note\":\"Megtal\\u00e1lva\",\"photo\":null}]}', '2026-01-30 20:58:43', 'approved'),
(13, 20, 42, '{\"items\":[{\"item_id\":106,\"is_present\":1,\"note\":\"Megtal\\u00e1lva\",\"photo\":null},{\"item_id\":111,\"is_present\":0,\"note\":\"Hi\\u00e1nyzik\",\"photo\":null},{\"item_id\":109,\"is_present\":1,\"note\":\"Megtal\\u00e1lva\",\"photo\":null},{\"item_id\":107,\"is_present\":1,\"note\":\"Megtal\\u00e1lva\",\"photo\":null},{\"item_id\":110,\"is_present\":1,\"note\":\"Megtal\\u00e1lva\",\"photo\":null},{\"item_id\":108,\"is_present\":1,\"note\":\"Megtal\\u00e1lva\",\"photo\":null},{\"item_id\":101,\"is_present\":1,\"note\":\"Megtal\\u00e1lva\",\"photo\":null}]}', '2026-01-31 11:42:26', 'approved'),
(14, 10, 42, '{\"items\":[{\"item_id\":109,\"is_present\":1,\"note\":\"Megtal\\u00e1lva\",\"photo\":null},{\"item_id\":111,\"is_present\":0,\"note\":\"Hi\\u00e1nyzik\",\"photo\":null},{\"item_id\":106,\"is_present\":1,\"note\":\"Megtal\\u00e1lva\",\"photo\":null},{\"item_id\":107,\"is_present\":0,\"note\":\"Hi\\u00e1nyzik\",\"photo\":null},{\"item_id\":110,\"is_present\":1,\"note\":\"Megtal\\u00e1lva\",\"photo\":null},{\"item_id\":108,\"is_present\":1,\"note\":\"Megtal\\u00e1lva\",\"photo\":null},{\"item_id\":101,\"is_present\":0,\"note\":\"Hi\\u00e1nyzik\",\"photo\":null}]}', '2026-01-31 11:52:59', 'pending'),
(15, 20, 42, '{\"items\":[{\"item_id\":106,\"is_present\":1,\"note\":\"Megtal\\u00e1lva\",\"photo\":null},{\"item_id\":111,\"is_present\":0,\"note\":\"Hi\\u00e1nyzik\",\"photo\":null},{\"item_id\":109,\"is_present\":1,\"note\":\"Megtal\\u00e1lva\",\"photo\":null},{\"item_id\":107,\"is_present\":1,\"note\":\"Megtal\\u00e1lva\",\"photo\":null},{\"item_id\":110,\"is_present\":0,\"note\":\"Hi\\u00e1nyzik\",\"photo\":null},{\"item_id\":108,\"is_present\":1,\"note\":\"Megtal\\u00e1lva\",\"photo\":null},{\"item_id\":101,\"is_present\":0,\"note\":\"Hi\\u00e1nyzik\",\"photo\":null}]}', '2026-01-31 12:09:07', 'approved'),
(16, 18, 42, '{\"items\":[{\"item_id\":106,\"is_present\":1,\"note\":\"Megtal\\u00e1lva\",\"photo\":null},{\"item_id\":111,\"is_present\":1,\"note\":\"Megtal\\u00e1lva\",\"photo\":null},{\"item_id\":109,\"is_present\":1,\"note\":\"Megtal\\u00e1lva\",\"photo\":null},{\"item_id\":107,\"is_present\":1,\"note\":\"Megtal\\u00e1lva\",\"photo\":null},{\"item_id\":110,\"is_present\":1,\"note\":\"Megtal\\u00e1lva\",\"photo\":null},{\"item_id\":108,\"is_present\":1,\"note\":\"Megtal\\u00e1lva\",\"photo\":null},{\"item_id\":101,\"is_present\":0,\"note\":\"Hi\\u00e1nyzik\",\"photo\":null}]}', '2026-01-31 12:20:19', 'pending');

-- --------------------------------------------------------

--
-- Tábla szerkezet ehhez a táblához `inventory_submission_responses`
--

CREATE TABLE `inventory_submission_responses` (
  `id` int(11) NOT NULL,
  `submission_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- A tábla adatainak kiíratása `inventory_submission_responses`
--

INSERT INTO `inventory_submission_responses` (`id`, `submission_id`, `user_id`, `message`, `created_at`) VALUES
(1, 11, 41, '✅ Beküldés elfogadva', '2026-01-31 12:11:36'),
(2, 15, 41, '✅ Beküldés elfogadva', '2026-01-31 12:11:48'),
(3, 13, 41, '✅ Beküldés elfogadva', '2026-01-31 12:11:50'),
(4, 6, 41, '✅ Beküldés elfogadva', '2026-01-31 12:27:56'),
(5, 10, 41, '✅ Beküldés elfogadva', '2026-01-31 12:27:58'),
(6, 12, 41, '✅ Beküldés elfogadva', '2026-01-31 12:28:00');

-- --------------------------------------------------------

--
-- Tábla szerkezet ehhez a táblához `items`
--

CREATE TABLE `items` (
  `id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `qr_code` varchar(255) NOT NULL,
  `image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- A tábla adatainak kiíratása `items`
--

INSERT INTO `items` (`id`, `room_id`, `name`, `qr_code`, `image`) VALUES
(74, 2, 'Monitor Dell', 'qr_1765448784_cbd7a7689d09.png', 'item_1765450233_726b0e5f176a.jpg'),
(79, 5, 'Microscope', 'QR-SILVER-001', '/uploads/items/item_1768416800_ef1f6faf6540.png'),
(80, 5, 'Test Tube Set', 'QR-SILVER-002', NULL),
(81, 5, '3D Printer', 'QR-SILVER-003', NULL),
(82, 6, 'Laptop Lenovo', 'QR-SILVER-004', NULL),
(83, 7, 'Industrial Fan', 'QR-SILVER-005', NULL),
(84, 8, 'Reception PC', 'QR-NOVA-001', NULL),
(85, 8, 'Conference Tablet', 'QR-NOVA-002', NULL),
(86, 9, 'Firewall Device', 'QR-NOVA-003', NULL),
(87, 9, 'Server Rack', 'QR-NOVA-004', NULL),
(88, 10, 'Drill Machine', 'QR-NOVA-005', NULL),
(89, 9, 'Toolbox', 'QR-NOVA-006', NULL),
(90, 10, 'Extension Cable', 'QR-NOVA-007', NULL),
(101, 3, 'Telefon', 'qr_1767732934_1df432d28b97.png', NULL),
(103, 11, 'Keyboard', 'qr_1767735403_6f0acfb1fac7.png', NULL),
(106, 3, 'Alma', '../uploads/qr/qr_1768417463_78bb73dd2ac8.png', NULL),
(107, 3, 'Eger', '../uploads/qr/qr_1768422243_c9e50a4fe4ed.png', NULL),
(108, 3, 'Kabel', '../uploads/qr/qr_1768422403_56f1431a43ad.png', NULL),
(109, 3, 'asdasd', '../uploads/qr/qr_1768422930_c6b8cab78725.png', '/uploads/items/item_1768422983_7d227d20b9e1.jpg'),
(110, 3, 'Eszkoz 1', '../uploads/qr/qr_1768493150_0ded1a8aed35.png', NULL),
(111, 3, 'Apple', '../uploads/qr/qr_1768551413_542764a0681c.png', NULL);

-- --------------------------------------------------------

--
-- Tábla szerkezet ehhez a táblához `rooms`
--

CREATE TABLE `rooms` (
  `id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- A tábla adatainak kiíratása `rooms`
--

INSERT INTO `rooms` (`id`, `company_id`, `name`) VALUES
(1, 1, 'Test Room'),
(2, 1, 'A10'),
(3, 2, 'Orion Server Room'),
(4, 2, 'Orion Storage'),
(5, 3, 'SilverTech Lab 1'),
(6, 3, 'SilverTech Office'),
(7, 3, 'SilverTech Warehouse'),
(8, 4, 'Nova Main Hall'),
(9, 4, 'Nova IT Room'),
(10, 4, 'Nova Storage'),
(11, 1, 'A0'),
(14, 1, '301');

-- --------------------------------------------------------

--
-- Tábla szerkezet ehhez a táblához `teams`
--

CREATE TABLE `teams` (
  `id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- A tábla adatainak kiíratása `teams`
--

INSERT INTO `teams` (`id`, `company_id`, `name`) VALUES
(1, 1, 'T13'),
(2, 1, 'rigok'),
(3, 1, 'Lapmesterek'),
(4, 2, 'Csapat 1 test'),
(5, 2, 'Csapat 2 test'),
(6, 2, 'Adam Csapat');

-- --------------------------------------------------------

--
-- Tábla szerkezet ehhez a táblához `team_room`
--

CREATE TABLE `team_room` (
  `id` int(11) NOT NULL,
  `team_id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `info` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- A tábla adatainak kiíratása `team_room`
--

INSERT INTO `team_room` (`id`, `team_id`, `room_id`, `info`, `created_at`) VALUES
(1, 1, 1, '', '2025-11-27 12:52:29'),
(3, 2, 2, '', '2025-12-10 09:27:27'),
(4, 1, 1, 'csereljetek ki a monitort', '2025-12-11 10:09:14'),
(5, 3, 11, 'csereljetek ki a monitort', '2025-12-11 10:22:45'),
(6, 2, 1, '', '2026-01-14 18:59:20'),
(7, 4, 3, '', '2026-01-14 19:59:37'),
(8, 4, 4, '', '2026-01-14 20:16:30'),
(9, 5, 3, '', '2026-01-16 09:15:13'),
(10, 5, 4, '', '2026-01-16 09:15:16'),
(11, 6, 3, '', '2026-01-30 20:18:29'),
(12, 6, 4, '', '2026-01-30 20:18:33');

-- --------------------------------------------------------

--
-- Tábla szerkezet ehhez a táblához `team_user`
--

CREATE TABLE `team_user` (
  `id` int(11) NOT NULL,
  `team_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- A tábla adatainak kiíratása `team_user`
--

INSERT INTO `team_user` (`id`, `team_id`, `user_id`) VALUES
(2, 1, 1),
(4, 1, 31),
(7, 2, 40),
(8, 4, 40),
(9, 4, 27),
(10, 5, 36),
(11, 5, 12),
(12, 6, 42),
(13, 3, 42),
(14, 3, 40);

-- --------------------------------------------------------

--
-- Tábla szerkezet ehhez a táblához `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `role` enum('worker','employer','admin') NOT NULL DEFAULT 'worker',
  `is_active` tinyint(1) NOT NULL DEFAULT 0,
  `is_blocked` tinyint(1) NOT NULL DEFAULT 0,
  `activation_token` varchar(255) DEFAULT NULL,
  `reset_token` varchar(255) DEFAULT NULL,
  `token_expires` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `api_token` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- A tábla adatainak kiíratása `users`
--

INSERT INTO `users` (`id`, `email`, `password`, `first_name`, `last_name`, `phone`, `role`, `is_active`, `is_blocked`, `activation_token`, `reset_token`, `token_expires`, `created_at`, `api_token`) VALUES
(1, 'testuser@example.test', '$2y$10$oOxP4BVVmZt.5R054h78b.zj3PZaT6kdaw4XRG6EYLNLXxOlT1yIK', 'Teszt', 'Felhasznalo', NULL, 'worker', 1, 0, NULL, NULL, NULL, '2025-11-26 15:19:42', NULL),
(2, 'admin@example.test', '$2y$10$C230nXIpZt56P/xH/zR.2.N5i4f/IF5nj0V40Taso.vXcN4qBAvHW', 'Admin', 'User', NULL, 'admin', 1, 0, NULL, NULL, NULL, '2025-11-26 15:38:14', NULL),
(9, 'vinklee738@gmail.com', '$2y$10$.sh1xsEFDqud78xemZ1B..7lG13V.Kq0FfYpL7Ly0NYrrdvXF0brC', 'Kappu', 'Dszino', NULL, 'worker', 1, 0, NULL, NULL, NULL, '2025-12-03 08:37:52', NULL),
(11, 'akosbalazs65@gmail.com', '$2y$10$ycrKTZycKtJz4wxVkpLN4.uasJG7keXcL9iZbf9etn5hKvxsBTrl.', 'Nyomo', 'Reka', NULL, 'worker', 1, 0, NULL, '', NULL, '2025-12-03 09:12:48', NULL),
(12, 'lapmesterek@gmail.com', '$2y$10$FJsnj8wvj4sn.jbmumAx5.7srE44IuoRepUHqhbx8orS8hhQ2kYdS', 'Kerekes', 'Jozsi', NULL, 'worker', 1, 0, NULL, NULL, NULL, '2025-12-10 10:34:48', NULL),
(13, 'peter.molnar@example.com', '$2y$10$Qga123Aaabbb..15', 'Péter', 'Molnár', '06204445555', 'admin', 1, 0, NULL, NULL, NULL, '2025-12-11 10:04:12', NULL),
(14, 'janos.illes@example.com', '$2y$10$Qga123Aaabbb..16', 'János', 'Illés', NULL, 'worker', 1, 0, NULL, NULL, NULL, '2025-12-11 10:04:12', NULL),
(15, 'rebeka.kiraly@example.com', '$2y$10$Qga123Aaabbb..17', 'Rebeka', 'Király', '06205556666', 'employer', 1, 0, NULL, NULL, NULL, '2025-12-11 10:04:12', NULL),
(16, 'zsolt.lakatos@example.com', '$2y$10$Qga123Aaabbb..18', 'Zsolt', 'Lakatos', '06206667777', 'worker', 1, 0, NULL, NULL, NULL, '2025-12-11 10:04:12', NULL),
(17, 'bea.kerekes@example.com', '$2y$10$Qga123Aaabbb..19', 'Bea', 'Kerekes', '06207778888', 'worker', 1, 0, NULL, NULL, NULL, '2025-12-11 10:04:12', NULL),
(18, 'akos.torok@example.com', '$2y$10$Qga123Aaabbb..20', 'Ákos', 'Török', '06208889999', 'worker', 1, 0, NULL, NULL, NULL, '2025-12-11 10:04:12', NULL),
(19, 'robert.kis@example.com', '$2y$10$Qga123Aaabbb..21', 'Róbert', 'Kis', NULL, 'employer', 1, 0, NULL, NULL, NULL, '2025-12-11 10:04:12', NULL),
(20, 'milan.gaspar@example.com', '$2y$10$Qga123Aaabbb..22', 'Milán', 'Gáspár', NULL, 'worker', 1, 0, NULL, NULL, NULL, '2025-12-11 10:04:12', NULL),
(21, 'renata.balogh@example.com', '$2y$10$Qga123Aaabbb..23', 'Renáta', 'Balogh', NULL, 'worker', 1, 0, NULL, NULL, NULL, '2025-12-11 10:04:12', NULL),
(22, 'andrea.fodor@example.com', '$2y$10$Qga123Aaabbb..24', 'Andrea', 'Fodor', '06209998888', 'admin', 1, 0, NULL, NULL, NULL, '2025-12-11 10:04:12', NULL),
(23, 'mate.szucs@example.com', '$2y$10$Qga123Aaabbb..25', 'Máté', 'Szűcs', NULL, 'worker', 1, 0, NULL, NULL, NULL, '2025-12-11 10:04:12', NULL),
(24, 'istvan.orosz@example.com', '$2y$10$Qga123Aaabbb..26', 'István', 'Orosz', NULL, 'worker', 1, 0, NULL, NULL, NULL, '2025-12-11 10:04:12', NULL),
(25, 'dora.kertesz@example.com', '$2y$10$Qga123Aaabbb..27', 'Dóra', 'Kertész', NULL, 'worker', 1, 0, NULL, NULL, NULL, '2025-12-11 10:04:12', NULL),
(26, 'lilla.virag@example.com', '$2y$10$Qga123Aaabbb..28', 'Lilla', 'Virág', '06201119999', 'employer', 1, 0, NULL, NULL, NULL, '2025-12-11 10:04:12', NULL),
(27, 'gabor.marton@example.com', '$2y$10$Qga123Aaabbb..29', 'Gábor', 'Márton', '06201117777', 'worker', 1, 0, NULL, NULL, NULL, '2025-12-11 10:04:12', NULL),
(28, 'tunde.farkas@example.com', '$2y$10$Qga123Aaabbb..30', 'Tünde', 'Farkas', NULL, 'worker', 1, 0, NULL, NULL, NULL, '2025-12-11 10:04:12', NULL),
(29, 'antal.tanar@example.com', '$2y$10$Qga123Aaabbb..31', 'Antal', 'Tanár', NULL, 'worker', 1, 0, NULL, NULL, NULL, '2025-12-11 10:04:12', NULL),
(30, 'zita.katai@example.com', '$2y$10$Qga123Aaabbb..32', 'Zita', 'Kátai', '06207773333', 'worker', 1, 0, NULL, NULL, NULL, '2025-12-11 10:04:12', NULL),
(31, 'flora.antal@example.com', '$2y$10$Qga123Aaabbb..33', 'Flóra', 'Antal', '06205557777', 'worker', 1, 0, NULL, NULL, NULL, '2025-12-11 10:04:12', NULL),
(32, 'henrik.kasa@example.com', '$2y$10$Qga123Aaabbb..34', 'Henrik', 'Kása', '06209997777', 'employer', 1, 0, NULL, NULL, NULL, '2025-12-11 10:04:12', NULL),
(33, 'vanda.csapo@example.com', '$2y$10$Qga123Aaabbb..35', 'Vanda', 'Csapó', NULL, 'worker', 1, 0, NULL, NULL, NULL, '2025-12-11 10:04:12', NULL),
(36, 'balazs.akos.dev@gmail.com', '$2y$10$r45S3KiazVPdulKYDxw0Xe8b44z29TulenfWQqYBkVEZsUAnDHEhy', 'Balazs', 'Akos', '063741526', 'employer', 1, 0, NULL, NULL, NULL, '2026-01-06 21:46:09', NULL),
(40, 'akosbalazs56@gmail.com', '$2y$10$SDAzdC9PKESxtrTwWdxzg.kmRcDDPlaENnio1McJ5NxjUzpK//E52', 'Balazs', 'Akos', '0638356201', 'admin', 1, 0, NULL, '', NULL, '2026-01-14 18:39:10', 'b2f238decb9886c6006093cc288678bee925b0700931df4b193b1fa119a8a139a4dba650a828994a'),
(41, 'adambickei14@gmail.com', '$2y$10$0F.cLMJiFZtFp32zz4Iwa.7BeOtDsLGc0D4jhA762d7TXvJFeCZKy', 'adam', 'bicskei', '06378456789', 'employer', 1, 0, NULL, NULL, NULL, '2026-01-14 20:33:23', '2c9def8ed3722e054438623610a0505009bb36c8d7641ad476fa5e5bfc31731b323f58cac76ed323'),
(42, 'adambickei12@gmail.com', '$2y$10$53.4Ds/wQU6X0FsJjGBsbOBnQBgOHrRl.tLXsY702Hn272ZA4YYti', 'Adam', 'Bicskei', NULL, 'employer', 1, 0, NULL, NULL, NULL, '2026-01-30 20:15:02', '55778787d921a0f8a254b61cac9177b651a80123661bba2599504c46f6a55d52884c4b2b10bc16ca');

-- --------------------------------------------------------

--
-- Tábla szerkezet ehhez a táblához `user_devices`
--

CREATE TABLE `user_devices` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `device_type` enum('mobile','desktop','tablet','') DEFAULT NULL,
  `os` enum('iOS','Android','Windows','') DEFAULT NULL,
  `browser` varchar(50) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `isp` varchar(100) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- A tábla adatainak kiíratása `user_devices`
--

INSERT INTO `user_devices` (`id`, `user_id`, `device_type`, `os`, `browser`, `ip_address`, `country`, `city`, `isp`, `created_at`) VALUES
(13, 11, 'desktop', '', 'Chrome', '::1', NULL, NULL, NULL, '2025-12-03 09:13:50'),
(14, 9, 'desktop', '', 'Chrome', '127.0.0.1', NULL, NULL, NULL, '2025-12-03 11:19:37'),
(16, 12, 'desktop', '', 'Chrome', '::1', NULL, NULL, NULL, '2025-12-10 10:35:16'),
(25, 11, 'desktop', '', 'Chrome', '::1', NULL, NULL, NULL, '2025-12-17 21:00:48'),
(27, 36, 'desktop', '', 'Chrome', '::1', NULL, NULL, NULL, '2026-01-06 21:47:09'),
(28, 36, 'desktop', '', 'Chrome', '127.0.0.1', NULL, NULL, NULL, '2026-01-06 21:51:07'),
(29, 36, 'desktop', '', 'Chrome', '127.0.0.1', NULL, NULL, NULL, '2026-01-06 22:11:08'),
(30, 36, 'desktop', '', 'Chrome', '127.0.0.1', NULL, NULL, NULL, '2026-01-06 22:13:16'),
(31, 40, 'desktop', '', 'Chrome', '::1', NULL, NULL, NULL, '2026-01-14 18:39:34'),
(32, 40, 'desktop', '', 'Chrome', '::1', NULL, NULL, NULL, '2026-01-14 18:40:45'),
(33, 40, 'desktop', '', 'Chrome', '127.0.0.1', NULL, NULL, NULL, '2026-01-14 18:57:01'),
(34, 40, 'desktop', '', 'Chrome', '127.0.0.1', NULL, NULL, NULL, '2026-01-14 19:00:52'),
(35, 40, 'desktop', '', 'Chrome', '127.0.0.1', NULL, NULL, NULL, '2026-01-14 19:00:53'),
(36, 40, 'desktop', '', 'Firefox', '127.0.0.1', NULL, NULL, NULL, '2026-01-14 19:27:42'),
(37, 40, 'desktop', '', 'Firefox', '127.0.0.1', NULL, NULL, NULL, '2026-01-14 19:38:11'),
(38, 40, 'desktop', '', 'Firefox', '127.0.0.1', NULL, NULL, NULL, '2026-01-14 19:48:45'),
(39, 40, 'desktop', '', 'Firefox', '127.0.0.1', NULL, NULL, NULL, '2026-01-14 19:50:46'),
(40, 40, 'desktop', '', 'Firefox', '172.20.10.2', NULL, NULL, NULL, '2026-01-14 19:56:01'),
(41, 40, 'desktop', '', 'Firefox', '127.0.0.1', NULL, NULL, NULL, '2026-01-14 20:06:03'),
(42, 40, 'desktop', '', 'Firefox', '127.0.0.1', NULL, NULL, NULL, '2026-01-14 20:22:50'),
(43, 40, 'desktop', '', 'Firefox', '127.0.0.1', NULL, NULL, NULL, '2026-01-14 20:24:29'),
(44, 41, 'desktop', '', 'Firefox', '127.0.0.1', NULL, NULL, NULL, '2026-01-14 20:35:29'),
(45, 41, 'desktop', '', 'Firefox', '127.0.0.1', NULL, NULL, NULL, '2026-01-14 20:35:54'),
(46, 40, 'desktop', '', 'Firefox', '127.0.0.1', NULL, NULL, NULL, '2026-01-14 20:36:29'),
(47, 41, 'desktop', '', 'Firefox', '127.0.0.1', NULL, NULL, NULL, '2026-01-14 20:38:07'),
(48, 40, 'desktop', '', 'Firefox', '127.0.0.1', NULL, NULL, NULL, '2026-01-14 20:57:59'),
(49, 40, 'desktop', '', 'Firefox', '127.0.0.1', NULL, NULL, NULL, '2026-01-15 16:57:09'),
(50, 41, 'desktop', '', 'Firefox', '127.0.0.1', NULL, NULL, NULL, '2026-01-15 17:00:53'),
(51, 40, 'desktop', '', 'Firefox', '127.0.0.1', NULL, NULL, NULL, '2026-01-15 17:02:45'),
(52, 41, 'desktop', '', 'Firefox', '127.0.0.1', NULL, NULL, NULL, '2026-01-15 17:07:51'),
(53, 41, 'desktop', '', 'Chrome', '::1', NULL, NULL, NULL, '2026-01-16 09:07:29'),
(54, 40, 'desktop', '', 'Chrome', '::1', NULL, NULL, NULL, '2026-01-16 09:10:42'),
(55, 41, 'desktop', '', 'Chrome', '::1', NULL, NULL, NULL, '2026-01-16 09:19:06'),
(56, 41, 'desktop', '', 'Firefox', '127.0.0.1', NULL, NULL, NULL, '2026-01-30 19:00:31'),
(57, 41, 'desktop', '', 'Firefox', '127.0.0.1', NULL, NULL, NULL, '2026-01-30 19:51:51'),
(58, 42, 'desktop', '', 'Firefox', '127.0.0.1', NULL, NULL, NULL, '2026-01-30 20:16:13'),
(59, 41, 'desktop', '', 'Firefox', '127.0.0.1', NULL, NULL, NULL, '2026-01-30 20:16:30'),
(60, 42, 'desktop', '', 'Firefox', '127.0.0.1', NULL, NULL, NULL, '2026-01-30 21:09:46'),
(61, 41, 'desktop', '', 'Firefox', '127.0.0.1', NULL, NULL, NULL, '2026-01-30 21:13:15'),
(62, 40, 'desktop', '', 'Chrome', '127.0.0.1', NULL, NULL, NULL, '2026-02-01 14:38:28'),
(63, 40, 'desktop', '', 'Chrome', '127.0.0.1', NULL, NULL, NULL, '2026-02-01 14:51:36'),
(64, 40, 'desktop', '', 'Chrome', '127.0.0.1', NULL, NULL, NULL, '2026-02-01 14:53:43'),
(65, 40, 'desktop', '', 'Chrome', '127.0.0.1', NULL, NULL, NULL, '2026-02-01 14:56:58'),
(66, 40, 'desktop', '', 'Chrome', '::1', NULL, NULL, NULL, '2026-02-02 12:05:33');

--
-- Indexek a kiírt táblákhoz
--

--
-- A tábla indexei `companies`
--
ALTER TABLE `companies`
  ADD PRIMARY KEY (`id`);

--
-- A tábla indexei `company_user`
--
ALTER TABLE `company_user`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `company_id` (`company_id`);

--
-- A tábla indexei `inventories`
--
ALTER TABLE `inventories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `company_id` (`company_id`);

--
-- A tábla indexei `inventory_items`
--
ALTER TABLE `inventory_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `inventory_id` (`inventory_id`),
  ADD KEY `item_id` (`item_id`),
  ADD KEY `user_id` (`user_id`);

--
-- A tábla indexei `inventory_schedules`
--
ALTER TABLE `inventory_schedules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `inventory_id` (`inventory_id`),
  ADD KEY `target_type` (`target_type`);

--
-- A tábla indexei `inventory_submissions`
--
ALTER TABLE `inventory_submissions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `inventory_id` (`inventory_id`),
  ADD KEY `user_id` (`user_id`);

--
-- A tábla indexei `inventory_submission_responses`
--
ALTER TABLE `inventory_submission_responses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `submission_id` (`submission_id`),
  ADD KEY `user_id` (`user_id`);

--
-- A tábla indexei `items`
--
ALTER TABLE `items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `qr_code` (`qr_code`),
  ADD KEY `room_id` (`room_id`);

--
-- A tábla indexei `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`id`),
  ADD KEY `company_id` (`company_id`);

--
-- A tábla indexei `teams`
--
ALTER TABLE `teams`
  ADD PRIMARY KEY (`id`),
  ADD KEY `company_id` (`company_id`);

--
-- A tábla indexei `team_room`
--
ALTER TABLE `team_room`
  ADD PRIMARY KEY (`id`),
  ADD KEY `team_id` (`team_id`),
  ADD KEY `room_id` (`room_id`);

--
-- A tábla indexei `team_user`
--
ALTER TABLE `team_user`
  ADD PRIMARY KEY (`id`),
  ADD KEY `team_id` (`team_id`),
  ADD KEY `user_id` (`user_id`);

--
-- A tábla indexei `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- A tábla indexei `user_devices`
--
ALTER TABLE `user_devices`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- A kiírt táblák AUTO_INCREMENT értéke
--

--
-- AUTO_INCREMENT a táblához `companies`
--
ALTER TABLE `companies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT a táblához `company_user`
--
ALTER TABLE `company_user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT a táblához `inventories`
--
ALTER TABLE `inventories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT a táblához `inventory_items`
--
ALTER TABLE `inventory_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- AUTO_INCREMENT a táblához `inventory_schedules`
--
ALTER TABLE `inventory_schedules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT a táblához `inventory_submissions`
--
ALTER TABLE `inventory_submissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT a táblához `inventory_submission_responses`
--
ALTER TABLE `inventory_submission_responses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT a táblához `items`
--
ALTER TABLE `items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=113;

--
-- AUTO_INCREMENT a táblához `rooms`
--
ALTER TABLE `rooms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT a táblához `teams`
--
ALTER TABLE `teams`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT a táblához `team_room`
--
ALTER TABLE `team_room`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT a táblához `team_user`
--
ALTER TABLE `team_user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT a táblához `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT a táblához `user_devices`
--
ALTER TABLE `user_devices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=67;

--
-- Megkötések a kiírt táblákhoz
--

--
-- Megkötések a táblához `company_user`
--
ALTER TABLE `company_user`
  ADD CONSTRAINT `company_user_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `company_user_ibfk_2` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;

--
-- Megkötések a táblához `inventories`
--
ALTER TABLE `inventories`
  ADD CONSTRAINT `inventories_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;

--
-- Megkötések a táblához `inventory_items`
--
ALTER TABLE `inventory_items`
  ADD CONSTRAINT `inventory_items_ibfk_1` FOREIGN KEY (`inventory_id`) REFERENCES `inventories` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `inventory_items_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `inventory_items_ibfk_3` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Megkötések a táblához `items`
--
ALTER TABLE `items`
  ADD CONSTRAINT `items_ibfk_1` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE;

--
-- Megkötések a táblához `rooms`
--
ALTER TABLE `rooms`
  ADD CONSTRAINT `rooms_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;

--
-- Megkötések a táblához `teams`
--
ALTER TABLE `teams`
  ADD CONSTRAINT `teams_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;

--
-- Megkötések a táblához `team_room`
--
ALTER TABLE `team_room`
  ADD CONSTRAINT `team_room_ibfk_1` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `team_room_ibfk_2` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE;

--
-- Megkötések a táblához `team_user`
--
ALTER TABLE `team_user`
  ADD CONSTRAINT `team_user_ibfk_1` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `team_user_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Megkötések a táblához `user_devices`
--
ALTER TABLE `user_devices`
  ADD CONSTRAINT `user_devices_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
