-- =====================================================================
--  Vite & Gourmand - Jeu de données de démonstration
--  À exécuter APRÈS schema.sql
--
--  Comptes de test (mots de passe conformes à la politique : 10 car., maj., min., chiffre, spécial)
--    Administrateur : jose@vite-gourmand.fr    / Jose#Admin2026
--    Employé        : julie@vite-gourmand.fr   / Julie#Employe2026
--    Utilisateur    : client@exemple.fr        / Client#Test2026
--    Employé désactivé (test) : ancien@vite-gourmand.fr / Ancien#Employe2026
--
--  Le compte administrateur n'est créable QUE par ce script SQL :
--  aucun formulaire de l'application ne permet de créer un administrateur.
-- =====================================================================

SET NAMES utf8mb4;

INSERT INTO role (role_id, libelle) VALUES
 (1, 'utilisateur'), (2, 'employe'), (3, 'administrateur');

INSERT INTO utilisateur (utilisateur_id, role_id, email, password, nom, prenom, telephone, adresse_postale, code_postal, ville, actif) VALUES
 (1, 3, 'jose@vite-gourmand.fr',  '$2y$10$IJzJgBdTJGA/rc7TFH6G4.Nwgx8Ri7ucojqxJ8GaSrfs3fmLHNCKO', 'Martin',  'José',   '0611223344', '12 quai des Chartrons', '33000', 'Bordeaux', 1),
 (2, 2, 'julie@vite-gourmand.fr', '$2y$10$d5W8AtLPBtpf2lTyHHT/q.NW4/478ZlpfwCghC2p7Ho/1uBYpsPEC', 'Martin',  'Julie',  '0622334455', '12 quai des Chartrons', '33000', 'Bordeaux', 1),
 (3, 1, 'client@exemple.fr',      '$2y$10$M7NBOpto5PNtCLyE1teHYOnYzdwO.qxzeu9Z.hzseJ/mMcx6tuEA2', 'Durand',  'Claire', '0633445566', '8 rue Sainte-Catherine', '33000', 'Bordeaux', 1),
 (4, 1, 'marc@exemple.fr',        '$2y$10$M7NBOpto5PNtCLyE1teHYOnYzdwO.qxzeu9Z.hzseJ/mMcx6tuEA2', 'Leroy',   'Marc',   '0644556677', '3 avenue de la Libération', '33700', 'Mérignac', 1),
 (5, 1, 'sophie@exemple.fr',      '$2y$10$M7NBOpto5PNtCLyE1teHYOnYzdwO.qxzeu9Z.hzseJ/mMcx6tuEA2', 'Bernard', 'Sophie', '0755667788', '21 cours Victor Hugo', '33000', 'Bordeaux', 1),
 (6, 2, 'ancien@vite-gourmand.fr','$2y$10$YaGwaHqk8DSDkn.3tklttunfA66uYz6UaImGW8ZhjWKDS.88VveMi', 'Petit',   'Lucas',  '0666778899', NULL, NULL, NULL, 0);

INSERT INTO theme (theme_id, libelle) VALUES
 (1, 'Noël'), (2, 'Pâques'), (3, 'Classique'), (4, 'Événement');

INSERT INTO regime (regime_id, libelle) VALUES
 (1, 'Classique'), (2, 'Végétarien'), (3, 'Vegan'), (4, 'Sans gluten');

INSERT INTO allergene (allergene_id, libelle) VALUES
 (1, 'Gluten'), (2, 'Crustacés'), (3, 'Œufs'), (4, 'Poisson'), (5, 'Arachides'), (6, 'Soja'),
 (7, 'Lait'), (8, 'Fruits à coque'), (9, 'Céleri'), (10, 'Moutarde'), (11, 'Sésame'),
 (12, 'Sulfites'), (13, 'Lupin'), (14, 'Mollusques');

INSERT INTO menu (menu_id, titre, description, theme_id, regime_id, nombre_personne_minimum, prix_minimum, conditions, quantite_restante) VALUES
 (1, 'Réveillon bordelais', 'Un menu de fête autour des produits du Sud-Ouest : foie gras, chapon fermier et bûche maison.', 1, 1, 6, 270.00,
     'Commande au minimum 10 jours avant la prestation. Produits frais : à conserver au réfrigérateur (0-4 °C) et à consommer dans les 24 h.', 8),
 (2, 'Noël au jardin', 'Un Noël végétarien gourmand : velouté de châtaigne, wellington de légumes et entremets chocolat.', 1, 2, 4, 140.00,
     'Commande au minimum 7 jours avant la prestation. Conserver au frais, réchauffer 20 min à 160 °C.', 10),
 (3, 'Brunch de Pâques', 'Œufs mimosa, agneau confit 7 heures et nid de Pâques chocolat-praliné pour un déjeuner en famille.', 2, 1, 8, 296.00,
     'Commande au minimum 2 semaines avant la prestation. Matériel de service prêté sur demande (à restituer sous 10 jours ouvrés).', 5),
 (4, 'Classique du marché', 'Le menu du quotidien, de saison : entrée fraîcheur, plat mijoté et tarte fine aux fruits.', 3, 1, 2, 56.00,
     'Commande au minimum 3 jours avant la prestation. À consommer dans les 48 h.', 25),
 (5, 'Green & Vegan', 'Un menu 100 % végétal et coloré : houmous de betterave, curry de légumes et mousse coco-mangue.', 3, 3, 4, 108.00,
     'Commande au minimum 5 jours avant la prestation. Sans produit d''origine animale.', 15),
 (6, 'Cocktail d''entreprise', 'Pièces cocktail salées et sucrées pour vos séminaires, inaugurations et événements professionnels.', 4, 4, 20, 500.00,
     'Commande au minimum 3 semaines avant la prestation. Matériel (plateaux, chauffe-plats) prêté : restitution obligatoire sous 10 jours ouvrés, sinon 600 € de frais (voir CGV).', 3);

