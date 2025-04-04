-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Хост: 127.0.0.1
-- Время создания: Апр 04 2025 г., 08:33
-- Версия сервера: 10.4.32-MariaDB
-- Версия PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `register`
--

-- --------------------------------------------------------

--
-- Структура таблицы `account`
--

CREATE TABLE `account` (
  `ID` int(11) NOT NULL,
  `Name` varchar(50) NOT NULL,
  `Balance` decimal(10,2) NOT NULL,
  `Currency` varchar(3) DEFAULT NULL,
  `UserID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `budget`
--

CREATE TABLE `budget` (
  `ID` int(11) NOT NULL,
  `Amount` decimal(10,2) NOT NULL,
  `Period` varchar(50) NOT NULL,
  `UserID` int(11) NOT NULL,
  `CategoryID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `category`
--

CREATE TABLE `category` (
  `ID` int(11) NOT NULL,
  `Name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `currency`
--

CREATE TABLE `currency` (
  `Code` varchar(3) NOT NULL,
  `Name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `transaction`
--

CREATE TABLE `transaction` (
  `ID` int(11) NOT NULL,
  `Type` enum('Доход','Расход') NOT NULL,
  `Amount` decimal(10,2) NOT NULL,
  `Date` date NOT NULL,
  `AccountID` int(11) NOT NULL,
  `CategoryID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `pass` varchar(1000) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `avatar` varchar(255) DEFAULT 'uploads/avatars/default.png',
  `is_admin` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Флаг администратора (1=да, 0=нет)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `pass`, `created_at`, `avatar`, `is_admin`) VALUES
(24, 'poni', 'djasfh@d.net', '$2y$10$mqT5hp5kK8mS7cxvVChu1OqQZLsSniG97OMET5YOrYJafJnuV8P86', '2025-02-26 10:43:58', 'uploads/avatars/default.png', 0),
(25, 'popi', 'djsfh@d.net', '$2y$10$Pn29TYb5Ht4xaF8eKtXlnOqtltLPJgkOevzZKKLty5rCa.gcjf/fO', '2025-02-26 10:46:15', 'uploads/avatars/default.png', 0),
(26, '1234567890abcdef', 'dfh@d.net', '$2y$10$eqMwb1XkNvN0IbQft.9J4OXRLSRJs9MR0Bseht1nu4Y/TH/qsGKSS', '2025-02-26 10:50:21', 'uploads/avatars/default.png', 0),
(27, 'hywjhhaa', 'fgdfgdfg@wsa.sd', '$2y$10$S0J6j7uyuZwglnPxrUlZCub/ukF9nisfG8K9jN/lvJ.YSkoWqdqvK', '2025-03-06 08:14:14', 'uploads/avatars/default.png', 0),
(30, '', '', '$2y$10$SESZ3j47RFLbhERcQBEYq.JZRBo5SNiWDaeMFzEmbMsNkONVyY1ou', '2025-03-12 08:40:40', 'uploads/avatars/default.png', 0),
(31, 'Fardd', 'figgfgfdj@ff.dr', '$2y$10$PVycg/IGUH6Z6qA8TYmqHec5mAeQSvKp376BI0KlbzqEKMV2dr4wG', '2025-03-12 08:40:51', 'uploads/avatars/default.png', 0),
(32, 'aasfd', 'dfsfdfdsffdfsdf@ww.vff', '$2y$10$kc.zK0uepEHvcew1xkiXxOWl63ilttM14fNcFqh06UG6me8pFE.VO', '2025-03-17 09:19:51', 'uploads/avatars/default.png', 0),
(33, 'rtr', 'kikondosk@gmail.com', '$2y$10$EEDg4Rw3KPWStxW5yzgij.CPK8kx4DQ4UTqXXPkmhZbrK/vbMVG6W', '2025-03-26 07:08:43', 'uploads/avatars/avatar_33_1743065544.png', 0),
(35, 'tss', 'sdsdfsf@sds.we', '$2y$10$tfBdPlxlaafLkLFy9hMhJOk6dCU4ojqlhj4pm3Cw67nxo1iwMsD3q', '2025-04-03 09:17:58', 'uploads/avatars/default.png', 0),
(37, 'root', 'sdsddfddsf@sds.we', '$2y$10$uHxwhkm2OFJKXN8UKEsAm.TJhIFFA80GlyIgtahGI7.xanBjlB6H2', '2025-04-03 09:20:23', 'uploads/avatars/default.png', 1),
(38, 'tssdfdfss', 'dfdfdsf@sds.sas', '$2y$10$M5dFhDrgZYzcoHjNy7yrtO3o7A7nrtJNOFWGsXkiFjhJvtOpWDYAe', '2025-04-04 06:32:16', 'uploads/avatars/default.png', 1);

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `account`
--
ALTER TABLE `account`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `Currency` (`Currency`),
  ADD KEY `Currency_2` (`Currency`),
  ADD KEY `UserID` (`UserID`);

--
-- Индексы таблицы `budget`
--
ALTER TABLE `budget`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `CategoryID` (`CategoryID`),
  ADD KEY `UserID` (`UserID`);

--
-- Индексы таблицы `category`
--
ALTER TABLE `category`
  ADD PRIMARY KEY (`ID`),
  ADD UNIQUE KEY `Name` (`Name`);

--
-- Индексы таблицы `currency`
--
ALTER TABLE `currency`
  ADD PRIMARY KEY (`Code`);

--
-- Индексы таблицы `transaction`
--
ALTER TABLE `transaction`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `AccountID` (`AccountID`),
  ADD KEY `CategoryID` (`CategoryID`);

--
-- Индексы таблицы `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `account`
--
ALTER TABLE `account`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `budget`
--
ALTER TABLE `budget`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `category`
--
ALTER TABLE `category`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `transaction`
--
ALTER TABLE `transaction`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `account`
--
ALTER TABLE `account`
  ADD CONSTRAINT `account_ibfk_2` FOREIGN KEY (`Currency`) REFERENCES `currency` (`Code`) ON DELETE SET NULL,
  ADD CONSTRAINT `account_ibfk_3` FOREIGN KEY (`UserID`) REFERENCES `users` (`id`);

--
-- Ограничения внешнего ключа таблицы `budget`
--
ALTER TABLE `budget`
  ADD CONSTRAINT `budget_ibfk_2` FOREIGN KEY (`CategoryID`) REFERENCES `category` (`ID`),
  ADD CONSTRAINT `budget_ibfk_3` FOREIGN KEY (`UserID`) REFERENCES `users` (`id`);

--
-- Ограничения внешнего ключа таблицы `transaction`
--
ALTER TABLE `transaction`
  ADD CONSTRAINT `transaction_ibfk_1` FOREIGN KEY (`AccountID`) REFERENCES `account` (`ID`),
  ADD CONSTRAINT `transaction_ibfk_2` FOREIGN KEY (`CategoryID`) REFERENCES `category` (`ID`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
