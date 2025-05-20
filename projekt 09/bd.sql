-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Хост: 127.0.0.1
-- Время создания: Май 20 2025 г., 01:43
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
-- База данных: `bd`
--

-- --------------------------------------------------------

--
-- Структура таблицы `airlines`
--

CREATE TABLE `airlines` (
  `airline_id` int(11) NOT NULL,
  `iata_code` varchar(2) NOT NULL,
  `icao_code` varchar(3) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `logo_url` varchar(512) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `airlines`
--

INSERT INTO `airlines` (`airline_id`, `iata_code`, `icao_code`, `name`, `logo_url`) VALUES
(1, 'AG', NULL, 'AirGO Simulated', NULL),
(2, 'CA', NULL, 'Connect Airways (Simulated)', NULL);

-- --------------------------------------------------------

--
-- Структура таблицы `airports`
--

CREATE TABLE `airports` (
  `airport_id` int(11) NOT NULL,
  `iata_code` varchar(3) NOT NULL,
  `icao_code` varchar(4) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `city` varchar(100) NOT NULL,
  `country_code` varchar(2) NOT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `timezone` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `airports`
--

INSERT INTO `airports` (`airport_id`, `iata_code`, `icao_code`, `name`, `city`, `country_code`, `latitude`, `longitude`, `timezone`) VALUES
(1, 'HUB', NULL, 'Hub Airport Simulated', 'Hub City', 'XX', NULL, NULL, 'UTC'),
(2, 'MOS', NULL, 'Аэропорты Москвы (симуляция)', 'Москва', 'RU', NULL, NULL, 'Europe/Moscow'),
(3, 'PAR', NULL, 'Аэропорты Парижа (симуляция)', 'Париж', 'FR', NULL, NULL, 'Europe/Paris'),
(5, 'DEF', NULL, 'Default Simulated Airport', 'Default City', 'XX', NULL, NULL, 'UTC');

-- --------------------------------------------------------

--
-- Структура таблицы `ancillary_services_booked`
--

CREATE TABLE `ancillary_services_booked` (
  `booked_ancillary_id` bigint(20) NOT NULL,
  `booking_id` bigint(20) NOT NULL,
  `passenger_id` bigint(20) DEFAULT NULL,
  `segment_id` bigint(20) DEFAULT NULL,
  `service_type` enum('baggage','seat_selection','meal','insurance','priority_boarding','other') NOT NULL,
  `service_code` varchar(50) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `currency_code` varchar(3) NOT NULL,
  `provider_reference` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `booked_segments`
--

CREATE TABLE `booked_segments` (
  `segment_id` bigint(20) NOT NULL,
  `booking_id` bigint(20) NOT NULL,
  `sequence_number` int(11) NOT NULL,
  `airline_iata_code` varchar(2) NOT NULL,
  `operating_airline_iata_code` varchar(2) DEFAULT NULL,
  `flight_number` varchar(10) NOT NULL,
  `departure_airport_iata_code` varchar(3) NOT NULL,
  `departure_terminal` varchar(10) DEFAULT NULL,
  `departure_at_utc` timestamp NULL DEFAULT NULL,
  `arrival_airport_iata_code` varchar(3) NOT NULL,
  `arrival_terminal` varchar(10) DEFAULT NULL,
  `arrival_at_utc` timestamp NULL DEFAULT NULL,
  `booking_class` varchar(2) DEFAULT NULL,
  `fare_basis` varchar(20) DEFAULT NULL,
  `aircraft_type` varchar(10) DEFAULT NULL,
  `duration_minutes` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `booked_segments`
--

INSERT INTO `booked_segments` (`segment_id`, `booking_id`, `sequence_number`, `airline_iata_code`, `operating_airline_iata_code`, `flight_number`, `departure_airport_iata_code`, `departure_terminal`, `departure_at_utc`, `arrival_airport_iata_code`, `arrival_terminal`, `arrival_at_utc`, `booking_class`, `fare_basis`, `aircraft_type`, `duration_minutes`, `created_at`) VALUES
(8, 8, 1, 'AG', NULL, '101', 'DEF', NULL, '2025-05-23 06:30:00', 'DEF', NULL, '2025-05-23 09:00:00', 'Y', NULL, 'B737', 150, '2025-05-19 22:18:32');

-- --------------------------------------------------------

--
-- Структура таблицы `bookings`
--

CREATE TABLE `bookings` (
  `booking_id` bigint(20) NOT NULL,
  `user_id` bigint(20) DEFAULT NULL,
  `booking_reference` varchar(20) NOT NULL,
  `status` enum('pending_payment','confirmed','ticketed','cancelled_by_user','cancelled_by_airline','payment_failed','error','completed') NOT NULL DEFAULT 'pending_payment',
  `total_price` decimal(12,2) NOT NULL,
  `currency_code` varchar(3) NOT NULL,
  `contact_email` varchar(255) NOT NULL,
  `contact_phone` varchar(30) NOT NULL,
  `payment_deadline` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `bookings`
--

INSERT INTO `bookings` (`booking_id`, `user_id`, `booking_reference`, `status`, `total_price`, `currency_code`, `contact_email`, `contact_phone`, `payment_deadline`, `created_at`, `updated_at`) VALUES
(8, 8, '2LBV7F', 'confirmed', 7500.00, 'RUB', 'kjrirfsd@ds.dd', '74365434565', NULL, '2025-05-19 22:18:32', '2025-05-19 22:18:32');

-- --------------------------------------------------------

--
-- Структура таблицы `passengers`
--

CREATE TABLE `passengers` (
  `passenger_id` bigint(20) NOT NULL,
  `booking_id` bigint(20) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `date_of_birth` date NOT NULL,
  `gender` enum('male','female','other','undisclosed') NOT NULL,
  `passenger_type` enum('adult','child','infant') NOT NULL,
  `document_type` enum('passport_intl','passport_national','id_card','birth_certificate') NOT NULL,
  `document_number` varchar(50) NOT NULL,
  `document_issuing_country_code` varchar(2) DEFAULT NULL,
  `document_expiry_date` date DEFAULT NULL,
  `nationality_country_code` varchar(2) NOT NULL,
  `ticket_number` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `passengers`
--

INSERT INTO `passengers` (`passenger_id`, `booking_id`, `first_name`, `last_name`, `middle_name`, `date_of_birth`, `gender`, `passenger_type`, `document_type`, `document_number`, `document_issuing_country_code`, `document_expiry_date`, `nationality_country_code`, `ticket_number`, `created_at`, `updated_at`) VALUES
(8, 8, 'Igor', 'Bo', NULL, '2005-02-10', 'male', 'adult', 'passport_intl', '54756546', NULL, '2030-02-03', 'RU', NULL, '2025-05-19 22:18:32', '2025-05-19 22:18:32');

-- --------------------------------------------------------

--
-- Структура таблицы `payments`
--

CREATE TABLE `payments` (
  `payment_id` bigint(20) NOT NULL,
  `booking_id` bigint(20) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `currency_code` varchar(3) NOT NULL,
  `status` enum('pending','succeeded','failed','refunded','partially_refunded','chargeback') NOT NULL DEFAULT 'pending',
  `payment_gateway` enum('stripe','paypal','local_gateway_1','other') NOT NULL,
  `gateway_transaction_id` varchar(255) DEFAULT NULL,
  `gateway_response` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`gateway_response`)),
  `payment_method_details` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `special_offers`
--

CREATE TABLE `special_offers` (
  `offer_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `subtitle` varchar(255) DEFAULT NULL,
  `description_short` text DEFAULT NULL,
  `price_from` decimal(10,2) DEFAULT NULL,
  `currency_code` varchar(3) DEFAULT 'RUB',
  `image_path` varchar(255) DEFAULT NULL,
  `details_page_title` varchar(255) DEFAULT NULL,
  `details_hero_subtitle` varchar(255) DEFAULT NULL,
  `details_main_description` text DEFAULT NULL,
  `details_what_to_see` text DEFAULT NULL,
  `details_direction` varchar(255) DEFAULT NULL,
  `details_price_info` varchar(255) DEFAULT NULL,
  `details_departure_from` varchar(255) DEFAULT NULL,
  `details_travel_period` varchar(255) DEFAULT NULL,
  `details_flight_class` varchar(100) DEFAULT NULL,
  `search_destination_iata` varchar(10) DEFAULT NULL,
  `search_origin_iata` varchar(10) DEFAULT NULL,
  `search_trip_type` enum('round-trip','one-way') DEFAULT 'round-trip',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) DEFAULT 100,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `special_offers`
--

INSERT INTO `special_offers` (`offer_id`, `title`, `subtitle`, `description_short`, `price_from`, `currency_code`, `image_path`, `details_page_title`, `details_hero_subtitle`, `details_main_description`, `details_what_to_see`, `details_direction`, `details_price_info`, `details_departure_from`, `details_travel_period`, `details_flight_class`, `search_destination_iata`, `search_origin_iata`, `search_trip_type`, `is_active`, `sort_order`, `created_at`, `updated_at`) VALUES
(1, 'да', 'жв', NULL, 3000.00, 'RUB', 'uploads/special_offers/offer_682b90d6484bd5.20601661.jpg', 'кпупук', 'укпупап', 'кпвапвап', 'екеуекуе', 'прохладный', '2000', 'спб', 'сегодня', 'Эконом', '4234234', '432423423', 'round-trip', 1, 100, '2025-05-19 13:17:58', '2025-05-19 20:13:10'),
(2, 'Именно', 'Именно', NULL, 2000.00, 'RUB', NULL, 'Прохладный', 'Прохладный', 'Прохладный', 'Прохладный', 'Прохладный', '2000', 'спб', 'сегодня', 'Эконом', 'Санкт-Пете', 'Прохладный', 'round-trip', 1, 100, '2025-05-19 19:07:30', '2025-05-19 19:07:30');

-- --------------------------------------------------------

--
-- Структура таблицы `users`
--

CREATE TABLE `users` (
  `user_id` bigint(20) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(1000) NOT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `phone_number` varchar(30) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_admin` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `users`
--

INSERT INTO `users` (`user_id`, `email`, `password_hash`, `first_name`, `last_name`, `phone_number`, `is_active`, `is_admin`, `created_at`, `updated_at`) VALUES
(2, 'bordd@gmail.cm', '$2y$10$FOCYnYjkGe/RKGveTEHz2.LwjlwbfESMC1YBErAMxVQSnx.x8ubYi', 'dfdd', NULL, '79522744318', 1, 0, '2025-05-17 12:54:19', '2025-05-19 19:05:04'),
(3, 'dffrgf@gg.com', '$2y$10$YJI086wNMdDeaG1dGmm86.GdZYlMQXc.FAOjzE5coTcvhf4S9PM.m', 'вавы', NULL, NULL, 1, 1, '2025-05-18 18:29:39', '2025-05-18 18:30:11'),
(4, 'sandaj@gma.com', '$2y$10$clciegvnLBaN9ncWnWCvJ.uIbLB8gtJtRhOzd/UgYgi63aQwsBFRq', 'Игарь', NULL, NULL, 1, 0, '2025-05-19 12:57:37', '2025-05-19 12:57:37'),
(5, 'sdada@dgsf.cc', '$2y$10$APcgnZnDUfPFSLM0LwWkKe/m4f3ybF2F3C4DkqhHLeu9Yq2LRsxd2', 'Игарь', NULL, NULL, 1, 0, '2025-05-19 13:00:58', '2025-05-19 13:00:58'),
(6, 'dgksdf@dfa.co', '$2y$10$.faJ.PVNvt.Vx/zwmMWwnePmmRgBUay3/u41WEDQpMXHeYXELpPTm', 'Игарь', NULL, NULL, 1, 0, '2025-05-19 13:26:46', '2025-05-19 13:26:46'),
(7, 'sdnfsd@rgfs.dsd', '$2y$10$f57GeWGOmgb9DTg7tP8mZeTiiTG3NcrsTd5u3RZr.agvLwwz.zjFe', 'Игарь', NULL, NULL, 1, 0, '2025-05-19 15:58:03', '2025-05-19 15:58:03'),
(8, 'kjrirfsd@ds.dd', '$2y$10$98hpmt7qyDpvBRtoL8afXudD4yxJSKPGKlRIq2oBZYBjT9.DGdi3K', 'фа', NULL, NULL, 1, 0, '2025-05-19 19:08:14', '2025-05-19 19:08:14');

-- --------------------------------------------------------

--
-- Структура таблицы `user_saved_passengers`
--

CREATE TABLE `user_saved_passengers` (
  `saved_passenger_id` bigint(20) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('male','female','other','undisclosed') DEFAULT NULL,
  `passenger_type` enum('adult','child','infant') DEFAULT NULL,
  `document_type` enum('passport_intl','passport_national','id_card','birth_certificate') DEFAULT NULL,
  `document_number` varchar(50) DEFAULT NULL,
  `document_issuing_country_code` varchar(2) DEFAULT NULL,
  `document_expiry_date` date DEFAULT NULL,
  `nationality_country_code` varchar(2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `user_saved_passengers`
--

INSERT INTO `user_saved_passengers` (`saved_passenger_id`, `user_id`, `first_name`, `last_name`, `middle_name`, `date_of_birth`, `gender`, `passenger_type`, `document_type`, `document_number`, `document_issuing_country_code`, `document_expiry_date`, `nationality_country_code`, `created_at`, `updated_at`) VALUES
(1, 2, 'Игарь', 'Бабич', 'Бо', '2005-12-02', 'male', 'adult', 'passport_intl', '345576784555', 'RU', '2027-03-02', 'RU', '2025-05-17 15:11:19', '2025-05-17 15:34:48');

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `airlines`
--
ALTER TABLE `airlines`
  ADD PRIMARY KEY (`airline_id`),
  ADD UNIQUE KEY `iata_code` (`iata_code`),
  ADD UNIQUE KEY `icao_code` (`icao_code`),
  ADD KEY `idx_airlines_iata_code` (`iata_code`);

--
-- Индексы таблицы `airports`
--
ALTER TABLE `airports`
  ADD PRIMARY KEY (`airport_id`),
  ADD UNIQUE KEY `iata_code` (`iata_code`),
  ADD UNIQUE KEY `icao_code` (`icao_code`),
  ADD KEY `idx_airports_iata_code` (`iata_code`),
  ADD KEY `idx_airports_city` (`city`);

--
-- Индексы таблицы `ancillary_services_booked`
--
ALTER TABLE `ancillary_services_booked`
  ADD PRIMARY KEY (`booked_ancillary_id`),
  ADD KEY `idx_ancillary_services_booked_booking_id` (`booking_id`),
  ADD KEY `idx_ancillary_services_booked_passenger_id` (`passenger_id`),
  ADD KEY `idx_ancillary_services_booked_segment_id` (`segment_id`);

--
-- Индексы таблицы `booked_segments`
--
ALTER TABLE `booked_segments`
  ADD PRIMARY KEY (`segment_id`),
  ADD KEY `booking_id` (`booking_id`),
  ADD KEY `airline_iata_code` (`airline_iata_code`),
  ADD KEY `operating_airline_iata_code` (`operating_airline_iata_code`),
  ADD KEY `departure_airport_iata_code` (`departure_airport_iata_code`),
  ADD KEY `arrival_airport_iata_code` (`arrival_airport_iata_code`);

--
-- Индексы таблицы `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`booking_id`),
  ADD UNIQUE KEY `booking_reference` (`booking_reference`),
  ADD KEY `idx_bookings_user_id` (`user_id`),
  ADD KEY `idx_bookings_booking_reference` (`booking_reference`),
  ADD KEY `idx_bookings_status` (`status`),
  ADD KEY `idx_bookings_created_at` (`created_at`);

--
-- Индексы таблицы `passengers`
--
ALTER TABLE `passengers`
  ADD PRIMARY KEY (`passenger_id`),
  ADD UNIQUE KEY `ticket_number` (`ticket_number`),
  ADD KEY `idx_passengers_booking_id` (`booking_id`),
  ADD KEY `idx_passengers_last_name` (`last_name`),
  ADD KEY `idx_passengers_ticket_number` (`ticket_number`);

--
-- Индексы таблицы `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`payment_id`),
  ADD UNIQUE KEY `gateway_transaction_id` (`gateway_transaction_id`),
  ADD KEY `idx_payments_booking_id` (`booking_id`),
  ADD KEY `idx_payments_gateway_transaction_id` (`gateway_transaction_id`);

--
-- Индексы таблицы `special_offers`
--
ALTER TABLE `special_offers`
  ADD PRIMARY KEY (`offer_id`),
  ADD KEY `idx_special_offers_is_active` (`is_active`,`sort_order`);

--
-- Индексы таблицы `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_users_email` (`email`);

--
-- Индексы таблицы `user_saved_passengers`
--
ALTER TABLE `user_saved_passengers`
  ADD PRIMARY KEY (`saved_passenger_id`),
  ADD KEY `idx_user_saved_passengers_user_id` (`user_id`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `airlines`
--
ALTER TABLE `airlines`
  MODIFY `airline_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT для таблицы `airports`
--
ALTER TABLE `airports`
  MODIFY `airport_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT для таблицы `ancillary_services_booked`
--
ALTER TABLE `ancillary_services_booked`
  MODIFY `booked_ancillary_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `booked_segments`
--
ALTER TABLE `booked_segments`
  MODIFY `segment_id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT для таблицы `bookings`
--
ALTER TABLE `bookings`
  MODIFY `booking_id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT для таблицы `passengers`
--
ALTER TABLE `passengers`
  MODIFY `passenger_id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT для таблицы `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `special_offers`
--
ALTER TABLE `special_offers`
  MODIFY `offer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT для таблицы `users`
--
ALTER TABLE `users`
  MODIFY `user_id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT для таблицы `user_saved_passengers`
--
ALTER TABLE `user_saved_passengers`
  MODIFY `saved_passenger_id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `ancillary_services_booked`
--
ALTER TABLE `ancillary_services_booked`
  ADD CONSTRAINT `ancillary_services_booked_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ancillary_services_booked_ibfk_2` FOREIGN KEY (`passenger_id`) REFERENCES `passengers` (`passenger_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ancillary_services_booked_ibfk_3` FOREIGN KEY (`segment_id`) REFERENCES `booked_segments` (`segment_id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `booked_segments`
--
ALTER TABLE `booked_segments`
  ADD CONSTRAINT `booked_segments_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `booked_segments_ibfk_2` FOREIGN KEY (`airline_iata_code`) REFERENCES `airlines` (`iata_code`),
  ADD CONSTRAINT `booked_segments_ibfk_3` FOREIGN KEY (`operating_airline_iata_code`) REFERENCES `airlines` (`iata_code`),
  ADD CONSTRAINT `booked_segments_ibfk_4` FOREIGN KEY (`departure_airport_iata_code`) REFERENCES `airports` (`iata_code`),
  ADD CONSTRAINT `booked_segments_ibfk_5` FOREIGN KEY (`arrival_airport_iata_code`) REFERENCES `airports` (`iata_code`);

--
-- Ограничения внешнего ключа таблицы `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Ограничения внешнего ключа таблицы `passengers`
--
ALTER TABLE `passengers`
  ADD CONSTRAINT `passengers_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`);

--
-- Ограничения внешнего ключа таблицы `user_saved_passengers`
--
ALTER TABLE `user_saved_passengers`
  ADD CONSTRAINT `user_saved_passengers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