INSERT INTO menu_image (menu_id, chemin, texte_alt, ordre) VALUES
 (1, '/assets/img/menus/reveillon-1.svg', 'Assiette de foie gras et chapon rôti sur une table de fête', 1),
 (1, '/assets/img/menus/reveillon-2.svg', 'Bûche de Noël maison décorée', 2),
 (2, '/assets/img/menus/noel-jardin-1.svg', 'Wellington de légumes tranché', 1),
 (3, '/assets/img/menus/paques-1.svg', 'Gigot d''agneau confit et légumes printaniers', 1),
 (3, '/assets/img/menus/paques-2.svg', 'Nid de Pâques en chocolat', 2),
 (4, '/assets/img/menus/classique-1.svg', 'Plat mijoté servi en cocotte', 1),
 (5, '/assets/img/menus/vegan-1.svg', 'Bol de curry de légumes coloré', 1),
 (6, '/assets/img/menus/cocktail-1.svg', 'Plateau de pièces cocktail salées', 1),
 (6, '/assets/img/menus/cocktail-2.svg', 'Mignardises sucrées sur présentoir', 2);

INSERT INTO plat (plat_id, titre, type, description) VALUES
 (1,  'Foie gras mi-cuit et chutney de figues', 'entree', 'Foie gras du Sud-Ouest, pain d''épices toasté'),
 (2,  'Chapon fermier aux morilles', 'plat', 'Sauce crémée aux morilles, gratin dauphinois'),
 (3,  'Bûche chocolat-marron', 'dessert', 'Biscuit chocolat, crème de marron'),
 (4,  'Velouté de châtaigne', 'entree', 'Crème de châtaigne et éclats de noisette'),
 (5,  'Wellington de légumes', 'plat', 'Légumes d''hiver et champignons en croûte feuilletée'),
 (6,  'Entremets chocolat', 'dessert', 'Mousse chocolat noir, croustillant praliné'),
 (7,  'Œufs mimosa', 'entree', 'Œufs fermiers, mayonnaise maison'),
 (8,  'Agneau confit 7 heures', 'plat', 'Épaule d''agneau, jus au thym, légumes printaniers'),
 (9,  'Nid de Pâques', 'dessert', 'Chocolat praliné et petits œufs'),
 (10, 'Salade fraîcheur de saison', 'entree', 'Crudités du marché, vinaigrette moutarde'),
 (11, 'Bœuf bourguignon', 'plat', 'Mijoté au vin rouge, pommes vapeur'),
 (12, 'Tarte fine aux pommes', 'dessert', 'Pâte feuilletée, pommes caramélisées'),
 (13, 'Houmous de betterave', 'entree', 'Pois chiches, betterave, sésame, pain pita'),
 (14, 'Curry de légumes au lait de coco', 'plat', 'Riz basmati, coriandre fraîche'),
 (15, 'Mousse coco-mangue', 'dessert', 'Lait de coco, coulis de mangue'),
 (16, 'Pièces cocktail salées', 'entree', 'Verrines, mini-cannelés salés, brochettes'),
 (17, 'Mignardises sucrées', 'dessert', 'Macarons, cannelés bordelais, tartelettes');

-- Le même plat peut être présent dans plusieurs menus (ex. plat 6 et plat 12)
INSERT INTO menu_plat (menu_id, plat_id) VALUES
 (1,1),(1,2),(1,3),
 (2,4),(2,5),(2,6),
 (3,7),(3,8),(3,9),
 (4,10),(4,11),(4,12),
 (5,13),(5,14),(5,15),
 (6,16),(6,17),(6,6);

INSERT INTO plat_allergene (plat_id, allergene_id) VALUES
 (1,1),(1,12),(2,7),(3,1),(3,3),(3,7),(3,8),(4,7),(4,8),(5,1),(5,9),(6,3),(6,7),(6,8),
 (7,3),(7,10),(8,9),(9,7),(9,8),(10,10),(11,12),(11,9),(12,1),(12,7),(12,3),
 (13,11),(16,1),(16,3),(16,7),(17,1),(17,3),(17,7),(17,8);

