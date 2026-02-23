-- Mise à jour de la table presets_messages pour le système de Quick Texts
-- Ajout des colonnes titre et contenu, modification de la catégorie

-- Étape 1: Modifier la structure de la table
-- Note: Exécutez ces commandes une par une. Si une colonne existe déjà, vous pouvez ignorer l'erreur.

-- Modifier le type de la colonne categorie pour accepter les nouvelles valeurs
ALTER TABLE `presets_messages` 
  MODIFY COLUMN `categorie` VARCHAR(50) NOT NULL;

-- Ajouter la colonne titre (si elle n'existe pas déjà)
ALTER TABLE `presets_messages` 
  ADD COLUMN `titre` VARCHAR(255) NULL AFTER `categorie`;

-- Ajouter la colonne contenu (si elle n'existe pas déjà)
ALTER TABLE `presets_messages` 
  ADD COLUMN `contenu` TEXT NULL AFTER `titre`;

-- Étape 2: Vider la table
TRUNCATE TABLE `presets_messages`;

-- Étape 3: Insérer les nouveaux presets pour la catégorie "Renseignement"
INSERT INTO `presets_messages` (`categorie`, `titre`, `contenu`) VALUES
('Renseignement', 'Prise Indicatif Opéra', 'Je prends l''indicatif Opéra.'),
('Renseignement', 'PMA Activé', 'PMA activé, adresse : '),
('Renseignement', 'CAI Activé', 'Le CAI est activé, adresse : '),
('Renseignement', 'PRM Activé', 'Le PRM est activé, adresse : '),
('Renseignement', 'CHU Activé', 'Le CHU est activé, adresse : '),
('Renseignement', 'CGMS Activé', 'Le CGMS est activé, adresse : '),
('Renseignement', 'COT Déporté Activé', 'Le COT déporté est activé, adresse : '),
('Renseignement', 'VLPC Activé', 'VLPC est activé.'),
('Renseignement', 'PRV Activé', 'Le PRV est activé, adresse : '),
('Renseignement', 'Leader CAI', 'Leader CAI est confié à : '),
('Renseignement', 'Leader CHU', 'Leader CHU est confié à : '),
('Renseignement', 'Leader Parc', 'Leader Parc est confié à : '),
('Renseignement', 'Leader PMA', 'Leader PMA est confié à : '),
('Renseignement', 'Leader Ramassage', 'Leader Ramassage est confié à : '),
('Renseignement', 'Leader PC', 'Leader PC est confié à : ');

-- Étape 4: Insérer le preset pour la catégorie "Ambiance"
INSERT INTO `presets_messages` (`categorie`, `titre`, `contenu`) VALUES
('Ambiance', 'Point de situation complet', 'Les secours sont confrontés à XX.\nLe dénombrement terrain fait état de XX UA, UR, IMPL.\nLe bilan humain peut évoluer de façon favorable/défavorable.\nLes efforts se portent sur : la prise en charge des victimes au PMA / les évacuations / l''ouverture d''un CAI.\nLes reconnaissances sont : toujours en cours / terminées.\nLe PRM est activé (adresse : ...)\nLe PMA est activé (adresse : ...)\nLe CAI est activé (adresse : ...)\nJe confirme : le déclenchement / attente avant déclenchement du département.');

-- Étape 5: Insérer les presets pour la catégorie "Demande"
INSERT INTO `presets_messages` (`categorie`, `titre`, `contenu`) VALUES
('Demande', 'Demande VPSP + Bénévoles', 'Je demande XX VPSP et XX bénévoles pour la prise en charge de victimes et l''ouverture d''un CAI.'),
('Demande', 'Renfort Cadre', 'Je demande un renfort Cadre.'),
('Demande', 'Renfort Logistique', 'Je demande un renfort Logistique.'),
('Demande', 'Ouverture COT', 'Je demande l''ouverture du COT 92.'),
('Demande', 'Déclenchement ARAMIS', 'Je demande le déclenchement du plan ARAMIS.'),
('Demande', 'Escorte Motorisée', 'Je demande une escorte motorisée.'),
('Demande', 'Demande de VPSP', 'Je demande XX VPSP.'),
('Demande', 'Demande de VL', 'Je demande XX VL.'),
('Demande', 'Demande Minibus', 'Je demande XX Minibus.'),
('Demande', 'Demande Remorque Télécom', 'Je demande XX remorque télécom.');

