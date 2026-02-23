-- Table des commentaires de fin de mission (une ligne par intervention)
CREATE TABLE IF NOT EXISTS `intervention_commentaires` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `intervention_id` int(11) NOT NULL,
  `points_positifs` text DEFAULT NULL,
  `points_negatifs` text DEFAULT NULL,
  `problemes_internes_crf` text DEFAULT NULL,
  `problemes_externes_crf` text DEFAULT NULL,
  `zone_libre` text DEFAULT NULL,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `intervention_id` (`intervention_id`),
  CONSTRAINT `intervention_commentaires_ibfk_1` FOREIGN KEY (`intervention_id`) REFERENCES `interventions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
