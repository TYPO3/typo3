-- phpMyAdmin SQL Dump
-- version 5.0.2
-- https://www.phpmyadmin.net/
--
-- Host: db
-- Erstellungszeit: 27. Mai 2020 um 05:57
-- Server-Version: 10.2.25-MariaDB-1:10.2.25+maria~bionic-log
-- PHP-Version: 7.4.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Datenbank: `db`
--

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tx_styleguide_elements_basic`
--

CREATE TABLE `tx_styleguide_elements_basic` (
  `uid` int(10) UNSIGNED NOT NULL,
  `pid` int(11) NOT NULL DEFAULT 0,
  `tstamp` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `crdate` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `cruser_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `deleted` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `hidden` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `sorting` int(11) NOT NULL DEFAULT 0,
  `sys_language_uid` int(11) NOT NULL DEFAULT 0,
  `l10n_parent` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_source` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_state` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `t3_origuid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_diffsource` mediumblob DEFAULT NULL,
  `t3ver_oid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_wsid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_state` smallint(6) NOT NULL DEFAULT 0,
  `t3ver_stage` int(11) NOT NULL DEFAULT 0,
  `t3ver_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_tstamp` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_move_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `input_1` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `input_2` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `input_3` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `input_4` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `input_5` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `input_8` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `input_9` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `input_10` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `input_11` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `input_12` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `input_13` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `input_14` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `input_15` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `input_16` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `input_19` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `input_20` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `input_21` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `input_22` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `input_23` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `input_24` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `input_25` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `input_26` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `input_27` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `input_28` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `input_29` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `input_30` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `input_31` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `input_32` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `input_33` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `input_34` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `input_35` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `input_36` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `input_37` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `input_38` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `input_39` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `input_40` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `input_41` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `input_42` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `inputdatetime_1` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `inputdatetime_2` date DEFAULT NULL,
  `inputdatetime_3` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `inputdatetime_4` datetime DEFAULT NULL,
  `inputdatetime_5` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `inputdatetime_6` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `inputdatetime_7` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `inputdatetime_8` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `inputdatetime_9` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `inputdatetime_10` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `text_1` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `text_2` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `text_3` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `text_4` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `text_5` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `text_6` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `text_7` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `text_8` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `text_9` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `text_10` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `text_11` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `text_12` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `text_13` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `text_14` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `text_15` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `text_16` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `text_17` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `text_18` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `text_19` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `checkbox_1` int(11) NOT NULL DEFAULT 0,
  `checkbox_2` int(11) NOT NULL DEFAULT 0,
  `checkbox_3` int(11) NOT NULL DEFAULT 0,
  `checkbox_4` int(11) NOT NULL DEFAULT 0,
  `checkbox_6` int(11) NOT NULL DEFAULT 0,
  `checkbox_7` int(11) NOT NULL DEFAULT 0,
  `checkbox_8` int(11) NOT NULL DEFAULT 0,
  `checkbox_9` int(11) NOT NULL DEFAULT 0,
  `checkbox_10` int(11) NOT NULL DEFAULT 0,
  `checkbox_11` int(11) NOT NULL DEFAULT 0,
  `checkbox_12` int(11) NOT NULL DEFAULT 0,
  `checkbox_13` int(11) NOT NULL DEFAULT 0,
  `checkbox_14` int(11) NOT NULL DEFAULT 0,
  `checkbox_15` int(11) NOT NULL DEFAULT 0,
  `checkbox_16` int(11) NOT NULL DEFAULT 0,
  `checkbox_17` int(11) NOT NULL DEFAULT 0,
  `checkbox_18` int(11) NOT NULL DEFAULT 0,
  `checkbox_19` int(11) NOT NULL DEFAULT 0,
  `checkbox_20` int(11) NOT NULL DEFAULT 0,
  `checkbox_21` int(11) NOT NULL DEFAULT 0,
  `checkbox_22` int(11) NOT NULL DEFAULT 0,
  `checkbox_23` int(11) NOT NULL DEFAULT 0,
  `checkbox_24` int(11) NOT NULL DEFAULT 0,
  `checkbox_25` int(11) NOT NULL DEFAULT 0,
  `checkbox_26` int(11) NOT NULL DEFAULT 0,
  `radio_1` int(11) NOT NULL DEFAULT 0,
  `radio_2` int(11) NOT NULL DEFAULT 0,
  `radio_3` int(11) NOT NULL DEFAULT 0,
  `radio_4` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `radio_5` int(11) NOT NULL DEFAULT 0,
  `radio_6` int(11) NOT NULL DEFAULT 0,
  `none_1` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `none_2` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `none_3` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `none_4` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `none_5` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `passthrough_1` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `passthrough_2` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_1` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `flex_1` text COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tx_styleguide_elements_group`
--

