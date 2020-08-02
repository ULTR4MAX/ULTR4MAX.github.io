-- phpMyAdmin SQL Dump
-- version 4.7.7
-- https://www.phpmyadmin.net/
--
-- Хост: localhost
-- Время создания: Июн 20 2020 г., 21:57
-- Версия сервера: 5.7.21-20-beget-5.7.21-20-1-log
-- Версия PHP: 5.6.40

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `y95109te_121212`
--

-- --------------------------------------------------------

--
-- Структура таблицы `roll_bets`
--
-- Создание: Июн 20 2020 г., 18:55
--

DROP TABLE IF EXISTS `roll_bets`;
CREATE TABLE `roll_bets` (
  `id` int(11) NOT NULL,
  `uid` int(11) NOT NULL,
  `uname` varchar(40) NOT NULL,
  `uicon` varchar(300) NOT NULL,
  `color` char(10) NOT NULL,
  `sum` bigint(20) NOT NULL,
  `date` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Структура таблицы `roll_game`
--
-- Создание: Июн 20 2020 г., 18:55
-- Последнее обновление: Июн 20 2020 г., 18:55
--

DROP TABLE IF EXISTS `roll_game`;
CREATE TABLE `roll_game` (
  `id` int(11) NOT NULL,
  `hash` varchar(64) NOT NULL,
  `result` varchar(12) NOT NULL,
  `fword` varchar(16) NOT NULL,
  `sword` varchar(16) NOT NULL,
  `date` int(11) DEFAULT NULL,
  `segment` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Структура таблицы `roll_users`
--
-- Создание: Июн 20 2020 г., 18:55
--

DROP TABLE IF EXISTS `roll_users`;
CREATE TABLE `roll_users` (
  `uid` int(11) NOT NULL,
  `uname` varchar(40) NOT NULL,
  `icon` varchar(300) NOT NULL,
  `score` int(11) NOT NULL,
  `ref` int(11) DEFAULT NULL,
  `sync` char(25) NOT NULL,
  `token` varchar(300) NOT NULL,
  `notify` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `roll_bets`
--
ALTER TABLE `roll_bets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id` (`id`);

--
-- Индексы таблицы `roll_game`
--
ALTER TABLE `roll_game`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id` (`id`);

--
-- Индексы таблицы `roll_users`
--
ALTER TABLE `roll_users`
  ADD PRIMARY KEY (`uid`),
  ADD UNIQUE KEY `id` (`uid`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `roll_bets`
--
ALTER TABLE `roll_bets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `roll_game`
--
ALTER TABLE `roll_game`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
