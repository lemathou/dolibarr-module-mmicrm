CREATE TABLE `llx_c_propal_topic_main` (
  `rowid` int(11) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 0,
  `pos` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `code` varchar(16) NOT NULL,
  `label` varchar(128) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `llx_c_propal_topic_main`
  ADD PRIMARY KEY (`rowid`),
  ADD UNIQUE KEY `pos` (`pos`,`active`);

ALTER TABLE `llx_c_propal_topic_main`
  MODIFY `rowid` int(11) NOT NULL AUTO_INCREMENT;