INSERT INTO horaire (jour, ordre, heure_ouverture, heure_fermeture, ferme) VALUES
 ('Lundi', 1, '09:00', '18:00', 0),
 ('Mardi', 2, '09:00', '18:00', 0),
 ('Mercredi', 3, '09:00', '18:00', 0),
 ('Jeudi', 4, '09:00', '18:00', 0),
 ('Vendredi', 5, '09:00', '19:00', 0),
 ('Samedi', 6, '10:00', '17:00', 0),
 ('Dimanche', 7, NULL, NULL, 1);

-- Commandes de démonstration (prix calculés selon les règles métier)
INSERT INTO commande (commande_id, numero_commande, utilisateur_id, menu_id, date_commande, date_prestation, heure_livraison,
    adresse_prestation, code_postal_prestation, ville_prestation, nombre_personne, prix_menu, remise, distance_km, prix_livraison, prix_total, statut, pret_materiel, restitution_materiel) VALUES
 (1, 'VG-2026-0001', 3, 4, '2026-10-02 10:15:00', '2026-10-10', '12:00:00', '8 rue Sainte-Catherine', '33000', 'Bordeaux', 2, 56.00, 0.00, 0.00, 0.00, 56.00, 'terminee', 0, 0),
 (2, 'VG-2026-0002', 4, 3, '2026-10-05 14:30:00', '2026-10-25', '11:30:00', '3 avenue de la Libération', '33700', 'Mérignac', 14, 518.00, 51.80, 8.20, 9.84, 476.04, 'terminee', 1, 1),
 (3, 'VG-2026-0003', 5, 5, '2026-10-20 09:00:00', '2026-11-05', '19:00:00', '21 cours Victor Hugo', '33000', 'Bordeaux', 4, 108.00, 0.00, 0.00, 0.00, 108.00, 'terminee', 0, 0),
 (4, 'VG-2026-0004', 3, 1, '2026-11-10 18:45:00', '2026-12-24', '18:00:00', '8 rue Sainte-Catherine', '33000', 'Bordeaux', 6, 270.00, 0.00, 0.00, 0.00, 270.00, 'accepte', 0, 0),
 (5, 'VG-2026-0005', 3, 2, '2026-11-15 20:10:00', '2026-12-20', '12:30:00', '8 rue Sainte-Catherine', '33000', 'Bordeaux', 4, 140.00, 0.00, 0.00, 0.00, 140.00, 'en_attente', 0, 0),
 (6, 'VG-2026-0006', 4, 6, '2026-11-12 11:00:00', '2026-11-16', '10:00:00', '3 avenue de la Libération', '33700', 'Mérignac', 25, 625.00, 62.50, 8.20, 9.84, 572.34, 'en_attente_retour_materiel', 1, 0);

INSERT INTO commande_suivi (commande_id, statut, date_modification, modifie_par) VALUES
 (1, 'en_attente', '2026-10-02 10:15:00', 3), (1, 'accepte', '2026-10-02 15:00:00', 2), (1, 'en_preparation', '2026-10-10 08:00:00', 2),
 (1, 'en_cours_de_livraison', '2026-10-10 11:20:00', 2), (1, 'livre', '2026-10-10 11:55:00', 2), (1, 'terminee', '2026-10-10 12:00:00', 2),
 (2, 'en_attente', '2026-10-05 14:30:00', 4), (2, 'accepte', '2026-10-06 09:00:00', 2), (2, 'en_preparation', '2026-10-25 07:00:00', 2),
 (2, 'en_cours_de_livraison', '2026-10-25 10:30:00', 2), (2, 'livre', '2026-10-25 11:25:00', 2), (2, 'en_attente_retour_materiel', '2026-10-25 11:30:00', 2),
 (2, 'terminee', '2026-10-29 16:00:00', 2),
 (3, 'en_attente', '2026-10-20 09:00:00', 5), (3, 'accepte', '2026-10-20 14:00:00', 2), (3, 'livre', '2026-11-05 18:50:00', 2), (3, 'terminee', '2026-11-05 19:00:00', 2),
 (4, 'en_attente', '2026-11-10 18:45:00', 3), (4, 'accepte', '2026-11-11 09:30:00', 2),
 (5, 'en_attente', '2026-11-15 20:10:00', 3),
 (6, 'en_attente', '2026-11-12 11:00:00', 4), (6, 'accepte', '2026-11-12 14:00:00', 2), (6, 'livre', '2026-11-16 09:55:00', 2),
 (6, 'en_attente_retour_materiel', '2026-11-16 10:00:00', 2);

INSERT INTO avis (commande_id, utilisateur_id, note, commentaire, statut, created_at) VALUES
 (1, 3, 5, 'Bœuf bourguignon fondant et livraison à l''heure. Julie et José sont adorables !', 'valide', '2026-10-11 09:00:00'),
 (2, 4, 4, 'Brunch de Pâques délicieux, toute la famille a adoré. Petit retard de livraison.', 'valide', '2026-10-30 10:00:00'),
 (3, 5, 5, 'Enfin un traiteur qui fait du vegan gourmand. Je recommande vivement.', 'en_attente', '2026-11-06 08:30:00');
