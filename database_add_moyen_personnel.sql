-- Création de la table moyen_personnel pour stocker les noms et prénoms des équipiers
CREATE TABLE IF NOT EXISTS moyen_personnel (
    id INT AUTO_INCREMENT PRIMARY KEY,
    moyen_id INT NOT NULL,
    role VARCHAR(50) NOT NULL,
    nom_prenom VARCHAR(255) NOT NULL,
    FOREIGN KEY (moyen_id) REFERENCES moyens(id) ON DELETE CASCADE,
    INDEX idx_moyen_id (moyen_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

