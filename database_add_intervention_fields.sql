-- Ajout des nouvelles colonnes à la table interventions
ALTER TABLE interventions 
ADD COLUMN adresse VARCHAR(255) NULL,
ADD COLUMN is_drm TINYINT(1) DEFAULT 0,
ADD COLUMN drm_numero VARCHAR(50) NULL,
ADD COLUMN adresse_chu VARCHAR(255) NULL,
ADD COLUMN adresse_prm VARCHAR(255) NULL,
ADD COLUMN plan_aramis TINYINT(1) DEFAULT 0;