CREATE TABLE `tx_styleguide_elements_group` (
  `uid` int(10) UNSIGNED NOT NULL,
  `pid` int(11) NOT NULL DEFAULT 0,
  `tstamp` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `crdate` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `cruser_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `deleted` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `hidden` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `sorting` int(11) NOT NULL DEFAULT 0,
  `sys_language_uid` int(11) NOT NULL DEFAULT 0,
  `l10n_parent` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_source` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_state` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `t3_origuid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_diffsource` mediumblob DEFAULT NULL,
  `t3ver_oid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_wsid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_state` smallint(6) NOT NULL DEFAULT 0,
  `t3ver_stage` int(11) NOT NULL DEFAULT 0,
  `t3ver_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_tstamp` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_move_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `group_db_1` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `group_db_2` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `group_db_3` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `group_db_4` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `group_db_5` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `group_db_7` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `group_db_8` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `group_db_9` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `group_folder_1` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `group_requestUpdate_1` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `flex_1` text COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tx_styleguide_elements_rte`
--

CREATE TABLE `tx_styleguide_elements_rte` (
  `uid` int(10) UNSIGNED NOT NULL,
  `pid` int(11) NOT NULL DEFAULT 0,
  `tstamp` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `crdate` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `cruser_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `deleted` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `hidden` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `sorting` int(11) NOT NULL DEFAULT 0,
  `sys_language_uid` int(11) NOT NULL DEFAULT 0,
  `l10n_parent` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_source` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_state` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `t3_origuid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_diffsource` mediumblob DEFAULT NULL,
  `t3ver_oid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_wsid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_state` smallint(6) NOT NULL DEFAULT 0,
  `t3ver_stage` int(11) NOT NULL DEFAULT 0,
  `t3ver_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_tstamp` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_move_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `rte_1` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rte_2` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rte_3` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rte_inline_1` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rte_flex_1` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `input_palette_1` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rte_palette_1` text COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tx_styleguide_elements_rte_flex_1_inline_1_child`
--

CREATE TABLE `tx_styleguide_elements_rte_flex_1_inline_1_child` (
  `uid` int(10) UNSIGNED NOT NULL,
  `pid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `tstamp` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `crdate` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `cruser_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `deleted` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `hidden` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `sorting` int(11) NOT NULL DEFAULT 0,
  `sys_language_uid` int(11) NOT NULL DEFAULT 0,
  `l10n_parent` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_source` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_state` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `l10n_diffsource` mediumblob DEFAULT NULL,
  `parentid` int(11) NOT NULL DEFAULT 0,
  `parenttable` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rte_1` text COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tx_styleguide_elements_rte_inline_1_child`
--

CREATE TABLE `tx_styleguide_elements_rte_inline_1_child` (
  `uid` int(10) UNSIGNED NOT NULL,
  `pid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `tstamp` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `crdate` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `cruser_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `deleted` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `hidden` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `sorting` int(11) NOT NULL DEFAULT 0,
  `sys_language_uid` int(11) NOT NULL DEFAULT 0,
  `l10n_parent` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_source` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_state` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `l10n_diffsource` mediumblob DEFAULT NULL,
  `parentid` int(11) NOT NULL DEFAULT 0,
  `parenttable` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rte_1` text COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tx_styleguide_elements_select`
--

CREATE TABLE `tx_styleguide_elements_select` (
  `uid` int(10) UNSIGNED NOT NULL,
  `pid` int(11) NOT NULL DEFAULT 0,
  `tstamp` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `crdate` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `cruser_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `deleted` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `hidden` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `sorting` int(11) NOT NULL DEFAULT 0,
  `sys_language_uid` int(11) NOT NULL DEFAULT 0,
  `l10n_parent` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_source` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_state` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `t3_origuid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_diffsource` mediumblob DEFAULT NULL,
  `t3ver_oid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_wsid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_state` smallint(6) NOT NULL DEFAULT 0,
  `t3ver_stage` int(11) NOT NULL DEFAULT 0,
  `t3ver_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_tstamp` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_move_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `select_single_1` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `select_single_2` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `select_single_3` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `select_single_4` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `select_single_5` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `select_single_7` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `select_single_8` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `select_single_10` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `select_single_11` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `select_single_12` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `select_single_13` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `select_single_14` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `select_single_15` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `select_singlebox_1` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `select_singlebox_2` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `select_checkbox_1` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `select_checkbox_2` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `select_checkbox_3` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `select_checkbox_4` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `select_multiplesidebyside_1` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `select_multiplesidebyside_2` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `select_multiplesidebyside_3` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `select_multiplesidebyside_5` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `select_multiplesidebyside_6` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `select_multiplesidebyside_7` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `select_multiplesidebyside_8` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `select_multiplesidebyside_9` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `select_tree_1` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `select_tree_2` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `select_tree_3` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `select_tree_4` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `select_tree_5` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `select_tree_6` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `flex_1` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `select_requestUpdate_1` text COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tx_styleguide_elements_select_multiplesidebyside_8_mm`
--

CREATE TABLE `tx_styleguide_elements_select_multiplesidebyside_8_mm` (
  `uid_local` int(11) NOT NULL DEFAULT 0,
  `uid_foreign` int(11) NOT NULL DEFAULT 0,
  `sorting` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tx_styleguide_elements_select_single_12_foreign`
--

CREATE TABLE `tx_styleguide_elements_select_single_12_foreign` (
  `uid` int(10) UNSIGNED NOT NULL,
  `pid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `tstamp` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `crdate` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `cruser_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `deleted` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `hidden` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `sorting` int(11) NOT NULL DEFAULT 0,
  `sys_language_uid` int(11) NOT NULL DEFAULT 0,
  `l10n_parent` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_source` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_state` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `l10n_diffsource` mediumblob DEFAULT NULL,
  `fal_1` text COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tx_styleguide_elements_select_single_15_mm`
--

CREATE TABLE `tx_styleguide_elements_select_single_15_mm` (
  `uid_local` int(11) NOT NULL DEFAULT 0,
  `uid_foreign` int(11) NOT NULL DEFAULT 0,
  `sorting` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tx_styleguide_elements_special`
--

CREATE TABLE `tx_styleguide_elements_special` (
  `uid` int(10) UNSIGNED NOT NULL,
  `pid` int(11) NOT NULL DEFAULT 0,
  `tstamp` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `crdate` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `cruser_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `deleted` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `hidden` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `sorting` int(11) NOT NULL DEFAULT 0,
  `sys_language_uid` int(11) NOT NULL DEFAULT 0,
  `l10n_parent` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_source` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_state` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `t3_origuid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_diffsource` mediumblob DEFAULT NULL,
  `t3ver_oid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_wsid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_state` smallint(6) NOT NULL DEFAULT 0,
  `t3ver_stage` int(11) NOT NULL DEFAULT 0,
  `t3ver_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_tstamp` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_move_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `special_custom_1` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `special_exclude_1` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `special_explicitvalues_1` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `special_languages_1` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `special_modlistgroup_1` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `special_pagetypes_1` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `special_tables_1` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `special_tables_2` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `special_tables_3` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `special_usermods_1` text COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tx_styleguide_elements_t3editor`
--

CREATE TABLE `tx_styleguide_elements_t3editor` (
  `uid` int(10) UNSIGNED NOT NULL,
  `pid` int(11) NOT NULL DEFAULT 0,
  `tstamp` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `crdate` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `cruser_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `deleted` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `hidden` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `sorting` int(11) NOT NULL DEFAULT 0,
  `sys_language_uid` int(11) NOT NULL DEFAULT 0,
  `l10n_parent` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_source` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_state` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `t3_origuid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_diffsource` mediumblob DEFAULT NULL,
  `t3ver_oid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_wsid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_state` smallint(6) NOT NULL DEFAULT 0,
  `t3ver_stage` int(11) NOT NULL DEFAULT 0,
  `t3ver_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_tstamp` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_move_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3editor_1` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `t3editor_reload_1` int(11) NOT NULL DEFAULT 0,
  `t3editor_inline_1` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `t3editor_flex_1` text COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tx_styleguide_elements_t3editor_flex_1_inline_1_child`
--

CREATE TABLE `tx_styleguide_elements_t3editor_flex_1_inline_1_child` (
  `uid` int(10) UNSIGNED NOT NULL,
  `pid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `tstamp` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `crdate` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `cruser_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `deleted` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `hidden` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `sorting` int(11) NOT NULL DEFAULT 0,
  `sys_language_uid` int(11) NOT NULL DEFAULT 0,
  `l10n_parent` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_source` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_state` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `l10n_diffsource` mediumblob DEFAULT NULL,
  `parentid` int(11) NOT NULL DEFAULT 0,
  `parenttable` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `t3editor_1` text COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tx_styleguide_elements_t3editor_inline_1_child`
--

CREATE TABLE `tx_styleguide_elements_t3editor_inline_1_child` (
  `uid` int(10) UNSIGNED NOT NULL,
  `pid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `tstamp` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `crdate` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `cruser_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `deleted` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `hidden` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `sorting` int(11) NOT NULL DEFAULT 0,
  `sys_language_uid` int(11) NOT NULL DEFAULT 0,
  `l10n_parent` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_source` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_state` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `l10n_diffsource` mediumblob DEFAULT NULL,
  `parentid` int(11) NOT NULL DEFAULT 0,
  `parenttable` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `t3editor_1` text COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tx_styleguide_flex`
--

CREATE TABLE `tx_styleguide_flex` (
  `uid` int(10) UNSIGNED NOT NULL,
  `pid` int(11) NOT NULL DEFAULT 0,
  `tstamp` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `crdate` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `cruser_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `deleted` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `hidden` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `sorting` int(11) NOT NULL DEFAULT 0,
  `sys_language_uid` int(11) NOT NULL DEFAULT 0,
  `l10n_parent` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_source` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_state` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `t3_origuid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_diffsource` mediumblob DEFAULT NULL,
  `t3ver_oid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_wsid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_state` smallint(6) NOT NULL DEFAULT 0,
  `t3ver_stage` int(11) NOT NULL DEFAULT 0,
  `t3ver_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_tstamp` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_move_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `flex_1` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `flex_2` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `flex_3` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `flex_4` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `flex_4_select_1` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `flex_5` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `flex_6` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `flex_6_select_1` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `flex_7` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `flex_7_select_1` text COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tx_styleguide_flex_flex_3_inline_1_child`
--

CREATE TABLE `tx_styleguide_flex_flex_3_inline_1_child` (
  `uid` int(10) UNSIGNED NOT NULL,
  `pid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `tstamp` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `crdate` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `cruser_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `deleted` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `hidden` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `sorting` int(11) NOT NULL DEFAULT 0,
  `sys_language_uid` int(11) NOT NULL DEFAULT 0,
  `l10n_parent` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_source` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_state` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `l10n_diffsource` mediumblob DEFAULT NULL,
  `parentid` int(11) NOT NULL DEFAULT 0,
  `parenttable` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `input_1` text COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tx_styleguide_inline_1n`
--

CREATE TABLE `tx_styleguide_inline_1n` (
  `uid` int(10) UNSIGNED NOT NULL,
  `pid` int(11) NOT NULL DEFAULT 0,
  `tstamp` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `crdate` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `cruser_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `deleted` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `hidden` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `sorting` int(11) NOT NULL DEFAULT 0,
  `sys_language_uid` int(11) NOT NULL DEFAULT 0,
  `l10n_parent` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_source` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_state` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `t3_origuid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_diffsource` mediumblob DEFAULT NULL,
  `t3ver_oid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_wsid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_state` smallint(6) NOT NULL DEFAULT 0,
  `t3ver_stage` int(11) NOT NULL DEFAULT 0,
  `t3ver_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_tstamp` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_move_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `inline_1` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tx_styleguide_inline_1n1n`
--

CREATE TABLE `tx_styleguide_inline_1n1n` (
  `uid` int(10) UNSIGNED NOT NULL,
  `pid` int(11) NOT NULL DEFAULT 0,
  `tstamp` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `crdate` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `cruser_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `deleted` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `hidden` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `sorting` int(11) NOT NULL DEFAULT 0,
  `sys_language_uid` int(11) NOT NULL DEFAULT 0,
  `l10n_parent` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_source` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_state` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `t3_origuid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_diffsource` mediumblob DEFAULT NULL,
  `t3ver_oid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_wsid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_state` smallint(6) NOT NULL DEFAULT 0,
  `t3ver_stage` int(11) NOT NULL DEFAULT 0,
  `t3ver_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_tstamp` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_move_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `inline_1` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tx_styleguide_inline_1n1n_child`
--

CREATE TABLE `tx_styleguide_inline_1n1n_child` (
  `uid` int(10) UNSIGNED NOT NULL,
  `pid` int(11) NOT NULL DEFAULT 0,
  `tstamp` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `crdate` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `cruser_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `deleted` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `hidden` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `sorting` int(11) NOT NULL DEFAULT 0,
  `sys_language_uid` int(11) NOT NULL DEFAULT 0,
  `l10n_parent` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_source` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_state` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `t3_origuid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_diffsource` mediumblob DEFAULT NULL,
  `t3ver_oid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_wsid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_state` smallint(6) NOT NULL DEFAULT 0,
  `t3ver_stage` int(11) NOT NULL DEFAULT 0,
  `t3ver_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_tstamp` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_move_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `parentid` int(11) NOT NULL DEFAULT 0,
  `parenttable` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `inline_1` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tx_styleguide_inline_1n1n_childchild`
--

CREATE TABLE `tx_styleguide_inline_1n1n_childchild` (
  `uid` int(10) UNSIGNED NOT NULL,
  `pid` int(11) NOT NULL DEFAULT 0,
  `tstamp` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `crdate` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `cruser_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `deleted` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `hidden` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `sorting` int(11) NOT NULL DEFAULT 0,
  `sys_language_uid` int(11) NOT NULL DEFAULT 0,
  `l10n_parent` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_source` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_state` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `t3_origuid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_diffsource` mediumblob DEFAULT NULL,
  `t3ver_oid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_wsid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_state` smallint(6) NOT NULL DEFAULT 0,
  `t3ver_stage` int(11) NOT NULL DEFAULT 0,
  `t3ver_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_tstamp` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_move_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `parentid` int(11) NOT NULL DEFAULT 0,
  `parenttable` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `input_1` text COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tx_styleguide_inline_1nnol10n`
--

CREATE TABLE `tx_styleguide_inline_1nnol10n` (
  `uid` int(10) UNSIGNED NOT NULL,
  `pid` int(11) NOT NULL DEFAULT 0,
  `tstamp` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `crdate` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `cruser_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `deleted` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `hidden` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `sorting` int(11) NOT NULL DEFAULT 0,
  `sys_language_uid` int(11) NOT NULL DEFAULT 0,
  `l10n_parent` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_source` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_state` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `t3_origuid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_diffsource` mediumblob DEFAULT NULL,
  `t3ver_oid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_wsid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_state` smallint(6) NOT NULL DEFAULT 0,
  `t3ver_stage` int(11) NOT NULL DEFAULT 0,
  `t3ver_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_tstamp` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_move_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `inline_1` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tx_styleguide_inline_1nnol10n_child`
--

CREATE TABLE `tx_styleguide_inline_1nnol10n_child` (
  `uid` int(10) UNSIGNED NOT NULL,
  `pid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `tstamp` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `crdate` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `cruser_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `deleted` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `disable` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `sorting` int(11) NOT NULL DEFAULT 0,
  `parentid` int(11) NOT NULL DEFAULT 0,
  `parenttable` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `input_1` text COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tx_styleguide_inline_1n_child`
--

CREATE TABLE `tx_styleguide_inline_1n_child` (
  `uid` int(10) UNSIGNED NOT NULL,
  `pid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `tstamp` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `crdate` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `cruser_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `deleted` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `disable` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `sorting` int(11) NOT NULL DEFAULT 0,
  `sys_language_uid` int(11) NOT NULL DEFAULT 0,
  `l10n_parent` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_source` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_state` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `l10n_diffsource` mediumblob DEFAULT NULL,
  `parentid` int(11) NOT NULL DEFAULT 0,
  `parenttable` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `input_1` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `input_2` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `group_db_1` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `select_tree_1` text COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tx_styleguide_inline_11`
--

CREATE TABLE `tx_styleguide_inline_11` (
  `uid` int(10) UNSIGNED NOT NULL,
  `pid` int(11) NOT NULL DEFAULT 0,
  `tstamp` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `crdate` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `cruser_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `deleted` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `hidden` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `sorting` int(11) NOT NULL DEFAULT 0,
  `sys_language_uid` int(11) NOT NULL DEFAULT 0,
  `l10n_parent` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_source` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_state` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `t3_origuid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_diffsource` mediumblob DEFAULT NULL,
  `t3ver_oid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_wsid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_state` smallint(6) NOT NULL DEFAULT 0,
  `t3ver_stage` int(11) NOT NULL DEFAULT 0,
  `t3ver_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_tstamp` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_move_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `inline_1` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tx_styleguide_inline_11_child`
--

CREATE TABLE `tx_styleguide_inline_11_child` (
  `uid` int(10) UNSIGNED NOT NULL,
  `pid` int(11) NOT NULL DEFAULT 0,
  `tstamp` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `crdate` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `cruser_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `deleted` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `hidden` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `sorting` int(11) NOT NULL DEFAULT 0,
  `sys_language_uid` int(11) NOT NULL DEFAULT 0,
  `l10n_parent` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_source` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_state` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `t3_origuid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_diffsource` mediumblob DEFAULT NULL,
  `t3ver_oid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_wsid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_state` smallint(6) NOT NULL DEFAULT 0,
  `t3ver_stage` int(11) NOT NULL DEFAULT 0,
  `t3ver_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_tstamp` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_move_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `input_1` text COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tx_styleguide_inline_expand`
--

CREATE TABLE `tx_styleguide_inline_expand` (
  `uid` int(10) UNSIGNED NOT NULL,
  `pid` int(11) NOT NULL DEFAULT 0,
  `tstamp` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `crdate` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `cruser_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `deleted` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `hidden` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `sorting` int(11) NOT NULL DEFAULT 0,
  `sys_language_uid` int(11) NOT NULL DEFAULT 0,
  `l10n_parent` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_source` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_state` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `t3_origuid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_diffsource` mediumblob DEFAULT NULL,
  `t3ver_oid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_wsid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_state` smallint(6) NOT NULL DEFAULT 0,
  `t3ver_stage` int(11) NOT NULL DEFAULT 0,
  `t3ver_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_tstamp` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_move_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `inline_1` text COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tx_styleguide_inline_expandsingle`
--

CREATE TABLE `tx_styleguide_inline_expandsingle` (
  `uid` int(10) UNSIGNED NOT NULL,
  `pid` int(11) NOT NULL DEFAULT 0,
  `tstamp` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `crdate` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `cruser_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `deleted` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `hidden` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `sorting` int(11) NOT NULL DEFAULT 0,
  `sys_language_uid` int(11) NOT NULL DEFAULT 0,
  `l10n_parent` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_source` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_state` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `t3_origuid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_diffsource` mediumblob DEFAULT NULL,
  `t3ver_oid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_wsid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_state` smallint(6) NOT NULL DEFAULT 0,
  `t3ver_stage` int(11) NOT NULL DEFAULT 0,
  `t3ver_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_tstamp` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_move_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `inline_1` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tx_styleguide_inline_expandsingle_child`
--

CREATE TABLE `tx_styleguide_inline_expandsingle_child` (
  `uid` int(10) UNSIGNED NOT NULL,
  `pid` int(11) NOT NULL DEFAULT 0,
  `tstamp` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `crdate` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `cruser_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `deleted` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `hidden` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `sorting` int(11) NOT NULL DEFAULT 0,
  `sys_language_uid` int(11) NOT NULL DEFAULT 0,
  `l10n_parent` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_source` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_state` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `t3_origuid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_diffsource` mediumblob DEFAULT NULL,
  `t3ver_oid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_wsid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_state` smallint(6) NOT NULL DEFAULT 0,
  `t3ver_stage` int(11) NOT NULL DEFAULT 0,
  `t3ver_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_tstamp` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_move_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `parentid` int(11) NOT NULL DEFAULT 0,
  `parenttable` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `input_1` text COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tx_styleguide_inline_expand_inline_1_child`
--

CREATE TABLE `tx_styleguide_inline_expand_inline_1_child` (
  `uid` int(10) UNSIGNED NOT NULL,
  `pid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `tstamp` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `crdate` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `cruser_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `deleted` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `hidden` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `sorting` int(11) NOT NULL DEFAULT 0,
  `sys_language_uid` int(11) NOT NULL DEFAULT 0,
  `l10n_parent` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_source` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_state` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `l10n_diffsource` mediumblob DEFAULT NULL,
  `parentid` int(11) NOT NULL DEFAULT 0,
  `parenttable` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dummy_1` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `inline_fal_1` int(11) NOT NULL DEFAULT 0,
  `rte_1` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `select_tree_1` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `t3editor_1` text COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tx_styleguide_inline_fal`
--

CREATE TABLE `tx_styleguide_inline_fal` (
  `uid` int(10) UNSIGNED NOT NULL,
  `pid` int(11) NOT NULL DEFAULT 0,
  `tstamp` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `crdate` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `cruser_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `deleted` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `hidden` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `sorting` int(11) NOT NULL DEFAULT 0,
  `sys_language_uid` int(11) NOT NULL DEFAULT 0,
  `l10n_parent` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_source` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_state` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `t3_origuid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_diffsource` mediumblob DEFAULT NULL,
  `t3ver_oid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_wsid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_state` smallint(6) NOT NULL DEFAULT 0,
  `t3ver_stage` int(11) NOT NULL DEFAULT 0,
  `t3ver_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_tstamp` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_move_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `inline_1` int(11) NOT NULL DEFAULT 0,
  `inline_2` int(11) NOT NULL DEFAULT 0,
  `inline_3` int(11) NOT NULL DEFAULT 0,
  `inline_flex_1` text COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tx_styleguide_inline_foreignrecorddefaults`
--

CREATE TABLE `tx_styleguide_inline_foreignrecorddefaults` (
  `uid` int(10) UNSIGNED NOT NULL,
  `pid` int(11) NOT NULL DEFAULT 0,
  `tstamp` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `crdate` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `cruser_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `deleted` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `hidden` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `sorting` int(11) NOT NULL DEFAULT 0,
  `sys_language_uid` int(11) NOT NULL DEFAULT 0,
  `l10n_parent` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_source` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_state` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `t3_origuid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_diffsource` mediumblob DEFAULT NULL,
  `t3ver_oid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_wsid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_state` smallint(6) NOT NULL DEFAULT 0,
  `t3ver_stage` int(11) NOT NULL DEFAULT 0,
  `t3ver_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_tstamp` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_move_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `inline_1` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tx_styleguide_inline_foreignrecorddefaults_child`
--

CREATE TABLE `tx_styleguide_inline_foreignrecorddefaults_child` (
  `uid` int(10) UNSIGNED NOT NULL,
  `pid` int(11) NOT NULL DEFAULT 0,
  `tstamp` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `crdate` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `cruser_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `deleted` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `hidden` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `sorting` int(11) NOT NULL DEFAULT 0,
  `sys_language_uid` int(11) NOT NULL DEFAULT 0,
  `l10n_parent` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_source` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_state` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `t3_origuid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_diffsource` mediumblob DEFAULT NULL,
  `t3ver_oid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_wsid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_state` smallint(6) NOT NULL DEFAULT 0,
  `t3ver_stage` int(11) NOT NULL DEFAULT 0,
  `t3ver_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_tstamp` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_move_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `parentid` int(11) NOT NULL DEFAULT 0,
  `parenttable` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `input_1` text COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tx_styleguide_inline_mm`
--

CREATE TABLE `tx_styleguide_inline_mm` (
  `uid` int(10) UNSIGNED NOT NULL,
  `pid` int(11) NOT NULL DEFAULT 0,
  `tstamp` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `crdate` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `cruser_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `deleted` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `hidden` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `sorting` int(11) NOT NULL DEFAULT 0,
  `sys_language_uid` int(11) NOT NULL DEFAULT 0,
  `l10n_parent` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_source` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_state` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `t3_origuid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_diffsource` mediumblob DEFAULT NULL,
  `t3ver_oid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_wsid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_state` smallint(6) NOT NULL DEFAULT 0,
  `t3ver_stage` int(11) NOT NULL DEFAULT 0,
  `t3ver_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_tstamp` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_move_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `title` tinytext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `inline_1` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tx_styleguide_inline_mm_child`
--

CREATE TABLE `tx_styleguide_inline_mm_child` (
  `uid` int(10) UNSIGNED NOT NULL,
  `pid` int(11) NOT NULL DEFAULT 0,
  `tstamp` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `crdate` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `cruser_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `deleted` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `hidden` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `sorting` int(11) NOT NULL DEFAULT 0,
  `sys_language_uid` int(11) NOT NULL DEFAULT 0,
  `l10n_parent` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_source` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_state` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `t3_origuid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_diffsource` mediumblob DEFAULT NULL,
  `t3ver_oid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_wsid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_state` smallint(6) NOT NULL DEFAULT 0,
  `t3ver_stage` int(11) NOT NULL DEFAULT 0,
  `t3ver_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_tstamp` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_move_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `title` tinytext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `parents` int(11) NOT NULL DEFAULT 0,
  `inline_2` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tx_styleguide_inline_mm_childchild`
--

CREATE TABLE `tx_styleguide_inline_mm_childchild` (
  `uid` int(10) UNSIGNED NOT NULL,
  `pid` int(11) NOT NULL DEFAULT 0,
  `tstamp` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `crdate` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `cruser_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `deleted` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `hidden` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `sorting` int(11) NOT NULL DEFAULT 0,
  `sys_language_uid` int(11) NOT NULL DEFAULT 0,
  `l10n_parent` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_source` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_state` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `t3_origuid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_diffsource` mediumblob DEFAULT NULL,
  `t3ver_oid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_wsid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_state` smallint(6) NOT NULL DEFAULT 0,
  `t3ver_stage` int(11) NOT NULL DEFAULT 0,
  `t3ver_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_tstamp` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_move_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `title` tinytext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `parents` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tx_styleguide_inline_mm_child_childchild_rel`
--

CREATE TABLE `tx_styleguide_inline_mm_child_childchild_rel` (
  `uid` int(11) NOT NULL,
  `uid_local` int(11) NOT NULL DEFAULT 0,
  `uid_foreign` int(11) NOT NULL DEFAULT 0,
  `tablenames` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `sorting` int(11) NOT NULL DEFAULT 0,
  `sorting_foreign` int(11) NOT NULL DEFAULT 0,
  `ident` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tx_styleguide_inline_mm_child_rel`
--

CREATE TABLE `tx_styleguide_inline_mm_child_rel` (
  `uid` int(11) NOT NULL,
  `uid_local` int(11) NOT NULL DEFAULT 0,
  `uid_foreign` int(11) NOT NULL DEFAULT 0,
  `tablenames` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `sorting` int(11) NOT NULL DEFAULT 0,
  `sorting_foreign` int(11) NOT NULL DEFAULT 0,
  `ident` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tx_styleguide_inline_mn`
--

CREATE TABLE `tx_styleguide_inline_mn` (
  `uid` int(10) UNSIGNED NOT NULL,
  `pid` int(11) NOT NULL DEFAULT 0,
  `tstamp` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `crdate` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `cruser_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `deleted` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `hidden` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `sorting` int(11) NOT NULL DEFAULT 0,
  `sys_language_uid` int(11) NOT NULL DEFAULT 0,
  `l10n_parent` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_source` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_state` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `t3_origuid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_diffsource` mediumblob DEFAULT NULL,
  `t3ver_oid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_wsid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_state` smallint(6) NOT NULL DEFAULT 0,
  `t3ver_stage` int(11) NOT NULL DEFAULT 0,
  `t3ver_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_tstamp` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_move_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `input_1` tinytext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `inline_1` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tx_styleguide_inline_mngroup`
--

CREATE TABLE `tx_styleguide_inline_mngroup` (
  `uid` int(10) UNSIGNED NOT NULL,
  `pid` int(11) NOT NULL DEFAULT 0,
  `tstamp` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `crdate` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `cruser_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `deleted` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `hidden` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `sorting` int(11) NOT NULL DEFAULT 0,
  `sys_language_uid` int(11) NOT NULL DEFAULT 0,
  `l10n_parent` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_source` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_state` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `t3_origuid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_diffsource` mediumblob DEFAULT NULL,
  `t3ver_oid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_wsid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_state` smallint(6) NOT NULL DEFAULT 0,
  `t3ver_stage` int(11) NOT NULL DEFAULT 0,
  `t3ver_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_tstamp` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_move_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `input_1` tinytext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `inline_1` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tx_styleguide_inline_mngroup_child`
--

CREATE TABLE `tx_styleguide_inline_mngroup_child` (
  `uid` int(10) UNSIGNED NOT NULL,
  `pid` int(11) NOT NULL DEFAULT 0,
  `tstamp` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `crdate` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `cruser_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `deleted` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `hidden` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `sorting` int(11) NOT NULL DEFAULT 0,
  `sys_language_uid` int(11) NOT NULL DEFAULT 0,
  `l10n_parent` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_source` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_state` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `t3_origuid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_diffsource` mediumblob DEFAULT NULL,
  `t3ver_oid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_wsid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_state` smallint(6) NOT NULL DEFAULT 0,
  `t3ver_stage` int(11) NOT NULL DEFAULT 0,
  `t3ver_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_tstamp` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_move_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `input_1` tinytext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `parents` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tx_styleguide_inline_mngroup_mm`
--

CREATE TABLE `tx_styleguide_inline_mngroup_mm` (
  `uid` int(10) UNSIGNED NOT NULL,
  `pid` int(11) NOT NULL DEFAULT 0,
  `tstamp` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `crdate` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `cruser_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `deleted` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `hidden` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `sys_language_uid` int(11) NOT NULL DEFAULT 0,
  `l10n_parent` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_source` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_state` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `t3_origuid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_diffsource` mediumblob DEFAULT NULL,
  `t3ver_oid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_wsid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_state` smallint(6) NOT NULL DEFAULT 0,
  `t3ver_stage` int(11) NOT NULL DEFAULT 0,
  `t3ver_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_tstamp` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_move_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `parentid` int(11) NOT NULL DEFAULT 0,
  `childid` int(11) NOT NULL DEFAULT 0,
  `parentsort` int(11) NOT NULL DEFAULT 0,
  `childsort` int(11) NOT NULL DEFAULT 0,
  `check_1` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tx_styleguide_inline_mnsymmetric`
--

CREATE TABLE `tx_styleguide_inline_mnsymmetric` (
  `uid` int(10) UNSIGNED NOT NULL,
  `pid` int(11) NOT NULL DEFAULT 0,
  `tstamp` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `crdate` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `cruser_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `deleted` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `hidden` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `sorting` int(11) NOT NULL DEFAULT 0,
  `sys_language_uid` int(11) NOT NULL DEFAULT 0,
  `l10n_parent` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_source` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_state` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `t3_origuid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_diffsource` mediumblob DEFAULT NULL,
  `t3ver_oid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_wsid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_state` smallint(6) NOT NULL DEFAULT 0,
  `t3ver_stage` int(11) NOT NULL DEFAULT 0,
  `t3ver_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_tstamp` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_move_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `input_1` tinytext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `branches` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tx_styleguide_inline_mnsymmetric_mm`
--

CREATE TABLE `tx_styleguide_inline_mnsymmetric_mm` (
  `uid` int(10) UNSIGNED NOT NULL,
  `pid` int(11) NOT NULL DEFAULT 0,
  `tstamp` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `crdate` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `cruser_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `deleted` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `hidden` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `sys_language_uid` int(11) NOT NULL DEFAULT 0,
  `l10n_parent` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_source` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_state` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `t3_origuid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_diffsource` mediumblob DEFAULT NULL,
  `t3ver_oid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_wsid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_state` smallint(6) NOT NULL DEFAULT 0,
  `t3ver_stage` int(11) NOT NULL DEFAULT 0,
  `t3ver_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_tstamp` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_move_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `hotelid` int(11) NOT NULL DEFAULT 0,
  `branchid` int(11) NOT NULL DEFAULT 0,
  `hotelsort` int(11) NOT NULL DEFAULT 0,
  `branchsort` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tx_styleguide_inline_mn_child`
--

CREATE TABLE `tx_styleguide_inline_mn_child` (
  `uid` int(10) UNSIGNED NOT NULL,
  `pid` int(11) NOT NULL DEFAULT 0,
  `tstamp` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `crdate` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `cruser_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `deleted` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `hidden` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `sorting` int(11) NOT NULL DEFAULT 0,
  `sys_language_uid` int(11) NOT NULL DEFAULT 0,
  `l10n_parent` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_source` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_state` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `t3_origuid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_diffsource` mediumblob DEFAULT NULL,
  `t3ver_oid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_wsid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_state` smallint(6) NOT NULL DEFAULT 0,
  `t3ver_stage` int(11) NOT NULL DEFAULT 0,
  `t3ver_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_tstamp` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_move_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `input_1` tinytext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `parents` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tx_styleguide_inline_mn_mm`
--

CREATE TABLE `tx_styleguide_inline_mn_mm` (
  `uid` int(10) UNSIGNED NOT NULL,
  `pid` int(11) NOT NULL DEFAULT 0,
  `tstamp` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `crdate` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `cruser_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `deleted` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `hidden` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `sys_language_uid` int(11) NOT NULL DEFAULT 0,
  `l10n_parent` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_source` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_state` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `t3_origuid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_diffsource` mediumblob DEFAULT NULL,
  `t3ver_oid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_wsid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_state` smallint(6) NOT NULL DEFAULT 0,
  `t3ver_stage` int(11) NOT NULL DEFAULT 0,
  `t3ver_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_tstamp` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_move_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `parentid` int(11) NOT NULL DEFAULT 0,
  `childid` int(11) NOT NULL DEFAULT 0,
  `parentsort` int(11) NOT NULL DEFAULT 0,
  `childsort` int(11) NOT NULL DEFAULT 0,
  `check_1` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tx_styleguide_inline_parentnosoftdelete`
--

CREATE TABLE `tx_styleguide_inline_parentnosoftdelete` (
  `uid` int(10) UNSIGNED NOT NULL,
  `pid` int(11) NOT NULL DEFAULT 0,
  `tstamp` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `crdate` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `cruser_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `hidden` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `sys_language_uid` int(11) NOT NULL DEFAULT 0,
  `l10n_parent` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_source` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_state` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `l10n_diffsource` mediumblob DEFAULT NULL,
  `t3ver_oid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_wsid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_state` smallint(6) NOT NULL DEFAULT 0,
  `t3ver_stage` int(11) NOT NULL DEFAULT 0,
  `t3ver_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_tstamp` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_move_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `inline_1` int(11) NOT NULL DEFAULT 0,
  `text_1` text COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tx_styleguide_inline_usecombination`
--

CREATE TABLE `tx_styleguide_inline_usecombination` (
  `uid` int(10) UNSIGNED NOT NULL,
  `pid` int(11) NOT NULL DEFAULT 0,
  `tstamp` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `crdate` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `cruser_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `deleted` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `hidden` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `sorting` int(11) NOT NULL DEFAULT 0,
  `sys_language_uid` int(11) NOT NULL DEFAULT 0,
  `l10n_parent` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_source` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_state` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `t3_origuid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_diffsource` mediumblob DEFAULT NULL,
  `t3ver_oid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_wsid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_state` smallint(6) NOT NULL DEFAULT 0,
  `t3ver_stage` int(11) NOT NULL DEFAULT 0,
  `t3ver_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_tstamp` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_move_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `inline_1` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tx_styleguide_inline_usecombinationbox`
--

CREATE TABLE `tx_styleguide_inline_usecombinationbox` (
  `uid` int(10) UNSIGNED NOT NULL,
  `pid` int(11) NOT NULL DEFAULT 0,
  `tstamp` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `crdate` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `cruser_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `deleted` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `hidden` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `sorting` int(11) NOT NULL DEFAULT 0,
  `sys_language_uid` int(11) NOT NULL DEFAULT 0,
  `l10n_parent` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_source` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_state` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `t3_origuid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_diffsource` mediumblob DEFAULT NULL,
  `t3ver_oid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_wsid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_state` smallint(6) NOT NULL DEFAULT 0,
  `t3ver_stage` int(11) NOT NULL DEFAULT 0,
  `t3ver_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_tstamp` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_move_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `inline_1` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tx_styleguide_inline_usecombinationbox_child`
--

CREATE TABLE `tx_styleguide_inline_usecombinationbox_child` (
  `uid` int(10) UNSIGNED NOT NULL,
  `pid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `tstamp` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `crdate` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `cruser_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `deleted` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `hidden` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `sys_language_uid` int(11) NOT NULL DEFAULT 0,
  `l10n_parent` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_source` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_state` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `l10n_diffsource` mediumblob DEFAULT NULL,
  `input_1` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tx_styleguide_inline_usecombinationbox_mm`
--

CREATE TABLE `tx_styleguide_inline_usecombinationbox_mm` (
  `uid` int(10) UNSIGNED NOT NULL,
  `pid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `tstamp` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `crdate` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `cruser_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `deleted` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `hidden` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `sys_language_uid` int(11) NOT NULL DEFAULT 0,
  `l10n_parent` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_source` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_state` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `l10n_diffsource` mediumblob DEFAULT NULL,
  `select_parent` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `select_child` int(10) UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tx_styleguide_inline_usecombination_child`
--

CREATE TABLE `tx_styleguide_inline_usecombination_child` (
  `uid` int(10) UNSIGNED NOT NULL,
  `pid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `tstamp` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `crdate` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `cruser_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `deleted` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `hidden` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `sys_language_uid` int(11) NOT NULL DEFAULT 0,
  `l10n_parent` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_source` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_state` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `l10n_diffsource` mediumblob DEFAULT NULL,
  `input_1` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tx_styleguide_inline_usecombination_mm`
--

CREATE TABLE `tx_styleguide_inline_usecombination_mm` (
  `uid` int(10) UNSIGNED NOT NULL,
  `pid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `tstamp` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `crdate` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `cruser_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `deleted` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `hidden` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `sys_language_uid` int(11) NOT NULL DEFAULT 0,
  `l10n_parent` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_source` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_state` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `l10n_diffsource` mediumblob DEFAULT NULL,
  `select_parent` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `select_child` int(10) UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tx_styleguide_palette`
--

CREATE TABLE `tx_styleguide_palette` (
  `uid` int(10) UNSIGNED NOT NULL,
  `pid` int(11) NOT NULL DEFAULT 0,
  `tstamp` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `crdate` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `cruser_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `deleted` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `hidden` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `sorting` int(11) NOT NULL DEFAULT 0,
  `sys_language_uid` int(11) NOT NULL DEFAULT 0,
  `l10n_parent` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_source` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_state` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `t3_origuid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_diffsource` mediumblob DEFAULT NULL,
  `t3ver_oid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_wsid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_state` smallint(6) NOT NULL DEFAULT 0,
  `t3ver_stage` int(11) NOT NULL DEFAULT 0,
  `t3ver_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_tstamp` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_move_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `palette_1_1` int(11) NOT NULL DEFAULT 0,
  `palette_1_2` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `palette_1_3` int(11) NOT NULL DEFAULT 0,
  `palette_2_1` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `palette_3_1` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `palette_3_2` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `palette_4_1` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `palette_4_2` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `palette_4_3` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `palette_4_4` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `palette_5_1` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `palette_5_2` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `palette_6_1` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `palette_7_1` text COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tx_styleguide_required`
--

CREATE TABLE `tx_styleguide_required` (
  `uid` int(10) UNSIGNED NOT NULL,
  `pid` int(11) NOT NULL DEFAULT 0,
  `tstamp` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `crdate` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `cruser_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `deleted` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `hidden` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `sorting` int(11) NOT NULL DEFAULT 0,
  `sys_language_uid` int(11) NOT NULL DEFAULT 0,
  `l10n_parent` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_source` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_state` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `t3_origuid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_diffsource` mediumblob DEFAULT NULL,
  `t3ver_oid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_wsid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_state` smallint(6) NOT NULL DEFAULT 0,
  `t3ver_stage` int(11) NOT NULL DEFAULT 0,
  `t3ver_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_tstamp` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_move_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `notrequired_1` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `input_1` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `input_2` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `input_3` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `text_1` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `select_1` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `select_2` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `select_3` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `select_4` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `select_5` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `group_1` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `group_2` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rte_1` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rte_2` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `inline_1` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `inline_2` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `inline_3` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `flex_1` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `flex_2` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `palette_input_1` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `palette_input_2` text COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tx_styleguide_required_flex_2_inline_1_child`
--

CREATE TABLE `tx_styleguide_required_flex_2_inline_1_child` (
  `uid` int(10) UNSIGNED NOT NULL,
  `pid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `tstamp` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `crdate` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `cruser_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `deleted` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `hidden` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `sorting` int(11) NOT NULL DEFAULT 0,
  `sys_language_uid` int(11) NOT NULL DEFAULT 0,
  `l10n_parent` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_source` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_state` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `l10n_diffsource` mediumblob DEFAULT NULL,
  `parentid` int(11) NOT NULL DEFAULT 0,
  `parenttable` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `input_1` text COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tx_styleguide_required_inline_1_child`
--

CREATE TABLE `tx_styleguide_required_inline_1_child` (
  `uid` int(10) UNSIGNED NOT NULL,
  `pid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `tstamp` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `crdate` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `cruser_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `deleted` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `hidden` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `sorting` int(11) NOT NULL DEFAULT 0,
  `sys_language_uid` int(11) NOT NULL DEFAULT 0,
  `l10n_parent` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_source` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_state` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `l10n_diffsource` mediumblob DEFAULT NULL,
  `parentid` int(11) NOT NULL DEFAULT 0,
  `parenttable` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `input_1` text COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tx_styleguide_required_inline_2_child`
--

CREATE TABLE `tx_styleguide_required_inline_2_child` (
  `uid` int(10) UNSIGNED NOT NULL,
  `pid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `tstamp` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `crdate` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `cruser_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `deleted` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `hidden` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `sorting` int(11) NOT NULL DEFAULT 0,
  `sys_language_uid` int(11) NOT NULL DEFAULT 0,
  `l10n_parent` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_source` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_state` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `l10n_diffsource` mediumblob DEFAULT NULL,
  `parentid` int(11) NOT NULL DEFAULT 0,
  `parenttable` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `input_1` text COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tx_styleguide_required_inline_3_child`
--

CREATE TABLE `tx_styleguide_required_inline_3_child` (
  `uid` int(10) UNSIGNED NOT NULL,
  `pid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `tstamp` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `crdate` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `cruser_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `deleted` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `hidden` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `sorting` int(11) NOT NULL DEFAULT 0,
  `sys_language_uid` int(11) NOT NULL DEFAULT 0,
  `l10n_parent` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_source` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_state` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `l10n_diffsource` mediumblob DEFAULT NULL,
  `parentid` int(11) NOT NULL DEFAULT 0,
  `parenttable` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `input_1` text COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tx_styleguide_required_rte_2_child`
--

CREATE TABLE `tx_styleguide_required_rte_2_child` (
  `uid` int(10) UNSIGNED NOT NULL,
  `pid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `tstamp` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `crdate` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `cruser_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `deleted` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `hidden` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `sorting` int(11) NOT NULL DEFAULT 0,
  `sys_language_uid` int(11) NOT NULL DEFAULT 0,
  `l10n_parent` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_source` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_state` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `l10n_diffsource` mediumblob DEFAULT NULL,
  `parentid` int(11) NOT NULL DEFAULT 0,
  `parenttable` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rte_1` text COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tx_styleguide_staticdata`
--

CREATE TABLE `tx_styleguide_staticdata` (
  `uid` int(10) UNSIGNED NOT NULL,
  `pid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `value_1` tinytext COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tx_styleguide_type`
--

CREATE TABLE `tx_styleguide_type` (
  `uid` int(10) UNSIGNED NOT NULL,
  `pid` int(11) NOT NULL DEFAULT 0,
  `tstamp` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `crdate` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `cruser_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `deleted` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `hidden` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `sorting` int(11) NOT NULL DEFAULT 0,
  `sys_language_uid` int(11) NOT NULL DEFAULT 0,
  `l10n_parent` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_source` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_state` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `t3_origuid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_diffsource` mediumblob DEFAULT NULL,
  `t3ver_oid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_wsid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_state` smallint(6) NOT NULL DEFAULT 0,
  `t3ver_stage` int(11) NOT NULL DEFAULT 0,
  `t3ver_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_tstamp` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_move_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `type` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `text_1` text COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tx_styleguide_valuesdefault`
--

CREATE TABLE `tx_styleguide_valuesdefault` (
  `uid` int(10) UNSIGNED NOT NULL,
  `pid` int(11) NOT NULL DEFAULT 0,
  `tstamp` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `crdate` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `cruser_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `deleted` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `hidden` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `sorting` int(11) NOT NULL DEFAULT 0,
  `sys_language_uid` int(11) NOT NULL DEFAULT 0,
  `l10n_parent` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_source` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_state` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `t3_origuid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `l10n_diffsource` mediumblob DEFAULT NULL,
  `t3ver_oid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_wsid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_state` smallint(6) NOT NULL DEFAULT 0,
  `t3ver_stage` int(11) NOT NULL DEFAULT 0,
  `t3ver_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_tstamp` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `t3ver_move_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `input_1` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `input_2` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `input_3` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `text_1` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `checkbox_1` int(11) NOT NULL DEFAULT 0,
  `checkbox_2` int(11) NOT NULL DEFAULT 0,
  `checkbox_3` int(11) NOT NULL DEFAULT 0,
  `radio_1` int(11) NOT NULL DEFAULT 0,
  `select_1` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `select_2` text COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indizes der exportierten Tabellen
--

--
-- Indizes für die Tabelle `tx_styleguide_elements_basic`
--
ALTER TABLE `tx_styleguide_elements_basic`
  ADD PRIMARY KEY (`uid`),
  ADD KEY `parent` (`pid`,`deleted`,`hidden`),
  ADD KEY `translation_source` (`l10n_source`),
  ADD KEY `t3ver_oid` (`t3ver_oid`,`t3ver_wsid`);

--
-- Indizes für die Tabelle `tx_styleguide_elements_group`
--
ALTER TABLE `tx_styleguide_elements_group`
  ADD PRIMARY KEY (`uid`),
  ADD KEY `parent` (`pid`,`deleted`,`hidden`),
  ADD KEY `translation_source` (`l10n_source`),
  ADD KEY `t3ver_oid` (`t3ver_oid`,`t3ver_wsid`);

--
-- Indizes für die Tabelle `tx_styleguide_elements_rte`
--
ALTER TABLE `tx_styleguide_elements_rte`
  ADD PRIMARY KEY (`uid`),
  ADD KEY `parent` (`pid`,`deleted`,`hidden`),
  ADD KEY `translation_source` (`l10n_source`),
  ADD KEY `t3ver_oid` (`t3ver_oid`,`t3ver_wsid`);

--
-- Indizes für die Tabelle `tx_styleguide_elements_rte_flex_1_inline_1_child`
--
ALTER TABLE `tx_styleguide_elements_rte_flex_1_inline_1_child`
  ADD PRIMARY KEY (`uid`),
  ADD KEY `parent` (`pid`,`deleted`,`hidden`),
  ADD KEY `translation_source` (`l10n_source`);

--
-- Indizes für die Tabelle `tx_styleguide_elements_rte_inline_1_child`
--
ALTER TABLE `tx_styleguide_elements_rte_inline_1_child`
  ADD PRIMARY KEY (`uid`),
  ADD KEY `parent` (`pid`,`deleted`,`hidden`),
  ADD KEY `translation_source` (`l10n_source`);

--
-- Indizes für die Tabelle `tx_styleguide_elements_select`
--
ALTER TABLE `tx_styleguide_elements_select`
  ADD PRIMARY KEY (`uid`),
  ADD KEY `parent` (`pid`,`deleted`,`hidden`),
  ADD KEY `translation_source` (`l10n_source`),
  ADD KEY `t3ver_oid` (`t3ver_oid`,`t3ver_wsid`);

--
-- Indizes für die Tabelle `tx_styleguide_elements_select_multiplesidebyside_8_mm`
--
ALTER TABLE `tx_styleguide_elements_select_multiplesidebyside_8_mm`
  ADD KEY `uid_local` (`uid_local`),
  ADD KEY `uid_foreign` (`uid_foreign`);

--
-- Indizes für die Tabelle `tx_styleguide_elements_select_single_12_foreign`
--
ALTER TABLE `tx_styleguide_elements_select_single_12_foreign`
  ADD PRIMARY KEY (`uid`),
  ADD KEY `parent` (`pid`,`deleted`,`hidden`),
  ADD KEY `translation_source` (`l10n_source`);

--
-- Indizes für die Tabelle `tx_styleguide_elements_select_single_15_mm`
--
ALTER TABLE `tx_styleguide_elements_select_single_15_mm`
  ADD KEY `uid_local` (`uid_local`),
  ADD KEY `uid_foreign` (`uid_foreign`);

--
-- Indizes für die Tabelle `tx_styleguide_elements_special`
--
ALTER TABLE `tx_styleguide_elements_special`
  ADD PRIMARY KEY (`uid`),
  ADD KEY `parent` (`pid`,`deleted`,`hidden`),
  ADD KEY `translation_source` (`l10n_source`),
  ADD KEY `t3ver_oid` (`t3ver_oid`,`t3ver_wsid`);

--
-- Indizes für die Tabelle `tx_styleguide_elements_t3editor`
--
ALTER TABLE `tx_styleguide_elements_t3editor`
  ADD PRIMARY KEY (`uid`),
  ADD KEY `parent` (`pid`,`deleted`,`hidden`),
  ADD KEY `translation_source` (`l10n_source`),
  ADD KEY `t3ver_oid` (`t3ver_oid`,`t3ver_wsid`);

--
-- Indizes für die Tabelle `tx_styleguide_elements_t3editor_flex_1_inline_1_child`
--
ALTER TABLE `tx_styleguide_elements_t3editor_flex_1_inline_1_child`
  ADD PRIMARY KEY (`uid`),
  ADD KEY `parent` (`pid`,`deleted`,`hidden`),
  ADD KEY `translation_source` (`l10n_source`);

--
-- Indizes für die Tabelle `tx_styleguide_elements_t3editor_inline_1_child`
--
ALTER TABLE `tx_styleguide_elements_t3editor_inline_1_child`
  ADD PRIMARY KEY (`uid`),
  ADD KEY `parent` (`pid`,`deleted`,`hidden`),
  ADD KEY `translation_source` (`l10n_source`);

--
-- Indizes für die Tabelle `tx_styleguide_flex`
--
ALTER TABLE `tx_styleguide_flex`
  ADD PRIMARY KEY (`uid`),
  ADD KEY `parent` (`pid`,`deleted`,`hidden`),
  ADD KEY `translation_source` (`l10n_source`),
  ADD KEY `t3ver_oid` (`t3ver_oid`,`t3ver_wsid`);

--
-- Indizes für die Tabelle `tx_styleguide_flex_flex_3_inline_1_child`
--
ALTER TABLE `tx_styleguide_flex_flex_3_inline_1_child`
  ADD PRIMARY KEY (`uid`),
  ADD KEY `parent` (`pid`,`deleted`,`hidden`),
  ADD KEY `translation_source` (`l10n_source`);

--
-- Indizes für die Tabelle `tx_styleguide_inline_1n`
--
ALTER TABLE `tx_styleguide_inline_1n`
  ADD PRIMARY KEY (`uid`),
  ADD KEY `parent` (`pid`,`deleted`,`hidden`),
  ADD KEY `translation_source` (`l10n_source`),
  ADD KEY `t3ver_oid` (`t3ver_oid`,`t3ver_wsid`);

--
-- Indizes für die Tabelle `tx_styleguide_inline_1n1n`
--
ALTER TABLE `tx_styleguide_inline_1n1n`
  ADD PRIMARY KEY (`uid`),
  ADD KEY `parent` (`pid`,`deleted`,`hidden`),
  ADD KEY `translation_source` (`l10n_source`),
  ADD KEY `t3ver_oid` (`t3ver_oid`,`t3ver_wsid`);

--
-- Indizes für die Tabelle `tx_styleguide_inline_1n1n_child`
--
ALTER TABLE `tx_styleguide_inline_1n1n_child`
  ADD PRIMARY KEY (`uid`),
  ADD KEY `parent` (`pid`,`deleted`,`hidden`),
  ADD KEY `translation_source` (`l10n_source`),
  ADD KEY `t3ver_oid` (`t3ver_oid`,`t3ver_wsid`);

--
-- Indizes für die Tabelle `tx_styleguide_inline_1n1n_childchild`
--
ALTER TABLE `tx_styleguide_inline_1n1n_childchild`
  ADD PRIMARY KEY (`uid`),
  ADD KEY `parent` (`pid`,`deleted`,`hidden`),
  ADD KEY `translation_source` (`l10n_source`),
  ADD KEY `t3ver_oid` (`t3ver_oid`,`t3ver_wsid`);

--
-- Indizes für die Tabelle `tx_styleguide_inline_1nnol10n`
--
ALTER TABLE `tx_styleguide_inline_1nnol10n`
  ADD PRIMARY KEY (`uid`),
  ADD KEY `parent` (`pid`,`deleted`,`hidden`),
  ADD KEY `translation_source` (`l10n_source`),
  ADD KEY `t3ver_oid` (`t3ver_oid`,`t3ver_wsid`);

--
-- Indizes für die Tabelle `tx_styleguide_inline_1nnol10n_child`
--
ALTER TABLE `tx_styleguide_inline_1nnol10n_child`
  ADD PRIMARY KEY (`uid`),
  ADD KEY `parent` (`pid`,`deleted`,`disable`);

--
-- Indizes für die Tabelle `tx_styleguide_inline_1n_child`
--
ALTER TABLE `tx_styleguide_inline_1n_child`
  ADD PRIMARY KEY (`uid`),
  ADD KEY `parent` (`pid`,`deleted`,`disable`),
  ADD KEY `translation_source` (`l10n_source`);

--
-- Indizes für die Tabelle `tx_styleguide_inline_11`
--
ALTER TABLE `tx_styleguide_inline_11`
  ADD PRIMARY KEY (`uid`),
  ADD KEY `parent` (`pid`,`deleted`,`hidden`),
  ADD KEY `translation_source` (`l10n_source`),
  ADD KEY `t3ver_oid` (`t3ver_oid`,`t3ver_wsid`);

--
-- Indizes für die Tabelle `tx_styleguide_inline_11_child`
--
ALTER TABLE `tx_styleguide_inline_11_child`
  ADD PRIMARY KEY (`uid`),
  ADD KEY `parent` (`pid`,`deleted`,`hidden`),
  ADD KEY `translation_source` (`l10n_source`),
  ADD KEY `t3ver_oid` (`t3ver_oid`,`t3ver_wsid`);

--
-- Indizes für die Tabelle `tx_styleguide_inline_expand`
--
ALTER TABLE `tx_styleguide_inline_expand`
  ADD PRIMARY KEY (`uid`),
  ADD KEY `parent` (`pid`,`deleted`,`hidden`),
  ADD KEY `translation_source` (`l10n_source`),
  ADD KEY `t3ver_oid` (`t3ver_oid`,`t3ver_wsid`);

--
-- Indizes für die Tabelle `tx_styleguide_inline_expandsingle`
--
ALTER TABLE `tx_styleguide_inline_expandsingle`
  ADD PRIMARY KEY (`uid`),
  ADD KEY `parent` (`pid`,`deleted`,`hidden`),
  ADD KEY `translation_source` (`l10n_source`),
  ADD KEY `t3ver_oid` (`t3ver_oid`,`t3ver_wsid`);

--
-- Indizes für die Tabelle `tx_styleguide_inline_expandsingle_child`
--
ALTER TABLE `tx_styleguide_inline_expandsingle_child`
  ADD PRIMARY KEY (`uid`),
  ADD KEY `parent` (`pid`,`deleted`,`hidden`),
  ADD KEY `translation_source` (`l10n_source`),
  ADD KEY `t3ver_oid` (`t3ver_oid`,`t3ver_wsid`);

--
-- Indizes für die Tabelle `tx_styleguide_inline_expand_inline_1_child`
--
ALTER TABLE `tx_styleguide_inline_expand_inline_1_child`
  ADD PRIMARY KEY (`uid`),
  ADD KEY `parent` (`pid`,`deleted`,`hidden`),
  ADD KEY `translation_source` (`l10n_source`);

--
-- Indizes für die Tabelle `tx_styleguide_inline_fal`
--
ALTER TABLE `tx_styleguide_inline_fal`
  ADD PRIMARY KEY (`uid`),
  ADD KEY `parent` (`pid`,`deleted`,`hidden`),
  ADD KEY `translation_source` (`l10n_source`),
  ADD KEY `t3ver_oid` (`t3ver_oid`,`t3ver_wsid`);

--
-- Indizes für die Tabelle `tx_styleguide_inline_foreignrecorddefaults`
--
ALTER TABLE `tx_styleguide_inline_foreignrecorddefaults`
  ADD PRIMARY KEY (`uid`),
  ADD KEY `parent` (`pid`,`deleted`,`hidden`),
  ADD KEY `translation_source` (`l10n_source`),
  ADD KEY `t3ver_oid` (`t3ver_oid`,`t3ver_wsid`);

--
-- Indizes für die Tabelle `tx_styleguide_inline_foreignrecorddefaults_child`
--
ALTER TABLE `tx_styleguide_inline_foreignrecorddefaults_child`
  ADD PRIMARY KEY (`uid`),
  ADD KEY `parent` (`pid`,`deleted`,`hidden`),
  ADD KEY `translation_source` (`l10n_source`),
  ADD KEY `t3ver_oid` (`t3ver_oid`,`t3ver_wsid`);

--
-- Indizes für die Tabelle `tx_styleguide_inline_mm`
--
ALTER TABLE `tx_styleguide_inline_mm`
  ADD PRIMARY KEY (`uid`),
  ADD KEY `parent` (`pid`,`deleted`,`hidden`),
  ADD KEY `translation_source` (`l10n_source`),
  ADD KEY `t3ver_oid` (`t3ver_oid`,`t3ver_wsid`);

--
-- Indizes für die Tabelle `tx_styleguide_inline_mm_child`
--
ALTER TABLE `tx_styleguide_inline_mm_child`
  ADD PRIMARY KEY (`uid`),
  ADD KEY `parent` (`pid`,`deleted`,`hidden`),
  ADD KEY `translation_source` (`l10n_source`),
  ADD KEY `t3ver_oid` (`t3ver_oid`,`t3ver_wsid`);

--
-- Indizes für die Tabelle `tx_styleguide_inline_mm_childchild`
--
ALTER TABLE `tx_styleguide_inline_mm_childchild`
  ADD PRIMARY KEY (`uid`),
  ADD KEY `parent` (`pid`,`deleted`,`hidden`),
  ADD KEY `translation_source` (`l10n_source`),
  ADD KEY `t3ver_oid` (`t3ver_oid`,`t3ver_wsid`);

--
-- Indizes für die Tabelle `tx_styleguide_inline_mm_child_childchild_rel`
--
ALTER TABLE `tx_styleguide_inline_mm_child_childchild_rel`
  ADD PRIMARY KEY (`uid`),
  ADD KEY `uid_local` (`uid_local`),
  ADD KEY `uid_foreign` (`uid_foreign`);

--
-- Indizes für die Tabelle `tx_styleguide_inline_mm_child_rel`
--
ALTER TABLE `tx_styleguide_inline_mm_child_rel`
  ADD PRIMARY KEY (`uid`),
  ADD KEY `uid_local` (`uid_local`),
  ADD KEY `uid_foreign` (`uid_foreign`);

--
-- Indizes für die Tabelle `tx_styleguide_inline_mn`
--
ALTER TABLE `tx_styleguide_inline_mn`
  ADD PRIMARY KEY (`uid`),
  ADD KEY `parent` (`pid`,`deleted`,`hidden`),
  ADD KEY `translation_source` (`l10n_source`),
  ADD KEY `t3ver_oid` (`t3ver_oid`,`t3ver_wsid`);

--
-- Indizes für die Tabelle `tx_styleguide_inline_mngroup`
--
ALTER TABLE `tx_styleguide_inline_mngroup`
  ADD PRIMARY KEY (`uid`),
  ADD KEY `parent` (`pid`,`deleted`,`hidden`),
  ADD KEY `translation_source` (`l10n_source`),
  ADD KEY `t3ver_oid` (`t3ver_oid`,`t3ver_wsid`);

--
-- Indizes für die Tabelle `tx_styleguide_inline_mngroup_child`
--
ALTER TABLE `tx_styleguide_inline_mngroup_child`
  ADD PRIMARY KEY (`uid`),
  ADD KEY `parent` (`pid`,`deleted`,`hidden`),
  ADD KEY `translation_source` (`l10n_source`),
  ADD KEY `t3ver_oid` (`t3ver_oid`,`t3ver_wsid`);

--
-- Indizes für die Tabelle `tx_styleguide_inline_mngroup_mm`
--
ALTER TABLE `tx_styleguide_inline_mngroup_mm`
  ADD PRIMARY KEY (`uid`),
  ADD KEY `parent` (`pid`,`deleted`,`hidden`),
  ADD KEY `translation_source` (`l10n_source`),
  ADD KEY `t3ver_oid` (`t3ver_oid`,`t3ver_wsid`);

--
-- Indizes für die Tabelle `tx_styleguide_inline_mnsymmetric`
--
ALTER TABLE `tx_styleguide_inline_mnsymmetric`
  ADD PRIMARY KEY (`uid`),
  ADD KEY `parent` (`pid`,`deleted`,`hidden`),
  ADD KEY `translation_source` (`l10n_source`),
  ADD KEY `t3ver_oid` (`t3ver_oid`,`t3ver_wsid`);

--
-- Indizes für die Tabelle `tx_styleguide_inline_mnsymmetric_mm`
--
ALTER TABLE `tx_styleguide_inline_mnsymmetric_mm`
  ADD PRIMARY KEY (`uid`),
  ADD KEY `parent` (`pid`,`deleted`,`hidden`),
  ADD KEY `translation_source` (`l10n_source`),
  ADD KEY `t3ver_oid` (`t3ver_oid`,`t3ver_wsid`);

--
-- Indizes für die Tabelle `tx_styleguide_inline_mn_child`
--
ALTER TABLE `tx_styleguide_inline_mn_child`
  ADD PRIMARY KEY (`uid`),
  ADD KEY `parent` (`pid`,`deleted`,`hidden`),
  ADD KEY `translation_source` (`l10n_source`),
  ADD KEY `t3ver_oid` (`t3ver_oid`,`t3ver_wsid`);

--
-- Indizes für die Tabelle `tx_styleguide_inline_mn_mm`
--
ALTER TABLE `tx_styleguide_inline_mn_mm`
  ADD PRIMARY KEY (`uid`),
  ADD KEY `parent` (`pid`,`deleted`,`hidden`),
  ADD KEY `translation_source` (`l10n_source`),
  ADD KEY `t3ver_oid` (`t3ver_oid`,`t3ver_wsid`);

--
-- Indizes für die Tabelle `tx_styleguide_inline_parentnosoftdelete`
--
ALTER TABLE `tx_styleguide_inline_parentnosoftdelete`
  ADD PRIMARY KEY (`uid`),
  ADD KEY `parent` (`pid`,`hidden`),
  ADD KEY `translation_source` (`l10n_source`),
  ADD KEY `t3ver_oid` (`t3ver_oid`,`t3ver_wsid`);

--
-- Indizes für die Tabelle `tx_styleguide_inline_usecombination`
--
ALTER TABLE `tx_styleguide_inline_usecombination`
  ADD PRIMARY KEY (`uid`),
  ADD KEY `parent` (`pid`,`deleted`,`hidden`),
  ADD KEY `translation_source` (`l10n_source`),
  ADD KEY `t3ver_oid` (`t3ver_oid`,`t3ver_wsid`);

--
-- Indizes für die Tabelle `tx_styleguide_inline_usecombinationbox`
--
ALTER TABLE `tx_styleguide_inline_usecombinationbox`
  ADD PRIMARY KEY (`uid`),
  ADD KEY `parent` (`pid`,`deleted`,`hidden`),
  ADD KEY `translation_source` (`l10n_source`),
  ADD KEY `t3ver_oid` (`t3ver_oid`,`t3ver_wsid`);

--
-- Indizes für die Tabelle `tx_styleguide_inline_usecombinationbox_child`
--
ALTER TABLE `tx_styleguide_inline_usecombinationbox_child`
  ADD PRIMARY KEY (`uid`),
  ADD KEY `parent` (`pid`,`deleted`,`hidden`),
  ADD KEY `translation_source` (`l10n_source`);

--
-- Indizes für die Tabelle `tx_styleguide_inline_usecombinationbox_mm`
--
ALTER TABLE `tx_styleguide_inline_usecombinationbox_mm`
  ADD PRIMARY KEY (`uid`),
  ADD KEY `parent` (`pid`,`deleted`,`hidden`),
  ADD KEY `translation_source` (`l10n_source`);

--
-- Indizes für die Tabelle `tx_styleguide_inline_usecombination_child`
--
ALTER TABLE `tx_styleguide_inline_usecombination_child`
  ADD PRIMARY KEY (`uid`),
  ADD KEY `parent` (`pid`,`deleted`,`hidden`),
  ADD KEY `translation_source` (`l10n_source`);

--
-- Indizes für die Tabelle `tx_styleguide_inline_usecombination_mm`
--
ALTER TABLE `tx_styleguide_inline_usecombination_mm`
  ADD PRIMARY KEY (`uid`),
  ADD KEY `parent` (`pid`,`deleted`,`hidden`),
  ADD KEY `translation_source` (`l10n_source`);

--
-- Indizes für die Tabelle `tx_styleguide_palette`
--
ALTER TABLE `tx_styleguide_palette`
  ADD PRIMARY KEY (`uid`),
  ADD KEY `parent` (`pid`,`deleted`,`hidden`),
  ADD KEY `translation_source` (`l10n_source`),
  ADD KEY `t3ver_oid` (`t3ver_oid`,`t3ver_wsid`);

--
-- Indizes für die Tabelle `tx_styleguide_required`
--
ALTER TABLE `tx_styleguide_required`
  ADD PRIMARY KEY (`uid`),
  ADD KEY `parent` (`pid`,`deleted`,`hidden`),
  ADD KEY `translation_source` (`l10n_source`),
  ADD KEY `t3ver_oid` (`t3ver_oid`,`t3ver_wsid`);

--
-- Indizes für die Tabelle `tx_styleguide_required_flex_2_inline_1_child`
--
ALTER TABLE `tx_styleguide_required_flex_2_inline_1_child`
  ADD PRIMARY KEY (`uid`),
  ADD KEY `parent` (`pid`,`deleted`,`hidden`),
  ADD KEY `translation_source` (`l10n_source`);

--
-- Indizes für die Tabelle `tx_styleguide_required_inline_1_child`
--
ALTER TABLE `tx_styleguide_required_inline_1_child`
  ADD PRIMARY KEY (`uid`),
  ADD KEY `parent` (`pid`,`deleted`,`hidden`),
  ADD KEY `translation_source` (`l10n_source`);

--
-- Indizes für die Tabelle `tx_styleguide_required_inline_2_child`
--
ALTER TABLE `tx_styleguide_required_inline_2_child`
  ADD PRIMARY KEY (`uid`),
  ADD KEY `parent` (`pid`,`deleted`,`hidden`),
  ADD KEY `translation_source` (`l10n_source`);

--
-- Indizes für die Tabelle `tx_styleguide_required_inline_3_child`
--
ALTER TABLE `tx_styleguide_required_inline_3_child`
  ADD PRIMARY KEY (`uid`),
  ADD KEY `parent` (`pid`,`deleted`,`hidden`),
  ADD KEY `translation_source` (`l10n_source`);

--
-- Indizes für die Tabelle `tx_styleguide_required_rte_2_child`
--
ALTER TABLE `tx_styleguide_required_rte_2_child`
  ADD PRIMARY KEY (`uid`),
  ADD KEY `parent` (`pid`,`deleted`,`hidden`),
  ADD KEY `translation_source` (`l10n_source`);

--
-- Indizes für die Tabelle `tx_styleguide_staticdata`
--
ALTER TABLE `tx_styleguide_staticdata`
  ADD PRIMARY KEY (`uid`),
  ADD KEY `parent` (`pid`);

--
-- Indizes für die Tabelle `tx_styleguide_type`
--
ALTER TABLE `tx_styleguide_type`
  ADD PRIMARY KEY (`uid`),
  ADD KEY `parent` (`pid`,`deleted`,`hidden`),
  ADD KEY `translation_source` (`l10n_source`),
  ADD KEY `t3ver_oid` (`t3ver_oid`,`t3ver_wsid`);

--
-- Indizes für die Tabelle `tx_styleguide_valuesdefault`
--
ALTER TABLE `tx_styleguide_valuesdefault`
  ADD PRIMARY KEY (`uid`),
  ADD KEY `parent` (`pid`,`deleted`,`hidden`),
  ADD KEY `translation_source` (`l10n_source`),
  ADD KEY `t3ver_oid` (`t3ver_oid`,`t3ver_wsid`);

--
-- AUTO_INCREMENT für exportierte Tabellen
--

--
-- AUTO_INCREMENT für Tabelle `tx_styleguide_elements_basic`
--
ALTER TABLE `tx_styleguide_elements_basic`
  MODIFY `uid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `tx_styleguide_elements_group`
--
ALTER TABLE `tx_styleguide_elements_group`
  MODIFY `uid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `tx_styleguide_elements_rte`
--
ALTER TABLE `tx_styleguide_elements_rte`
  MODIFY `uid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `tx_styleguide_elements_rte_flex_1_inline_1_child`
--
ALTER TABLE `tx_styleguide_elements_rte_flex_1_inline_1_child`
  MODIFY `uid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `tx_styleguide_elements_rte_inline_1_child`
--
ALTER TABLE `tx_styleguide_elements_rte_inline_1_child`
  MODIFY `uid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `tx_styleguide_elements_select`
--
ALTER TABLE `tx_styleguide_elements_select`
  MODIFY `uid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `tx_styleguide_elements_select_single_12_foreign`
--
ALTER TABLE `tx_styleguide_elements_select_single_12_foreign`
  MODIFY `uid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `tx_styleguide_elements_special`
--
ALTER TABLE `tx_styleguide_elements_special`
  MODIFY `uid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `tx_styleguide_elements_t3editor`
--
ALTER TABLE `tx_styleguide_elements_t3editor`
  MODIFY `uid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `tx_styleguide_elements_t3editor_flex_1_inline_1_child`
--
ALTER TABLE `tx_styleguide_elements_t3editor_flex_1_inline_1_child`
  MODIFY `uid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `tx_styleguide_elements_t3editor_inline_1_child`
--
ALTER TABLE `tx_styleguide_elements_t3editor_inline_1_child`
  MODIFY `uid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `tx_styleguide_flex`
--
ALTER TABLE `tx_styleguide_flex`
  MODIFY `uid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `tx_styleguide_flex_flex_3_inline_1_child`
--
ALTER TABLE `tx_styleguide_flex_flex_3_inline_1_child`
  MODIFY `uid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `tx_styleguide_inline_1n`
--
ALTER TABLE `tx_styleguide_inline_1n`
  MODIFY `uid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `tx_styleguide_inline_1n1n`
--
ALTER TABLE `tx_styleguide_inline_1n1n`
  MODIFY `uid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `tx_styleguide_inline_1n1n_child`
--
ALTER TABLE `tx_styleguide_inline_1n1n_child`
  MODIFY `uid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `tx_styleguide_inline_1n1n_childchild`
--
ALTER TABLE `tx_styleguide_inline_1n1n_childchild`
  MODIFY `uid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `tx_styleguide_inline_1nnol10n`
--
ALTER TABLE `tx_styleguide_inline_1nnol10n`
  MODIFY `uid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `tx_styleguide_inline_1nnol10n_child`
--
ALTER TABLE `tx_styleguide_inline_1nnol10n_child`
  MODIFY `uid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `tx_styleguide_inline_1n_child`
--
ALTER TABLE `tx_styleguide_inline_1n_child`
  MODIFY `uid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `tx_styleguide_inline_11`
--
ALTER TABLE `tx_styleguide_inline_11`
  MODIFY `uid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `tx_styleguide_inline_11_child`
--
ALTER TABLE `tx_styleguide_inline_11_child`
  MODIFY `uid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `tx_styleguide_inline_expand`
--
ALTER TABLE `tx_styleguide_inline_expand`
  MODIFY `uid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `tx_styleguide_inline_expandsingle`
--
ALTER TABLE `tx_styleguide_inline_expandsingle`
  MODIFY `uid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `tx_styleguide_inline_expandsingle_child`
--
ALTER TABLE `tx_styleguide_inline_expandsingle_child`
  MODIFY `uid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `tx_styleguide_inline_expand_inline_1_child`
--
ALTER TABLE `tx_styleguide_inline_expand_inline_1_child`
  MODIFY `uid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `tx_styleguide_inline_fal`
--
ALTER TABLE `tx_styleguide_inline_fal`
  MODIFY `uid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `tx_styleguide_inline_foreignrecorddefaults`
--
ALTER TABLE `tx_styleguide_inline_foreignrecorddefaults`
  MODIFY `uid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `tx_styleguide_inline_foreignrecorddefaults_child`
--
ALTER TABLE `tx_styleguide_inline_foreignrecorddefaults_child`
  MODIFY `uid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `tx_styleguide_inline_mm`
--
ALTER TABLE `tx_styleguide_inline_mm`
  MODIFY `uid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `tx_styleguide_inline_mm_child`
--
ALTER TABLE `tx_styleguide_inline_mm_child`
  MODIFY `uid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `tx_styleguide_inline_mm_childchild`
--
ALTER TABLE `tx_styleguide_inline_mm_childchild`
  MODIFY `uid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `tx_styleguide_inline_mm_child_childchild_rel`
--
ALTER TABLE `tx_styleguide_inline_mm_child_childchild_rel`
  MODIFY `uid` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `tx_styleguide_inline_mm_child_rel`
--
ALTER TABLE `tx_styleguide_inline_mm_child_rel`
  MODIFY `uid` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `tx_styleguide_inline_mn`
--
ALTER TABLE `tx_styleguide_inline_mn`
  MODIFY `uid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `tx_styleguide_inline_mngroup`
--
ALTER TABLE `tx_styleguide_inline_mngroup`
  MODIFY `uid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `tx_styleguide_inline_mngroup_child`
--
ALTER TABLE `tx_styleguide_inline_mngroup_child`
  MODIFY `uid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `tx_styleguide_inline_mngroup_mm`
--
ALTER TABLE `tx_styleguide_inline_mngroup_mm`
  MODIFY `uid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `tx_styleguide_inline_mnsymmetric`
--
ALTER TABLE `tx_styleguide_inline_mnsymmetric`
  MODIFY `uid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `tx_styleguide_inline_mnsymmetric_mm`
--
ALTER TABLE `tx_styleguide_inline_mnsymmetric_mm`
  MODIFY `uid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `tx_styleguide_inline_mn_child`
--
ALTER TABLE `tx_styleguide_inline_mn_child`
  MODIFY `uid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `tx_styleguide_inline_mn_mm`
--
ALTER TABLE `tx_styleguide_inline_mn_mm`
  MODIFY `uid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `tx_styleguide_inline_parentnosoftdelete`
--
ALTER TABLE `tx_styleguide_inline_parentnosoftdelete`
  MODIFY `uid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `tx_styleguide_inline_usecombination`
--
ALTER TABLE `tx_styleguide_inline_usecombination`
  MODIFY `uid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `tx_styleguide_inline_usecombinationbox`
--
ALTER TABLE `tx_styleguide_inline_usecombinationbox`
  MODIFY `uid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `tx_styleguide_inline_usecombinationbox_child`
--
ALTER TABLE `tx_styleguide_inline_usecombinationbox_child`
  MODIFY `uid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `tx_styleguide_inline_usecombinationbox_mm`
--
ALTER TABLE `tx_styleguide_inline_usecombinationbox_mm`
  MODIFY `uid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `tx_styleguide_inline_usecombination_child`
--
ALTER TABLE `tx_styleguide_inline_usecombination_child`
  MODIFY `uid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `tx_styleguide_inline_usecombination_mm`
--
ALTER TABLE `tx_styleguide_inline_usecombination_mm`
  MODIFY `uid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `tx_styleguide_palette`
--
ALTER TABLE `tx_styleguide_palette`
  MODIFY `uid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `tx_styleguide_required`
--
ALTER TABLE `tx_styleguide_required`
  MODIFY `uid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `tx_styleguide_required_flex_2_inline_1_child`
--
ALTER TABLE `tx_styleguide_required_flex_2_inline_1_child`
  MODIFY `uid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `tx_styleguide_required_inline_1_child`
--
ALTER TABLE `tx_styleguide_required_inline_1_child`
  MODIFY `uid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `tx_styleguide_required_inline_2_child`
--
ALTER TABLE `tx_styleguide_required_inline_2_child`
  MODIFY `uid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `tx_styleguide_required_inline_3_child`
--
ALTER TABLE `tx_styleguide_required_inline_3_child`
  MODIFY `uid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `tx_styleguide_required_rte_2_child`
--
ALTER TABLE `tx_styleguide_required_rte_2_child`
  MODIFY `uid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `tx_styleguide_staticdata`
--
ALTER TABLE `tx_styleguide_staticdata`
  MODIFY `uid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `tx_styleguide_type`
--
ALTER TABLE `tx_styleguide_type`
  MODIFY `uid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `tx_styleguide_valuesdefault`
--
ALTER TABLE `tx_styleguide_valuesdefault`
  MODIFY `uid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
